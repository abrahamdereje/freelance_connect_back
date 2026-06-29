<?php

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Review;

class NotificationService
{
    /**
     * Create a notification and broadcast it to the user in real time.
     */
    public function send(int $userId, string $type, string $title, string $body, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        try {
            broadcast(new NotificationSent($notification));
        } catch (\Exception $e) {
            \Log::warning("Broadcasting notification failed: " . $e->getMessage());
        }

        return $notification;
    }

    // ----------------------------------------------------------------
    // Domain-specific helpers
    // ----------------------------------------------------------------

    /** Employer receives a new proposal on their job */
    public function proposalReceived(Proposal $proposal): void
    {
        $proposal->load(['job', 'freelancer']);
        $this->send(
            $proposal->job->employer_id,
            'proposal_received',
            'New Proposal Received',
            "{$proposal->freelancer->name} submitted a proposal for \"{$proposal->job->title}\".",
            ['proposal_id' => $proposal->id, 'job_id' => $proposal->job_id]
        );
    }

    /** Freelancer learns they were hired */
    public function freelancerHired(Contract $contract): void
    {
        $contract->load(['job', 'employer']);
        $this->send(
            $contract->freelancer_id,
            'hired',
            'You Were Hired! 🎉',
            "Congratulations! You have been hired for \"{$contract->job->title}\" by {$contract->employer->name}.",
            ['contract_id' => $contract->id, 'job_id' => $contract->job_id]
        );
    }

    /** Employer learns a milestone was submitted for review */
    public function milestoneSubmitted(Milestone $milestone): void
    {
        $milestone->load(['contract.employer', 'contract.freelancer']);
        $contract = $milestone->contract;
        $this->send(
            $contract->employer_id,
            'milestone_submitted',
            'Milestone Submitted for Review',
            "{$contract->freelancer->name} submitted work for \"{$milestone->title}\" — please review and release.",
            ['contract_id' => $contract->id, 'milestone_id' => $milestone->id]
        );
    }

    /** Freelancer learns a milestone payment was released */
    public function milestoneReleased(Milestone $milestone): void
    {
        $milestone->load(['contract.employer']);
        $contract = $milestone->contract;
        $this->send(
            $contract->freelancer_id,
            'milestone_released',
            'Payment Released 💰',
            "Payment of \${$milestone->amount} for \"{$milestone->title}\" has been released to your wallet.",
            ['contract_id' => $contract->id, 'milestone_id' => $milestone->id]
        );
    }

    /** Both parties notified when a contract is completed */
    public function contractCompleted(Contract $contract): void
    {
        $contract->load(['job', 'employer', 'freelancer']);
        $this->send(
            $contract->employer_id,
            'contract_completed',
            'Contract Completed',
            "The contract for \"{$contract->job->title}\" has been completed.",
            ['contract_id' => $contract->id]
        );
        $this->send(
            $contract->freelancer_id,
            'contract_completed',
            'Contract Completed',
            "Your contract for \"{$contract->job->title}\" has been marked as completed.",
            ['contract_id' => $contract->id]
        );
    }

    /** Notify the reviewee that they received a review */
    public function reviewReceived(Review $review): void
    {
        $review->load(['reviewer', 'contract.job']);
        $this->send(
            $review->reviewee_id,
            'review_received',
            'New Review Received ⭐',
            "{$review->reviewer->name} left you a {$review->rating}-star review for \"{$review->contract->job->title}\".",
            ['review_id' => $review->id, 'contract_id' => $review->contract_id]
        );
    }

    /** Notify freelancer their proposal was rejected */
    public function proposalRejected(Proposal $proposal): void
    {
        $proposal->load(['job']);
        $this->send(
            $proposal->freelancer_id,
            'proposal_rejected',
            'Proposal Not Selected',
            "Your proposal for \"{$proposal->job->title}\" was not selected this time.",
            ['proposal_id' => $proposal->id, 'job_id' => $proposal->job_id]
        );
    }
}
