<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\General\Notification\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BaseApiController
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(): AnonymousResourceCollection
    {
        $data = $this->notificationService->getNotifications();
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return NotificationResource::collection($data)
            ->additional([
                'meta' => ['unread_count' => $unreadCount]
            ]);
    }

    /**
     * Mark a single notification as read that related to the authenticated rider.
     */
    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        $updatedNotification = $this->notificationService->markAsRead($notification);

        return $this->apiResponseUpdated(new NotificationResource($updatedNotification));
    }

    /**
     * Mark all notifications as read that related to the authenticated rider.
     */
    public function markAllAsRead(): JsonResponse
    {
        $readCount = $this->notificationService->markAllAsRead();

        return $this->apiResponseUpdated(['read_count' => $readCount]);
    }

    /**
    * Get the count of unread notifications that related to the authenticated rider.
    */
    public function unreadNotificationsCount(): JsonResponse
    {
        $data = $this->notificationService->getUnreadCount();

        return $this->apiResponseShow(['unread_count' => $data]);
    }
}