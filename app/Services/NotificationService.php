<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    /**
     * Get paginated notifications for the authenticated user.
     *
     * @return \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotifications()
    {
        return $this->user()
            ->notifications()
            ->latest()
            ->dynamicPaginate();
    }

    /**
     * Mark a single notification as read by the authenticated user.
     *
     * @throws \InvalidArgumentException
     */
    public function markAsRead(DatabaseNotification $notification): DatabaseNotification
    {
        $notification = $this->user()->notifications()->findOrFail($notification->id);

        $notification->markAsRead();

        return $notification;
    }

    /**
     * Mark all unread notifications as read.
     *
     * @return int Number of notifications marked as read.
     */
    public function markAllAsRead(): int
    {
        return $this->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
    * Get the count of unread notifications for the authenticated user.
    *
    * @return int Number of notifications marked as unread.
    */
    public function getUnreadCount(): int
    {
        return $this->user()->unreadNotifications()->count();
    }

    /**
     * Get the authenticated user.
     *
     * @return \App\Models\User
     */
    private function user(): User
    {
        return auth()->user();
    }
}