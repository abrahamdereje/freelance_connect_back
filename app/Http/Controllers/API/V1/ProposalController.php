<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\StoreProposalRequest;
use App\Services\ProposalService;
use App\Services\NotificationService;
use App\Repositories\Contracts\ProposalRepositoryInterface;
use App\Repositories\Contracts\JobRepositoryInterface;
use App\Http\Resources\ProposalResource;
use App\DTOs\ProposalDTO;
use App\Enums\ProposalStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProposalController extends ApiController
{
    public function __construct(
        protected ProposalService $proposalService,
        protected NotificationService $notificationService,
        protected ProposalRepositoryInterface $proposalRepository,
        protected JobRepositoryInterface $jobRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isFreelancer()) {
            $proposals = $this->proposalRepository->getForFreelancer($user->id);
            return $this->successResponse(
                ProposalResource::collection($proposals),
                'Your proposals retrieved successfully.'
            );
        }

        return $this->errorResponse('Access Denied. Only freelancers can view their proposals listing here.', 403);
    }

    public function getJobProposals(int $jobId): JsonResponse
    {
        $job = $this->jobRepository->find($jobId);

        if (!$job) {
            return $this->errorResponse('Job not found.', 404);
        }

        if ($job->employer_id !== auth()->id()) {
            return $this->errorResponse('Forbidden: You do not own this job listing.', 403);
        }

        $proposals = $this->proposalRepository->getForJob($jobId);

        return $this->successResponse(
            ProposalResource::collection($proposals),
            'Job proposals retrieved successfully.'
        );
    }

    public function store(StoreProposalRequest $request, int $jobId): JsonResponse
    {
        Gate::authorize('create', \App\Models\Proposal::class);

        $dto = ProposalDTO::fromArray($request->validated());
        $proposal = $this->proposalService->submitProposal($request->user()->id, $jobId, $dto);

        // Notify the job owner about the new proposal
        $this->notificationService->proposalReceived($proposal);

        return $this->successResponse(
            new ProposalResource($proposal->load(['job', 'freelancer'])),
            'Proposal submitted successfully.',
            201
        );
    }

    public function reject(int $id): JsonResponse
    {
        $proposal = $this->proposalRepository->find($id);

        if (!$proposal) {
            return $this->errorResponse('Proposal not found.', 404);
        }

        // Ensure the employer owns the job this proposal belongs to
        $proposal->load('job');
        if ($proposal->job->employer_id !== auth()->id()) {
            return $this->errorResponse('Forbidden: You do not own this job listing.', 403);
        }

        if ($proposal->status !== ProposalStatus::PENDING) {
            return $this->errorResponse('Only pending proposals can be rejected.', 422);
        }

        $this->proposalRepository->update($proposal, ['status' => ProposalStatus::REJECTED]);
        $proposal->refresh();

        // Notify the freelancer
        $this->notificationService->proposalRejected($proposal);

        return $this->successResponse(
            new ProposalResource($proposal->load(['job', 'freelancer'])),
            'Proposal rejected.'
        );
    }

    public function withdraw(int $id): JsonResponse
    {
        $proposal = $this->proposalRepository->find($id);

        if (!$proposal) {
            return $this->errorResponse('Proposal not found.', 404);
        }

        Gate::authorize('update', $proposal);

        $this->proposalService->withdrawProposal($proposal);

        return $this->successResponse(
            new ProposalResource($proposal),
            'Proposal withdrawn successfully.'
        );
    }
}
