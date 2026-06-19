<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function submitReview(int $reviewerId, int $contractId, array $data): Review
    {
        $contract = Contract::find($contractId);
        if (!$contract || $contract->status !== \App\Enums\ContractStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'contract_id' => ['You can only review completed contracts.'],
            ]);
        }

        if ($contract->employer_id !== $reviewerId && $contract->freelancer_id !== $reviewerId) {
            throw ValidationException::withMessages([
                'contract_id' => ['You are not a party to this contract.'],
            ]);
        }

        $revieweeId = ($contract->employer_id === $reviewerId) ? $contract->freelancer_id : $contract->employer_id;

        $existing = Review::where('contract_id', $contractId)
            ->where('reviewer_id', $reviewerId)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'contract_id' => ['You have already reviewed this contract.'],
            ]);
        }

        return DB::transaction(function () use ($reviewerId, $revieweeId, $contractId, $data) {
            $review = Review::create([
                'contract_id' => $contractId,
                'reviewer_id' => $reviewerId,
                'reviewee_id' => $revieweeId,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->recalculateUserRating($revieweeId);

            return $review;
        });
    }

    public function recalculateUserRating(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $avgRating = Review::where('reviewee_id', $userId)->avg('rating') ?? 0.00;

        if ($user->isFreelancer()) {
            $profile = $user->freelancerProfile;
            if ($profile) {
                $profile->update(['rating' => $avgRating]);
            }
        } elseif ($user->isEmployer()) {
            $profile = $user->employerProfile;
            if ($profile) {
                $profile->update(['rating' => $avgRating]);
            }
        }
    }
}
