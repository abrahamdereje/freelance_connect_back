<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Services\ContractService;
use App\Services\WalletService;
use App\Services\NotificationService;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\ProposalRepositoryInterface;
use App\Http\Resources\ContractResource;
use App\Models\Milestone;
use App\DTOs\MilestoneDTO;
use App\Enums\ContractStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractController extends ApiController
{
    public function __construct(
        protected ContractService $contractService,
        protected WalletService $walletService,
        protected NotificationService $notificationService,
        protected ContractRepositoryInterface $contractRepository,
        protected ProposalRepositoryInterface $proposalRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isEmployer()) {
            $contracts = $this->contractRepository->getForEmployer($user->id);
        } elseif ($user->isFreelancer()) {
            $contracts = $this->contractRepository->getForFreelancer($user->id);
        } else {
            // Admin lists all
            $contracts = \App\Models\Contract::with(['employer', 'freelancer', 'job'])->latest()->get();
        }

        return $this->successResponse(
            ContractResource::collection($contracts),
            'Contracts retrieved successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $contract = $this->contractRepository->find($id);

        if (!$contract) {
            return $this->errorResponse('Contract not found.', 404);
        }

        Gate::authorize('view', $contract);

        return $this->successResponse(
            new ContractResource($contract),
            'Contract retrieved successfully.'
        );
    }

    public function hire(int $proposalId): JsonResponse
    {
        $proposal = $this->proposalRepository->find($proposalId);

        if (!$proposal) {
            return $this->errorResponse('Proposal not found.', 404);
        }

        Gate::authorize('acceptReject', $proposal);

        $contract = $this->contractService->hire($proposal);

        // Notify the freelancer they were hired
        $this->notificationService->freelancerHired($contract);

        return $this->successResponse(
            new ContractResource($contract),
            'Hired successfully. Contract and initial milestone generated.',
            201
        );
    }

    public function addMilestone(Request $request, int $contractId): JsonResponse
    {
        $contract = $this->contractRepository->find($contractId);

        if (!$contract) {
            return $this->errorResponse('Contract not found.', 404);
        }

        Gate::authorize('fundMilestone', $contract);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'due_date' => ['nullable', 'date'],
        ]);

        $dto = MilestoneDTO::fromArray($validated);
        $milestone = $this->contractService->addMilestone($contract, $dto);

        return $this->successResponse(
            $milestone,
            'Milestone added successfully.',
            201
        );
    }

    public function fundMilestone(int $contractId, int $milestoneId): JsonResponse
    {
        $contract = $this->contractRepository->find($contractId);
        if (!$contract) {
            return $this->errorResponse('Contract not found.', 404);
        }

        Gate::authorize('fundMilestone', $contract);

        $milestone = Milestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->errorResponse('Milestone not found.', 404);
        }

        try {
            $this->walletService->holdEscrow($contract, $milestone);
            return $this->successResponse(
                $milestone->fresh(),
                'Milestone funded and escrow held successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function submitWork(int $contractId, int $milestoneId): JsonResponse
    {
        $contract = $this->contractRepository->find($contractId);
        if (!$contract) {
            return $this->errorResponse('Contract not found.', 404);
        }

        Gate::authorize('submitWork', $contract);

        $milestone = Milestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->errorResponse('Milestone not found.', 404);
        }

        try {
            $updated = $this->contractService->submitWork($milestone);

            // Notify employer that work was submitted
            $this->notificationService->milestoneSubmitted($updated);

            return $this->successResponse(
                $updated,
                'Work submitted for review successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function releaseMilestone(int $contractId, int $milestoneId): JsonResponse
    {
        $contract = $this->contractRepository->find($contractId);
        if (!$contract) {
            return $this->errorResponse('Contract not found.', 404);
        }

        Gate::authorize('releaseMilestone', $contract);

        $milestone = Milestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->errorResponse('Milestone not found.', 404);
        }

        try {
            $this->contractService->releaseMilestone($milestone);
            $milestone->refresh();

            // Notify freelancer of payment release
            $this->notificationService->milestoneReleased($milestone);

            // If contract is now completed, notify both parties
            $contract->refresh();
            if ($contract->status === ContractStatus::COMPLETED) {
                $this->notificationService->contractCompleted($contract);
            }

            return $this->successResponse(
                $milestone->fresh(),
                'Milestone released successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    public function end(int $id): JsonResponse
    {
        $contract = $this->contractRepository->find($id);
        if (!$contract) {
            return $this->errorResponse('Contract not found.', 404);
        }

        Gate::authorize('view', $contract); // User should be part of it

        try {
            $updated = $this->contractService->endContract($contract);
            return $this->successResponse(
                new ContractResource($updated),
                'Contract successfully ended.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
