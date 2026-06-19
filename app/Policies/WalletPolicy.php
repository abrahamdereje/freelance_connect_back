<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the user can view the wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $wallet->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can deposit into the wallet.
     */
    public function deposit(User $user, Wallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }
}
