<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\StoreReviewRequest;
use App\Services\ReviewService;
use App\Services\NotificationService;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\JsonResponse;

class ReviewController extends ApiController
{
    public function __construct(
        protected ReviewService $reviewService,
        protected NotificationService $notificationService
    ) {}

    public function store(StoreReviewRequest $request, int $contractId): JsonResponse
    {
        try {
            $review = $this->reviewService->submitReview(
                auth()->id(),
                $contractId,
                $request->validated()
            );

            // Notify the person being reviewed
            $this->notificationService->reviewReceived($review);

            return $this->successResponse(
                new ReviewResource($review->load(['reviewer', 'reviewee'])),
                'Review submitted successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
