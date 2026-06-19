<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /**
     * List all notifications for the authenticated user (newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return $this->successResponse(
            NotificationResource::collection($notifications),
            'Notifications retrieved successfully.'
        );
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(int $id, Request $request): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)->find($id);

        if (!$notification) {
            return $this->errorResponse('Notification not found.', 404);
        }

        $notification->update(['read_at' => now()]);

        return $this->successResponse(
            new NotificationResource($notification),
            'Notification marked as read.'
        );
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read.');
    }

    /**
     * Return the unread count for badge display.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->successResponse(['count' => $count], 'Unread count retrieved.');
    }
}
