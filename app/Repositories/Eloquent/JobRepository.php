<?php

namespace App\Repositories\Eloquent;

use App\Models\Job;
use App\Repositories\Contracts\JobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class JobRepository implements JobRepositoryInterface
{
    public function find(int $id): ?Job
    {
        return Job::with(['employer', 'category', 'skills', 'attachments'])->find($id);
    }

    public function create(array $data): Job
    {
        return Job::create($data);
    }

    public function update(Job $job, array $data): bool
    {
        return $job->update($data);
    }

    public function delete(Job $job): bool
    {
        return $job->delete();
    }

    public function searchAndFilter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = Job::with(['employer', 'category', 'skills'])
            ->withCount('proposals');

        // If an authenticated employer is requesting, show their jobs too (regardless of status).
        // For everyone else, only show open jobs.
        if ($user && $user->isEmployer()) {
            $query->where(function ($q) use ($user) {
                $q->where('status', 'open')
                  ->orWhere('employer_id', $user->id);
            });
        } else {
            $query->where('status', 'open');
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['min_budget'])) {
            $query->where('budget', '>=', $filters['min_budget']);
        }

        if (isset($filters['max_budget'])) {
            $query->where('budget', '<=', $filters['max_budget']);
        }

        if (!empty($filters['skills'])) {
            $skills = (array) $filters['skills'];
            $query->whereHas('skills', function ($q) use ($skills) {
                $q->whereIn('skills.id', $skills);
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
