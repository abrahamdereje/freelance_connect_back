<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\StoreJobRequest;
use App\Services\JobService;
use App\Repositories\Contracts\JobRepositoryInterface;
use App\Http\Resources\JobResource;
use App\DTOs\JobDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobController extends ApiController
{
    public function __construct(
        protected JobService $jobService,
        protected JobRepositoryInterface $jobRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'type', 'min_budget', 'max_budget', 'skills']);
        $jobs = $this->jobRepository->searchAndFilter($filters, (int) $request->get('per_page', 15));

        return $this->successResponse(
            JobResource::collection($jobs)->response()->getData(true),
            'Jobs retrieved successfully.'
        );
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        Gate::authorize('create', \App\Models\Job::class);

        $dto = JobDTO::fromArray($request->validated());
        $job = $this->jobService->createJob($request->user()->id, $dto);

        return $this->successResponse(
            new JobResource($job->load(['employer', 'category', 'skills', 'attachments'])),
            'Job created successfully.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);

        if (!$job) {
            return $this->errorResponse('Job not found.', 404);
        }

        return $this->successResponse(
            new JobResource($job),
            'Job retrieved successfully.'
        );
    }

    public function update(StoreJobRequest $request, int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);

        if (!$job) {
            return $this->errorResponse('Job not found.', 404);
        }

        Gate::authorize('update', $job);

        $dto = JobDTO::fromArray($request->validated());
        $updatedJob = $this->jobService->updateJob($job, $dto);

        return $this->successResponse(
            new JobResource($updatedJob),
            'Job updated successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);

        if (!$job) {
            return $this->errorResponse('Job not found.', 404);
        }

        Gate::authorize('delete', $job);

        $this->jobService->deleteJob($job);

        return $this->successResponse(null, 'Job deleted successfully.');
    }

    public function close(int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);

        if (!$job) {
            return $this->errorResponse('Job not found.', 404);
        }

        Gate::authorize('update', $job);

        if ($job->status !== \App\Enums\JobStatus::OPEN) {
            return $this->errorResponse('Only open jobs can be closed.', 400);
        }

        $job->update(['status' => \App\Enums\JobStatus::CANCELLED]);

        return $this->successResponse(
            new JobResource($job),
            'Job closed successfully.'
        );
    }
}
