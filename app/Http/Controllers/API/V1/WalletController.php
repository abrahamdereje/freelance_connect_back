<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\DepositRequest;
use App\Services\WalletService;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Http\Resources\WalletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WalletController extends ApiController
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletRepositoryInterface $walletRepository
    ) {}

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletRepository->findByUserId($request->user()->id);

        if (!$wallet) {
            $wallet = $this->walletRepository->createWallet($request->user()->id);
        }

        Gate::authorize('view', $wallet);

        return $this->successResponse(
            new WalletResource($wallet->load('transactions')),
            'Wallet details retrieved successfully.'
        );
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $wallet = $this->walletRepository->findByUserId($request->user()->id);

        if (!$wallet) {
            $wallet = $this->walletRepository->createWallet($request->user()->id);
        }

        Gate::authorize('deposit', $wallet);

        $transaction = $this->walletService->deposit($request->user(), (float) $request->validated()['amount']);

        return $this->successResponse(
            new WalletResource($wallet->fresh('transactions')),
            'Deposit successful.'
        );
    }
}
