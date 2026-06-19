<?php

namespace App\Services;

use App\DTOs\DisputeDTO;
use App\Models\Dispute;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\User;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use App\Enums\DisputeStatus;
use App\Enums\ContractStatus;
use App\Enums\JobStatus;
use Illuminate\Validation\ValidationException;

class DisputeService
{
    public function __construct(
        protected DisputeRepositoryInterface $disputeRepository,
        protected WalletService $walletService
    ) {}

    public function raiseDispute(int $raiserId, DisputeDTO $dto): Dispute
    {
        $contract = Contract::find($dto->contract_id);
        if (!$contract || ($contract->status !== ContractStatus::ACTIVE && $contract->status !== ContractStatus::COMPLETED)) {
            throw ValidationException::withMessages([
                'contract_id' => ['Disputes can only be raised on active or completed contracts.'],
            ]);
        }

        if ($contract->employer_id !== $raiserId && $contract->freelancer_id !== $raiserId) {
            throw ValidationException::withMessages([
                'raiser_id' => ['You are not authorized to raise a dispute on this contract.'],
            ]);
        }

        return DB::transaction(function () use ($raiserId, $contract, $dto) {
            $dispute = $this->disputeRepository->create([
                'contract_id' => $dto->contract_id,
                'milestone_id' => $dto->milestone_id,
                'raiser_id' => $raiserId,
                'reason' => $dto->reason,
                'description' => $dto->description,
                'evidence_path' => $dto->evidence_path,
                'status' => DisputeStatus::PENDING,
            ]);

            $contract->update(['status' => ContractStatus::DISPUTED]);

            return $dispute;
        });
    }

    public function resolveDispute(User $admin, Dispute $dispute, string $resolution, string $details): Dispute
    {
        if ($dispute->status !== DisputeStatus::PENDING && $dispute->status !== DisputeStatus::RESOLVING) {
            throw ValidationException::withMessages([
                'dispute' => ['This dispute has already been resolved.'],
            ]);
        }

        if ($resolution !== 'release' && $resolution !== 'refund') {
            throw ValidationException::withMessages([
                'resolution' => ['Resolution must be either "release" or "refund".'],
            ]);
        }

        return DB::transaction(function () use ($admin, $dispute, $resolution, $details) {
            $contract = $dispute->contract;
            $milestone = $dispute->milestone;

            if ($milestone) {
                if ($resolution === 'release') {
                    $this->walletService->releaseEscrow($contract, $milestone);
                } else {
                    $this->walletService->refundEscrow($contract, $milestone);
                }
            } else {
                if ($contract->milestones()->exists()) {
                    $heldMilestones = $contract->milestones()->where('status', \App\Enums\MilestoneStatus::FUNDED)->get();
                    foreach ($heldMilestones as $m) {
                        if ($resolution === 'release') {
                            $this->walletService->releaseEscrow($contract, $m);
                        } else {
                            $this->walletService->refundEscrow($contract, $m);
                        }
                    }
                }
            }

            $this->disputeRepository->update($dispute, [
                'status' => DisputeStatus::RESOLVED,
                'resolution_details' => $details,
                'resolved_by' => $admin->id,
            ]);

            $hasPendingDisputes = Dispute::where('contract_id', $contract->id)
                ->where('status', DisputeStatus::PENDING)
                ->exists();

            if (!$hasPendingDisputes) {
                $allMilestonesReleased = !$contract->milestones()
                    ->where('status', '!=', \App\Enums\MilestoneStatus::RELEASED)
                    ->exists();

                if ($allMilestonesReleased) {
                    $contract->update([
                        'status' => ContractStatus::COMPLETED,
                        'end_date' => now()
                    ]);
                    $contract->job->update(['status' => JobStatus::COMPLETED]);
                } else {
                    $contract->update(['status' => ContractStatus::TERMINATED]);
                    $contract->job->update(['status' => JobStatus::CANCELLED]);
                }
            }

            return $dispute->load(['contract', 'resolver']);
        });
    }
}
