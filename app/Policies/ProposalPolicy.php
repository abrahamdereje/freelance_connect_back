<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Proposal $proposal): bool
    {
        // Owner of the proposal or the employer who posted the job
        return $proposal->freelancer_id === $user->id || 
            ($proposal->job && $proposal->job->employer_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isFreelancer();
    }

    /**
     * Determine whether the user can update the model (e.g. withdraw).
     */
    public function update(User $user, Proposal $proposal): bool
    {
        return $proposal->freelancer_id === $user->id;
    }

    /**
     * Determine whether the employer can accept/reject the proposal.
     */
    public function acceptReject(User $user, Proposal $proposal): bool
    {
        return $user->isEmployer() && $proposal->job && $proposal->job->employer_id === $user->id;
    }
}
