<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class MessagePolicy
{
    /**
     * Determine whether the user can view/participate in the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can send a message in the conversation.
     */
    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->where('user_id', $user->id)->exists();
    }
}
