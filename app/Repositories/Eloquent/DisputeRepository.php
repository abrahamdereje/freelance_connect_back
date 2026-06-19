<?php

namespace App\Repositories\Eloquent;

use App\Models\Dispute;
use App\Repositories\Contracts\DisputeRepositoryInterface;

class DisputeRepository implements DisputeRepositoryInterface
{
    public function find(int $id): ?Dispute
    {
        return Dispute::with(['contract', 'milestone', 'raiser', 'resolver'])->find($id);
    }

    public function create(array $data): Dispute
    {
        return Dispute::create($data);
    }

    public function update(Dispute $dispute, array $data): bool
    {
        return $dispute->update($data);
    }

    public function paginate(int $perPage = 15)
    {
        return Dispute::with(['contract', 'raiser'])
            ->latest()
            ->paginate($perPage);
    }

    public function getForContract(int $contractId)
    {
        return Dispute::where('contract_id', $contractId)
            ->latest()
            ->get();
    }
}
