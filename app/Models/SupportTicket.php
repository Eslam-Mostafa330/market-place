<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Filters\SupportTicketFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends BaseModel
{
    use Filterable;

    protected string $default_filters = SupportTicketFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'requester_id',
        'agent_id',
        'order_id',
        'subject',
        'category',
        'status',
        'awaiting_customer',
        'last_message_at',
        'first_replied_at',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category'          => TicketCategory::class,
            'status'            => TicketStatus::class,
            'awaiting_customer' => 'boolean',
            'last_message_at'   => 'datetime',
            'first_replied_at'  => 'datetime',
            'closed_at'         => 'datetime',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The customer who opened the ticket.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * The support agent handling the ticket, once one has claimed it.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * The order the ticket is about, when it is about one.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The ticket conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    /**
     * Tickets still being worked on, from either side's point of view.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::OPEN, TicketStatus::ASSIGNED]);
    }

    /**** ***************** ****/
    /**** Business Methods  ****/
    /**** ***************** ****/
    /**
     * A closed ticket is a finished record: it takes no further messages.
     */
    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * Determine whether the given user may see this ticket.
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->requester_id === $user->id || $user->staffsSupportDesk();
    }

    /**
     * Determine whether the given user is handling this ticket.
     */
    public function isHandledBy(User $user): bool
    {
        return $this->agent_id !== null && $this->agent_id === $user->id;
    }
}
