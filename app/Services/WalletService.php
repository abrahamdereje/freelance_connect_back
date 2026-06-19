<?php

namespace App\Services;

use App\Models\User;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Wallet;
use App\Models\Escrow;
use App\Models\Transaction;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Enums\TransactionType;
use App\Enums\EscrowStatus;
use App\Enums\MilestoneStatus;

class WalletService
{
    public function __construct(
        protected WalletRepositoryInterface $walletRepository
    ) {}

    public function deposit(User $user, float $amount): Transaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Deposit amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use ($user, $amount) {
            $wallet = $this->walletRepository->findByUserId($user->id);
            if (!$wallet) {
                $wallet = $this->walletRepository->createWallet($user->id);
            }

            $this->walletRepository->updateBalance($wallet, $amount);

            return $this->walletRepository->createTransaction([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => TransactionType::DEPOSIT,
                'description' => 'Deposited virtual funds into wallet.',
            ]);
        });
    }

    public function holdEscrow(Contract $contract, Milestone $milestone): Escrow
    {
        return DB::transaction(function () use ($contract, $milestone) {
            if ($milestone->status !== MilestoneStatus::PENDING) {
                throw new \Exception('Milestone is already funded or processed.');
            }

            $employer = $contract->employer;
            $wallet = $this->walletRepository->findByUserId($employer->id);

            if (!$wallet || $wallet->balance < $milestone->amount) {
                throw new \Exception('Insufficient wallet balance to fund this milestone.');
            }

            // Deduct from employer wallet
            $this->walletRepository->updateBalance($wallet, -$milestone->amount);

            // Record transaction
            $this->walletRepository->createTransaction([
                'wallet_id' => $wallet->id,
                'amount' => -$milestone->amount,
                'type' => TransactionType::ESCROW_HOLD,
                'description' => "Escrow hold for Contract milestone: {$milestone->title}",
                'reference_type' => Milestone::class,
                'reference_id' => $milestone->id,
            ]);

            // Create Escrow record
            $escrow = $this->walletRepository->createEscrow([
                'contract_id' => $contract->id,
                'milestone_id' => $milestone->id,
                'amount' => $milestone->amount,
                'status' => EscrowStatus::HELD,
            ]);

            // Update milestone status
            $milestone->update(['status' => MilestoneStatus::FUNDED]);

            return $escrow;
        });
    }

    public function releaseEscrow(Contract $contract, Milestone $milestone): void
    {
        DB::transaction(function () use ($contract, $milestone) {
            $escrow = Escrow::where('milestone_id', $milestone->id)
                ->where('status', EscrowStatus::HELD)
                ->first();

            if (!$escrow) {
                throw new \Exception('No active escrow found for this milestone.');
            }

            $freelancer = $contract->freelancer;
            $wallet = $this->walletRepository->findByUserId($freelancer->id);
            if (!$wallet) {
                $wallet = $this->walletRepository->createWallet($freelancer->id);
            }

            // Add to freelancer wallet
            $this->walletRepository->updateBalance($wallet, $escrow->amount);

            // Record transaction
            $this->walletRepository->createTransaction([
                'wallet_id' => $wallet->id,
                'amount' => $escrow->amount,
                'type' => TransactionType::ESCROW_RELEASE,
                'description' => "Escrow release for Contract milestone: {$milestone->title}",
                'reference_type' => Milestone::class,
                'reference_id' => $milestone->id,
            ]);

            // Update Escrow record
            $escrow->update(['status' => EscrowStatus::RELEASED]);

            // Update milestone status
            $milestone->update(['status' => MilestoneStatus::RELEASED]);
        });
    }

    public function refundEscrow(Contract $contract, Milestone $milestone): void
    {
        DB::transaction(function () use ($contract, $milestone) {
            $escrow = Escrow::where('milestone_id', $milestone->id)
                ->where('status', EscrowStatus::HELD)
                ->first();

            if (!$escrow) {
                throw new \Exception('No active escrow found for this milestone.');
            }

            $employer = $contract->employer;
            $wallet = $this->walletRepository->findByUserId($employer->id);
            if (!$wallet) {
                $wallet = $this->walletRepository->createWallet($employer->id);
            }

            // Return to employer wallet
            $this->walletRepository->updateBalance($wallet, $escrow->amount);

            // Record transaction
            $this->walletRepository->createTransaction([
                'wallet_id' => $wallet->id,
                'amount' => $escrow->amount,
                'type' => TransactionType::REFUND,
                'description' => "Refunded escrow for milestone: {$milestone->title}",
                'reference_type' => Milestone::class,
                'reference_id' => $milestone->id,
            ]);

            // Update Escrow record
            $escrow->update(['status' => EscrowStatus::REFUNDED]);

            // Update milestone status
            $milestone->update(['status' => MilestoneStatus::REFUNDED]);
        });
    }
}
