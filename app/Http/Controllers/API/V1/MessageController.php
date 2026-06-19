<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\StoreMessageRequest;
use App\Services\MessageService;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends ApiController
{
    public function __construct(
        protected MessageService $messageService,
        protected MessageRepositoryInterface $messageRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->messageRepository->getConversationsForUser($request->user()->id);

        return $this->successResponse(
            $conversations,
            'Conversations retrieved successfully.'
        );
    }

    public function show(int $conversationId, Request $request): JsonResponse
    {
        $conversation = $this->messageRepository->findConversation($conversationId);

        if (!$conversation) {
            return $this->errorResponse('Conversation not found.', 404);
        }

        Gate::authorize('view', $conversation);

        $this->messageService->markAsRead($conversationId, $request->user()->id);

        $messages = $this->messageRepository->getMessagesInConversation($conversationId, (int) $request->get('per_page', 30));

        return $this->successResponse(
            MessageResource::collection($messages)->resolve(),
            'Messages retrieved successfully.'
        );
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $recipientId = (int) $validated['recipient_id'];
        $senderId = $request->user()->id;

        if ($recipientId === $senderId) {
            return $this->errorResponse('You cannot start a conversation with yourself.', 400);
        }

        $conversation = $this->messageService->startConversation($senderId, $recipientId);

        return $this->successResponse(
            $conversation->load('users'),
            'Conversation started successfully.',
            201
        );
    }

    public function storeMessage(StoreMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = $this->messageRepository->findConversation($conversationId);

        if (!$conversation) {
            return $this->errorResponse('Conversation not found.', 404);
        }

        Gate::authorize('send', $conversation);

        $message = $this->messageService->sendMessage(
            $request->user()->id,
            $conversationId,
            $request->validated()['message_text']
        );

        return $this->successResponse(
            (new MessageResource($message->load('sender')))->resolve(),
            'Message sent successfully.',
            201
        );
    }

    public function markAsRead(int $conversationId, Request $request): JsonResponse
    {
        $conversation = $this->messageRepository->findConversation($conversationId);

        if (!$conversation) {
            return $this->errorResponse('Conversation not found.', 404);
        }

        Gate::authorize('view', $conversation);

        $this->messageService->markAsRead($conversationId, $request->user()->id);

        return $this->successResponse(null, 'Messages marked as read.');
    }
}
