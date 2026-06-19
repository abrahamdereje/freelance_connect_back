<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversations.{id}', function ($user, $id) {
    return \App\Models\Conversation::where('id', $id)
        ->whereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->exists();
});

// Each user may only listen on their own notification channel
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
