<?php

namespace App\Jobs\CustomerPreference;

use App\Models\CustomerProfile;
use App\Services\CustomerPreferencesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Illuminate\Support\Facades\Log;

class RefreshCustomerPreferences implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum attempts before the job is marked as permanently failed.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between each retry attempt.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    private const THROTTLE_TTL = [
        'view'     => 10,
        'favorite' => 30,
        'order'    => 40,
    ];

    public function __construct(public string $customerId, public string $type)
    {
        $this->onQueue('refresh-user-preference');
    }

    /**
     * Prevents the job from being queued multiple times for the same
     * customer and type within a defined throttle window. If the lock
     * is already held (dispatched recently), the dispatch is silently skipped.
     */
    public static function throttledDispatch(string $customerId, string $type): void
    {
        $lockKey = "preferences_{$customerId}_{$type}";
        $ttl     = self::THROTTLE_TTL[$type] ?? 60;

        if (! Cache::add($lockKey, true, $ttl)) {
            return;
        }

        static::dispatch($customerId, $type)->delay(10);
    }

    public function handle(): void
    {
        $profile = CustomerProfile::where('user_id', $this->customerId)->first();

        if (! $profile) return;

        $service = app(CustomerPreferencesService::class);

        match ($this->type) {
            'view'     => $service->refreshRecentlyViewed($profile),
            'favorite' => $service->refreshFavorited($profile),
            'order'    => $service->refreshAfterOrder($profile),
        };
    }

    /**
     * Called when the job has exhausted all retry attempts.
     */
    public function failed(Throwable $exception): void
    {
        Log::warning('RefreshCustomerPreferences job permanently failed', [
            'customer_id' => $this->customerId,
            'type'        => $this->type,
            'error'       => $exception->getMessage(),
        ]);
    }
}