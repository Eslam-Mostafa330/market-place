<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'sender_id',
        'body',
        'read_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Whoever wrote the message, customer or agent.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    /**
     * Messages the given user has not read, meaning the ones they did not write.
     */
    #[Scope]
    protected function unreadFor(Builder $query, string $userId): void
    {
        $query->whereNull('read_at')->where('sender_id', '!=', $userId);
    }

    /**
     * The conversation newest first, carrying only what a chat bubble needs.
     *
     * Two messages can land in the same second, so the id breaks the tie. A
     * cursor walking on time alone would repeat or skip one of the pair.
     */
    #[Scope]
    protected function conversation(Builder $query): void
    {
        $query->select('id', 'sender_id', 'body', 'read_at', 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
