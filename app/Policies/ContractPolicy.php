<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    /**
     * Determine whether the user can view the contract.
     */
    public function view(User $user, Contract $contract): bool
    {
        return $contract->employer_id === $user->id || $contract->freelancer_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can fund a milestone on the contract.
     */
    public function fundMilestone(User $user, Contract $contract): bool
    {
        return $user->isEmployer() && $contract->employer_id === $user->id;
    }

    /**
     * Determine whether the user can request review / submit work on a milestone.
     */
    public function submitWork(User $user, Contract $contract): bool
    {
        return $user->isFreelancer() && $contract->freelancer_id === $user->id;
    }

    /**
     * Determine whether the user can release funds on a milestone.
     */
    public function releaseMilestone(User $user, Contract $contract): bool
    {
        return $user->isEmployer() && $contract->employer_id === $user->id;
    }

    /**
     * Determine whether the user can raise a dispute.
     */
    public function raiseDispute(User $user, Contract $contract): bool
    {
        return $contract->employer_id === $user->id || $contract->freelancer_id === $user->id;
    }
}
