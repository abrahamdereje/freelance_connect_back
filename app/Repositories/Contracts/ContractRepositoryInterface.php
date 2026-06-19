<?php

namespace App\Repositories\Contracts;

use App\Models\Contract;

interface ContractRepositoryInterface
{
    public function find(int $id): ?Contract;
    public function create(array $data): Contract;
    public function update(Contract $contract, array $data): bool;
    public function getForEmployer(int $employerId);
    public function getForFreelancer(int $freelancerId);
}
