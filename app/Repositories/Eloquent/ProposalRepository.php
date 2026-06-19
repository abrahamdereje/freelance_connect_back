<?php

namespace App\Repositories\Eloquent;

use App\Models\Proposal;
use App\Repositories\Contracts\ProposalRepositoryInterface;

class ProposalRepository implements ProposalRepositoryInterface
{
    public function find(int $id): ?Proposal
    {
        return Proposal::with(['job', 'freelancer'])->find($id);
    }

    public function findForJobAndFreelancer(int $jobId, int $freelancerId): ?Proposal
    {
        return Proposal::where('job_id', $jobId)
            ->where('freelancer_id', $freelancerId)
            ->first();
    }

    public function create(array $data): Proposal
    {
        return Proposal::create($data);
    }

    public function update(Proposal $proposal, array $data): bool
    {
        return $proposal->update($data);
    }

    public function delete(Proposal $proposal): bool
    {
        return $proposal->delete();
    }

    public function getForJob(int $jobId)
    {
        return Proposal::with([
            'freelancer.freelancerProfile.skills',
            'freelancer.reviewsReceived.reviewer'
        ])
        ->where('job_id', $jobId)
        ->latest()
        ->get();
    }

    public function getForFreelancer(int $freelancerId)
    {
        return Proposal::with('job')
            ->where('freelancer_id', $freelancerId)
            ->latest()
            ->get();
    }
}
