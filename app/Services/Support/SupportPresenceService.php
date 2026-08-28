<?php

namespace App\Services\Support;

use App\Enums\AgentAvailability;
use App\Models\SupportAgentStatus;
use App\Models\User;

class SupportPresenceService
{
    /**
     * Record that an agent is at their console, and how they are set.
     */
    public function heartbeat(User $agent, AgentAvailability $availability): SupportAgentStatus
    {
        return SupportAgentStatus::updateOrCreate(
            ['user_id' => $agent->id],
            [
                'availability' => $availability,
                'last_seen_at' => now(),
            ],
        );
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
        return SupportAgentStatus::query()
            ->where('availability', AgentAvailability::ONLINE)
            ->where('last_seen_at', '>=', now()->subSeconds(config('support.agent_presence_ttl_seconds')))
            ->exists();
    }

    /**
     * What a customer is allowed to know about the desk.
     */
    public function customerSnapshot(): array
    {
        $staffed = $this->deskIsStaffed();

        return [
            'support_available' => $staffed,
            'message'           => $staffed ? __('support.desk_staffed') : __('support.desk_unstaffed'),
        ];
    }
}
