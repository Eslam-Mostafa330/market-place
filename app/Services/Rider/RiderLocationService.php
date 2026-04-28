<?php

namespace App\Services\Rider;

use App\Enums\RiderAvailability;
use App\Models\RiderProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class RiderLocationService
{
    const LOCATION_KEY  = 'rider:location:%s';
    const DB_SYNC_KEY   = 'rider:last_db_sync:%s';
    const LOCATION_TTL  = 300;
    const DB_SYNC_EVERY = 30;

    /**
     * Returns the default Redis connection used across all operations in this service.
     *
     * Centralizing the connection ensures consistent prefix handling,
     * since Laravel automatically prepends the configured Redis prefix
     * to all keys when using this connection.
     */
    private function redis()
    {
        return Redis::connection('default');
    }

    /**
     * Builds a namespaced Redis key for a given rider profile ID.
     *
     * Using a consistent key format (e.g. "rider:location:{id}")
     * keeps all rider keys grouped and easy to scan or delete by pattern.
     *
     * @param string $pattern    One of the LOCATION_KEY or DB_SYNC_KEY constants
     * @param string $profileId  The rider's profile UUID
     *
     * @return string  The fully formatted Redis key
     */
    private function key(string $pattern, string $profileId): string
    {
        return sprintf($pattern, $profileId);
    }

    /**
     * Strips the Laravel Redis prefix from a key returned by keys().
     *
     * The keys() command returns keys with the configured prefix already included
     * (e.g. "marketplace-database-rider:location:abc"), but commands like hgetall()
     * and get() add the prefix themselves. Passing a pre-prefixed key to those
     * commands would double the prefix and cause a key-not-found result.
     * This method removes the prefix so the key can be passed safely.
     *
     * @param string $key  The raw key returned by Redis keys()
     *
     * @return string  The key with the Laravel prefix stripped
     */
    private function stripPrefix(string $key): string
    {
        $prefix = config('database.redis.options.prefix', '');

        return ($prefix && str_starts_with($key, $prefix))
            ? substr($key, strlen($prefix))
            : $key;
    }

    /**
     * Calculates the straight-line surface distance in kilometers between two GPS coordinates.
     *
     * Uses the Haversine formula, which accounts for Earth's curvature by computing
     * the arc distance along the surface rather than a flat straight line.
     * This is accurate enough for delivery radius checks (error < 0.5% at city scale).
     *
     * @param float $lat1  Latitude of the first point
     * @param float $lng1  Longitude of the first point
     * @param float $lat2  Latitude of the second point
     * @param float $lng2  Longitude of the second point
     *
     * @return float  Distance in kilometers
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius        = 6371;
        $latDelta           = deg2rad($lat2 - $lat1);
        $lngDelta           = deg2rad($lng2 - $lng1);
        $haversineOfAngle   = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        $centralAngle       = 2 * atan2(sqrt($haversineOfAngle), sqrt(1 - $haversineOfAngle));

        return $earthRadius * $centralAngle;
    }


    /**
     * Determines whether enough time has passed since the last MySQL sync to warrant a new write.
     *
     * Reads the sync timestamp stored in Redis after the previous MySQL write.
     * If the key is missing (first update ever, or TTL expired) or the elapsed
     * time since the last sync meets or exceeds DB_SYNC_EVERY seconds, returns true.
     *
     * @param string $syncKey  The Redis key holding the last sync timestamp
     *
     * @return bool  True if a MySQL write should be performed, false to skip
     */
    private function shouldSyncToDatabase(string $syncKey): bool
    {
        $lastSyncedAt = $this->redis()->get($syncKey);

        return empty($lastSyncedAt)
            || Carbon::parse($lastSyncedAt)->diffInSeconds(Carbon::now()) >= self::DB_SYNC_EVERY;
    }

    /**
     * Writes the rider's current GPS coordinates to Redis immediately,
     * and syncs to MySQL at most once every 30 seconds.
     *
     * Every incoming location update is stored in Redis instantly (sub-millisecond).
     * MySQL is only written to when DB_SYNC_EVERY seconds have elapsed since the
     * last sync, which drastically reduces DB write load at scale — for example,
     * 1000 riders updating every 10 seconds produces 100 writes/sec to Redis
     * but only ~33 writes/sec to MySQL instead of 100.
     *
     * @param RiderProfile $profile  The authenticated rider's profile model
     * @param float        $lat      Current latitude (-90 to 90)
     * @param float        $lng      Current longitude (-180 to 180)
     */
    public function updateRiderLocation(RiderProfile $profile, float $lat, float $lng): Carbon
    {
        $now         = now();
        $locationKey = $this->key(self::LOCATION_KEY, $profile->id);
        $syncKey     = $this->key(self::DB_SYNC_KEY, $profile->id);

        $this->redis()->hmset($locationKey, [
            'latitude'   => (string) $lat,
            'longitude'  => (string) $lng,
            'updated_at' => $now->toISOString(),
            'rider_id'   => (string) $profile->id,
            'user_id'    => (string) $profile->user_id,
            'available'  => (string) $profile->rider_availability->value,
        ]);

        $this->redis()->expire($locationKey, self::LOCATION_TTL);

        if ($this->shouldSyncToDatabase($syncKey)) {
            $profile->update([
                'current_latitude'         => $lat,
                'current_longitude'        => $lng,
                'last_location_updated_at' => $now,
            ]);

            $this->redis()->setex($syncKey, self::LOCATION_TTL, $now->toISOString());
        }

        return $now;
    }

    /**
     * Finds the nearest available rider to a given store location within a maximum radius.
     *
     * Reads all active rider location keys from Redis (only riders who have updated
     * their location within the last 5 minutes exist in Redis), filters to available
     * riders only, calculates the Haversine distance from the store to each rider,
     * and returns the closest one within the specified radius.
     *
     * Returns null if no available riders are found within the radius.
     *
     * @param float $storeLat  Latitude of the store placing the order
     * @param float $storeLng  Longitude of the store placing the order
     * @param float $maxKm     Maximum search radius in kilometers (default 10km)
     *
     * @return array|null  Rider location data with a distance_km field, or null if none found
     */
    public function findNearestRider(float $storeLat, float $storeLng, float $maxKm = 10): ?array
    {
        $keys = $this->redis()->keys($this->key(self::LOCATION_KEY, '*'));

        if (empty($keys)) {
            return null;
        }

        $nearestRider     = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($keys as $key) {
            $rider = $this->redis()->hgetall($this->stripPrefix($key));

            if (empty($rider['latitude']) || empty($rider['longitude']) || (int) ($rider['available'] ?? 0) !== RiderAvailability::AVAILABLE->value) {
                continue;
            }

            $distanceToRider = $this->haversineDistance(
                $storeLat, $storeLng,
                (float) $rider['latitude'],
                (float) $rider['longitude']
            );

            if ($distanceToRider <= $maxKm && $distanceToRider < $shortestDistance) {
                $shortestDistance = $distanceToRider;
                $nearestRider     = array_merge($rider, ['distance_km' => round($distanceToRider, 2)]);
            }
        }

        return $nearestRider;
    }

    /**
     * Retrieves a specific rider's current location, preferring Redis over MySQL.
     *
     * Attempts to read from Redis first for maximum speed. If the key has expired
     * (rider has been inactive for more than 5 minutes) or Redis is unavailable,
     * falls back to reading the last known location from MySQL.
     *
     * @param string $riderProfileId  The rider's profile UUID
     *
     * @return array|null  Location data array, or null if no location is recorded anywhere
     */
    public function getRiderLocation(string $riderProfileId): ?array
    {
        $redisData = $this->redis()->hgetall($this->key(self::LOCATION_KEY, $riderProfileId));

        if (! empty($redisData['latitude'])) {
            return $redisData;
        }

        $profile = RiderProfile::find($riderProfileId);

        if (! $profile?->current_latitude) {
            return null;
        }

        return [
            'latitude'   => $profile->current_latitude,
            'longitude'  => $profile->current_longitude,
            'updated_at' => $profile->last_location_updated_at,
            'available'  => $profile->rider_availability->value,
            'source'     => 'database',
        ];
    }

    /**
     * Removes all Redis keys associated with a rider's location tracking.
     *
     * Deletes both the location key and the DB sync timer key. This is called
     * when a rider goes unavailable or logs out, ensuring they no longer appear
     * in findNearestRider() results and their sync timer is reset cleanly
     * for when they come back online.
     *
     * @param string $riderProfileId  The rider's profile UUID
     */
    public function removeRiderLocation(string $riderProfileId): void
    {
        $this->redis()->del($this->key(self::LOCATION_KEY, $riderProfileId));
        $this->redis()->del($this->key(self::DB_SYNC_KEY, $riderProfileId));
    }
}