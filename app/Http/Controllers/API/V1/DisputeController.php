<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\StoreDisputeRequest;
use App\Http\Requests\ResolveDisputeRequest;
use App\Services\DisputeService;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use App\Http\Resources\DisputeResource;
use App\DTOs\DisputeDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DisputeController extends ApiController
{
    public function __construct(
        protected DisputeService $disputeService,
        protected DisputeRepositoryInterface $disputeRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $disputes = $this->disputeRepository->paginate((int) $request->get('per_page', 15));
            return $this->successResponse(
                DisputeResource::collection($disputes)->response()->getData(true),
                'All disputes retrieved successfully.'
            );
        }

        // For regular users, filter by raiser or participant of contract
        $disputes = \App\Models\Dispute::with(['contract', 'raiser'])
            ->where('raiser_id', $user->id)
            ->orWhereHas('contract', function ($q) use ($user) {
                $q->where('employer_id', $user->id)
                  ->orWhere('freelancer_id', $user->id);
            })
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return $this->successResponse(
            DisputeResource::collection($disputes)->response()->getData(true),
            'Your disputes retrieved successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $dispute = $this->disputeRepository->find($id);

        if (!$dispute) {
            return $this->errorResponse('Dispute not found.', 404);
        }

        Gate::authorize('view', $dispute);

        return $this->successResponse(
            new DisputeResource($dispute),
            'Dispute retrieved successfully.'
        );
    }

    public function store(StoreDisputeRequest $request): JsonResponse
    {
        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('dispute_evidence', 'public');
        }

        $data = $request->validated();
        $data['evidence_path'] = $evidencePath;

        $dto = DisputeDTO::fromArray($data);
        $dispute = $this->disputeService->raiseDispute($request->user()->id, $dto);

        return $this->successResponse(
            new DisputeResource($dispute->load(['contract', 'raiser'])),
            'Dispute raised successfully.',
            201
        );
    }

    public function resolve(ResolveDisputeRequest $request, int $id): JsonResponse
    {
        $dispute = $this->disputeRepository->find($id);

        if (!$dispute) {
            return $this->errorResponse('Dispute not found.', 404);
        }

        Gate::authorize('resolve', $dispute);

        try {
            $resolved = $this->disputeService->resolveDispute(
                $request->user(),
                $dispute,
                $request->validated()['resolution'],
                $request->validated()['details']
            );

            return $this->successResponse(
                new DisputeResource($resolved),
                'Dispute resolved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
