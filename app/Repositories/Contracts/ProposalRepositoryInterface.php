<?php

namespace App\Repositories\Contracts;

use App\Models\Proposal;

interface ProposalRepositoryInterface
{
    public function find(int $id): ?Proposal;
    public function findForJobAndFreelancer(int $jobId, int $freelancerId): ?Proposal;
    public function create(array $data): Proposal;
    public function update(Proposal $proposal, array $data): bool;
    public function delete(Proposal $proposal): bool;
    public function getForJob(int $jobId);
    public function getForFreelancer(int $freelancerId);
}
