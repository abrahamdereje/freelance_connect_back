<?php

namespace App\Repositories\Contracts;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

interface JobRepositoryInterface
{
    public function find(int $id): ?Job;
    public function create(array $data): Job;
    public function update(Job $job, array $data): bool;
    public function delete(Job $job): bool;
    public function searchAndFilter(array $filters, int $perPage = 15): LengthAwarePaginator;
}
