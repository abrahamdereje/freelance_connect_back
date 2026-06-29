<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Events\MessageSent;
use App\Events\MessagesRead;

class MessageService
{
    public function __construct(
        protected MessageRepositoryInterface $messageRepository
    ) {}

    public function startConversation(int $user1Id, int $user2Id): Conversation
    {
        return $this->messageRepository->findOrCreateConversation($user1Id, $user2Id);
    }

    public function sendMessage(int $senderId, int $conversationId, string $messageText): Message
    {
        $message = $this->messageRepository->createMessage([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'message_text' => $messageText,
        ]);

        $message->load('sender');

        try {
            // Broadcast to ALL participants (including sender for real-time display)
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            \Log::warning("Broadcasting message sent failed: " . $e->getMessage());
        }

        return $message;
    }

    public function markAsRead(int $conversationId, int $userId): bool
    {
        $updated = $this->messageRepository->markAsRead($conversationId, $userId);

        if ($updated) {
            try {
                // Notify the sender that their messages have been read
                broadcast(new MessagesRead(
                    $conversationId,
                    $userId,
                    now()->toIso8601String()
                ));
            } catch (\Exception $e) {
                \Log::warning("Broadcasting message read failed: " . $e->getMessage());
            }
        }

        return $updated;
    }
}
