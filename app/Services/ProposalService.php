<?php

namespace App\Services;

use App\DTOs\ProposalDTO;
use App\Models\Proposal;
use App\Repositories\Contracts\ProposalRepositoryInterface;
use App\Repositories\Contracts\JobRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ProposalService
{
    public function __construct(
        protected ProposalRepositoryInterface $proposalRepository,
        protected JobRepositoryInterface $jobRepository
    ) {}

    public function submitProposal(int $freelancerId, int $jobId, ProposalDTO $dto): Proposal
    {
        $job = $this->jobRepository->find($jobId);
        if (!$job || $job->status !== \App\Enums\JobStatus::OPEN) {
            throw ValidationException::withMessages([
                'job_id' => ['You can only bid on open jobs.'],
            ]);
        }

        $existing = $this->proposalRepository->findForJobAndFreelancer($jobId, $freelancerId);
        if ($existing) {
            throw ValidationException::withMessages([
                'job_id' => ['You have already submitted a proposal for this job.'],
            ]);
        }

        return $this->proposalRepository->create([
            'job_id' => $jobId,
            'freelancer_id' => $freelancerId,
            'cover_letter' => $dto->cover_letter,
            'bid_amount' => $dto->bid_amount,
            'estimated_duration_days' => $dto->estimated_duration_days,
            'status' => \App\Enums\ProposalStatus::PENDING,
        ]);
    }

    public function withdrawProposal(Proposal $proposal): bool
    {
        if ($proposal->status !== \App\Enums\ProposalStatus::PENDING) {
            throw ValidationException::withMessages([
                'proposal' => ['Only pending proposals can be withdrawn.'],
            ]);
        }

        return $this->proposalRepository->update($proposal, [
            'status' => \App\Enums\ProposalStatus::WITHDRAWN,
        ]);
    }
}
