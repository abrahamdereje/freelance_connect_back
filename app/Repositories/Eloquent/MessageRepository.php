<?php

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;

class MessageRepository implements MessageRepositoryInterface
{
    public function findConversation(int $id): ?Conversation
    {
        return Conversation::with('users')->find($id);
    }

    public function findOrCreateConversation(int $user1Id, int $user2Id): Conversation
    {
        // Try to find a conversation that has both users
        $conversation = Conversation::whereHas('users', function ($q) use ($user1Id) {
            $q->where('users.id', $user1Id);
        })->whereHas('users', function ($q) use ($user2Id) {
            $q->where('users.id', $user2Id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create();
            $conversation->users()->attach([$user1Id, $user2Id]);
        }

        return $conversation;
    }

    public function getConversationsForUser(int $userId)
    {
        return Conversation::whereHas('users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        })
        ->with(['users', 'lastMessage'])
        ->latest('updated_at')
        ->get();
    }

    public function getMessagesInConversation(int $conversationId, int $perPage = 30)
    {
        return Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->oldest()
            ->get();
    }

    public function createMessage(array $data): Message
    {
        return Message::create($data);
    }

    public function markAsRead(int $conversationId, int $userId): bool
    {
        return Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]) > 0;
    }
}
