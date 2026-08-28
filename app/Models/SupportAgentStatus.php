<?php

namespace App\Models;

use App\Enums\AgentAvailability;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAgentStatus extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'availability',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability' => AgentAvailability::class,
            'last_seen_at' => 'datetime',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The agent this presence row belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
