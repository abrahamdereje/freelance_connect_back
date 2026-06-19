<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\Contract;
use App\Models\Milestone;
use App\DTOs\MilestoneDTO;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\ProposalRepositoryInterface;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use App\Enums\ProposalStatus;
use App\Enums\JobStatus;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use Illuminate\Validation\ValidationException;

class ContractService
{
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
        protected ProposalRepositoryInterface $proposalRepository,
        protected WalletService $walletService
    ) {}

    public function hire(Proposal $proposal): Contract
    {
        if ($proposal->status !== ProposalStatus::PENDING) {
            throw ValidationException::withMessages([
                'proposal' => ['This proposal is no longer pending.'],
            ]);
        }

        return DB::transaction(function () use ($proposal) {
            // Accept this proposal
            $proposal->update(['status' => ProposalStatus::ACCEPTED]);

            // Reject all other proposals for this job
            Proposal::where('job_id', $proposal->job_id)
                ->where('id', '!=', $proposal->id)
                ->update(['status' => ProposalStatus::REJECTED]);

            // Update job status to in_progress
            $proposal->job->update(['status' => JobStatus::IN_PROGRESS]);

            // Create Contract
            $contract = $this->contractRepository->create([
                'job_id' => $proposal->job_id,
                'employer_id' => $proposal->job->employer_id,
                'freelancer_id' => $proposal->freelancer_id,
                'proposal_id' => $proposal->id,
                'title' => $proposal->job->title,
                'total_amount' => $proposal->bid_amount,
                'status' => ContractStatus::ACTIVE,
                'start_date' => now(),
            ]);

            // Create default milestone
            $contract->milestones()->create([
                'title' => 'Initial Milestone',
                'amount' => $proposal->bid_amount,
                'status' => MilestoneStatus::PENDING,
            ]);

            return $contract->load('milestones');
        });
    }

    public function addMilestone(Contract $contract, MilestoneDTO $dto): Milestone
    {
        if ($contract->status !== ContractStatus::ACTIVE) {
            throw new \Exception('Milestones can only be added to active contracts.');
        }

        return DB::transaction(function () use ($contract, $dto) {
            $milestone = $contract->milestones()->create([
                'title' => $dto->title,
                'amount' => $dto->amount,
                'status' => MilestoneStatus::PENDING,
                'due_date' => $dto->due_date,
            ]);

            // Update contract total amount
            $contract->increment('total_amount', $dto->amount);

            return $milestone;
        });
    }

    public function submitWork(Milestone $milestone): Milestone
    {
        if ($milestone->status !== MilestoneStatus::FUNDED) {
            throw new \Exception('Milestone must be funded before submitting work.');
        }

        $milestone->update(['status' => MilestoneStatus::IN_REVIEW]);
        return $milestone;
    }

    public function releaseMilestone(Milestone $milestone): void
    {
        if ($milestone->status !== MilestoneStatus::FUNDED && $milestone->status !== MilestoneStatus::IN_REVIEW) {
            throw new \Exception('Milestone must be funded or in review to release funds.');
        }

        DB::transaction(function () use ($milestone) {
            $contract = $milestone->contract;

            // Release escrow using WalletService
            $this->walletService->releaseEscrow($contract, $milestone);

            // Check if all milestones are released
            $allReleased = !$contract->milestones()
                ->where('status', '!=', MilestoneStatus::RELEASED)
                ->exists();

            if ($allReleased) {
                $contract->update([
                    'status' => ContractStatus::COMPLETED,
                    'end_date' => now(),
                ]);
                $contract->job->update(['status' => JobStatus::COMPLETED]);
            }
        });
    }
    public function endContract(Contract $contract): Contract
    {
        if ($contract->status !== ContractStatus::ACTIVE) {
            throw new \Exception('Only active contracts can be ended.');
        }

        return DB::transaction(function () use ($contract) {
            // Refund any funded milestones that are not released
            $unreleasedMilestones = $contract->milestones()
                ->whereIn('status', [MilestoneStatus::FUNDED, MilestoneStatus::IN_REVIEW])
                ->get();

            foreach ($unreleasedMilestones as $milestone) {
                $this->walletService->refundEscrow($contract, $milestone);
            }

            // Cancel any pending milestones
            $contract->milestones()->where('status', MilestoneStatus::PENDING)->delete();

            $contract->update([
                'status' => ContractStatus::COMPLETED,
                'end_date' => now(),
            ]);
            
            $contract->job->update(['status' => JobStatus::COMPLETED]);

            return $contract;
        });
    }
}
