<?php

namespace App\Repositories\Contracts;

use App\Models\Dispute;

interface DisputeRepositoryInterface
{
    public function find(int $id): ?Dispute;
    public function create(array $data): Dispute;
    public function update(Dispute $dispute, array $data): bool;
    public function paginate(int $perPage = 15);
    public function getForContract(int $contractId);
}
