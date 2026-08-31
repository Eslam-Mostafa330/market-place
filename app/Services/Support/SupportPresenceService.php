<?php

namespace App\Services\Support;

use App\Enums\AgentAvailability;
use App\Events\Support\SupportDeskAvailabilityChanged;
use App\Models\SupportAgentStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SupportPresenceService
{
    private const STAFFED_CACHE_KEY = 'support.desk_staffed';

    /**
     * Record that an agent is at their console, and how they are set.
     */
    public function heartbeat(User $agent, AgentAvailability $availability): SupportAgentStatus
    {
        $status = SupportAgentStatus::updateOrCreate(
            ['user_id' => $agent->id],
            [
                'availability' => $availability,
                'last_seen_at' => now(),
            ],
        );

        $this->availability();

        return $status;
    }

    /**
     * Keep online agents from going stale while they work.
     */
    public function touch(User $agent): void
    {
        $refreshed = SupportAgentStatus::where('user_id', $agent->id)
            ->where('availability', AgentAvailability::ONLINE)
            ->where('last_seen_at', '<', now()->subMinutes(config('support.heartbeat_write_every_minutes')))
            ->update(['last_seen_at' => now()]);

        if ($refreshed) {
            $this->availability();
        }
    }

    /**
     * The agent's own presence, offline until they first say otherwise.
     */
    public function statusFor(User $agent): SupportAgentStatus
    {
        return SupportAgentStatus::firstOrNew(
            ['user_id' => $agent->id],
            ['availability' => AgentAvailability::default()],
        );
    }

    /**
     * Whether anyone is actually on the desk right now.
     */
    public function deskIsStaffed(): bool
    {
        return SupportAgentStatus::query()->present()->exists();
    }

    /**
     * Return the desk availability and broadcast changes.
     *
     * @return array<string, mixed>
     */
    public function availability(): array
    {
        $staffed = $this->deskIsStaffed();

        $snapshot = [
            'support_available' => $staffed,
            'message'           => $staffed ? __('support.desk_staffed') : __('support.desk_unstaffed'),
        ];

        if (Cache::get(self::STAFFED_CACHE_KEY) !== $staffed) {
            Cache::forever(self::STAFFED_CACHE_KEY, $staffed);

            SupportDeskAvailabilityChanged::dispatch($snapshot);
        }

        return $snapshot;
    }
}
