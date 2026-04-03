<?php

namespace App\Jobs\Order;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Order\AdminOrderEscalationNotification;
use App\Notifications\Order\RiderAssignedNotification;
use App\Services\RiderLocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FindRiderJob implements ShouldQueue
{
    use Queueable;

    /**
     * 5 minutes of searching — 10 attempts × 30 seconds backoff.
     */
    public int $tries = 10;

    /**
     * Seconds to wait before retrying after no rider is found.
     */
    public int $backoff = 30;

    /**
     * How long the unique lock is held in seconds.
     * Matches the total search window (5 minutes).
     */
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Order $order)
    {
        //
    }

    /**
     * Unique key per order — ensures only one FindRiderJob exists in the queue per order at any time.
     */
    public function uniqueId(): string
    {
        return $this->order->id;
    }

    /**
     * Execute the job.
     * 
     * Try to find and assign the nearest available rider.
     *
     * Called by the queue worker each attempt.
     * Re-fetches the order fresh each time in case status changed
     * Stop if order is no longer waiting
     */
    public function handle(RiderLocationService $riderService): void
    {
        $order = $this->order->fresh();

        if ($order->order_status !== OrderStatus::WAITING_RIDER) {
            return;
        }

        $branch = $order->storeBranch;
        $nearestRider = $riderService->findNearestRider((float) $branch->latitude, (float) $branch->longitude);

        if ($nearestRider) {
            $this->assignRider($order, $nearestRider);
            return;
        }

        $order->increment('rider_assignment_attempts');

        if ($order->rider_assignment_attempts >= $this->tries) {
            $this->escalateToAdmin($order);
            return;
        }

        $this->release($this->backoff);
    }

    /**
     * Assign the found rider to the order and notify them.
     *
     * Updates order status to RIDER_ASSIGNED and sends a notification to the rider's account.
     */
    private function assignRider(Order $order, array $riderData): void
    {
        $order->update([
            'rider_id'     => $riderData['user_id'],
            'order_status' => OrderStatus::RIDER_ASSIGNED,
        ]);

        User::find($riderData['user_id']) ?->notify(new RiderAssignedNotification($order));
    }

    /**
     * No rider found after all attempts — escalate to admin.
     *
     * Order not canceled automatically, instead admins are notified to intervene manually (assign a rider, extend search, or cancel).
     */
    private function escalateToAdmin(Order $order): void
    {
        User::where('role', UserRole::ADMIN)
            ->get()
            ->each(fn ($admin) => $admin->notify(
                new AdminOrderEscalationNotification($order)
            ));
    }

    /**
     * A method called automatically when the job fails after exhausting all retry attempts.
     *
     * In normal cases the escalateToAdmin() should handle no rider found cases,
     * but This method only runs if something unexpected keeps failing the job repeatedly.
     * It ensures the order doesn't stay stuck in WAITING_RIDER status forever.
     */
    public function failed(): void
    {
        $order = $this->order->fresh();

        if ($order?->order_status !== OrderStatus::WAITING_RIDER) {
            return;
        }

        $this->escalateToAdmin($order);
    }
}