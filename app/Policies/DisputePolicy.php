<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    /**
     * Determine whether the user can view the dispute.
     */
    public function view(User $user, Dispute $dispute): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $contract = $dispute->contract;
        return $dispute->raiser_id === $user->id || 
            ($contract && ($contract->employer_id === $user->id || $contract->freelancer_id === $user->id));
    }

    /**
     * Determine whether the user can resolve the dispute.
     */
    public function resolve(User $user): bool
    {
        return $user->isAdmin();
    }
}
