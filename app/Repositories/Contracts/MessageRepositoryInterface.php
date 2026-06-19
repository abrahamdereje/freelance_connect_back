<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\Message;

interface MessageRepositoryInterface
{
    public function findConversation(int $id): ?Conversation;
    public function findOrCreateConversation(int $user1Id, int $user2Id): Conversation;
    public function getConversationsForUser(int $userId);
    public function getMessagesInConversation(int $conversationId, int $perPage = 30);
    public function createMessage(array $data): Message;
    public function markAsRead(int $conversationId, int $userId): bool;
}
