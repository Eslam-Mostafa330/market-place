<?php

namespace App\Jobs\Order;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Order\AdminOrderEscalationNotification;
use App\Notifications\Order\RiderAssignedNotification;
use App\Services\Rider\RiderLocationService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\DB;

class FindRiderJob implements ShouldQueue, ShouldBeUnique
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
    public function __construct(public readonly string $orderId)
    {
        $this->onQueue('rider-matching');
    }

    /**
     * Unique key per order — ensures only one FindRiderJob exists in the queue per order at any time.
     */
    public function uniqueId(): string
    {
        return $this->orderId;
    }

    /**
     * Dispatch a new rider search, replacing any active search.
     *
     * Releases the existing unique lock so the new search is queued.
     */
    public static function dispatchFor(string $orderId): void
    {
        $job = new self($orderId);

        app(UniqueLock::class)->release($job);

        self::dispatch($orderId);
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
        $order = Order::query()
            ->select('id', 'order_status', 'store_branch_id', 'rider_assignment_attempts')
            ->with('storeBranch:id,latitude,longitude,slug')
            ->find($this->orderId);

        if (! $order || $order->order_status !== OrderStatus::WAITING_RIDER) {
            return;
        }

        $branch = $order->storeBranch;

        $nearestRider = $riderService->findNearestRider(
            (float) $branch->latitude,
            (float) $branch->longitude
        );

        if ($nearestRider) {
            $this->assignRider($order, $nearestRider);

            return;
        }

        $order->increment('rider_assignment_attempts');
        $order->refresh();

        if ($order->rider_assignment_attempts >= $this->tries) {
            $this->escalateToAdmin($order);
            return;
        }

        $this->release($this->backoff);
    }

    /**
     * Assign a rider if the order is still waiting.
     *
     * Re-checks the status under a row lock to avoid overwriting
     * another assignment made concurrently.
     */
    private function assignRider(Order $order, array $riderData): bool
    {
        $assigned = DB::transaction(function () use ($order, $riderData) {
            $fresh = Order::query()
                ->select('id', 'order_status')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $fresh || $fresh->order_status !== OrderStatus::WAITING_RIDER) {
                return false;
            }

            $fresh->update([
                'rider_id'     => $riderData['user_id'],
                'order_status' => OrderStatus::RIDER_ASSIGNED,
            ]);

            return true;
        });

        if (! $assigned) {
            return false;
        }

        $branchSlug = $order->storeBranch->slug;

        User::query()->select('id')->find($riderData['user_id'])?->notify(new RiderAssignedNotification($order->id, $branchSlug));

        return true;
    }

    /**
     * No rider found after all attempts then escalate to admin.
     *
     * Order not canceled automatically, instead admins are notified to intervene manually (assign a rider, extend search, or cancel).
     */
    private function escalateToAdmin(Order $order): void
    {
        User::where('role', UserRole::ADMIN)
            ->select('id')
            ->get()
            ->each(fn ($admin) => $admin->notify(
                new AdminOrderEscalationNotification($order->id)
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
        $order = Order::find($this->orderId);

        if (! $order || $order->order_status !== OrderStatus::WAITING_RIDER) {
            return;
        }

        $this->escalateToAdmin($order);
    }
}