<?php

namespace App\Repositories\Eloquent;

use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;

class ContractRepository implements ContractRepositoryInterface
{
    public function find(int $id): ?Contract
    {
        return Contract::with([
            'job',
            'employer.employerProfile',
            'employer.reviewsReceived.reviewer',
            'freelancer.freelancerProfile.skills',
            'freelancer.reviewsReceived.reviewer',
            'proposal',
            'milestones',
            'escrow',
            'reviews.reviewer',
            'reviews.reviewee'
        ])->find($id);
    }

    public function create(array $data): Contract
    {
        return Contract::create($data);
    }

    public function update(Contract $contract, array $data): bool
    {
        return $contract->update($data);
    }

    public function getForEmployer(int $employerId)
    {
        return Contract::with(['freelancer', 'job'])
            ->where('employer_id', $employerId)
            ->latest()
            ->get();
    }

    public function getForFreelancer(int $freelancerId)
    {
        return Contract::with(['employer', 'job'])
            ->where('freelancer_id', $freelancerId)
            ->latest()
            ->get();
    }
}
