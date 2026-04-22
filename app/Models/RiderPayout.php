<?php

namespace App\Models;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use App\Filters\PayoutFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class RiderPayout extends BaseModel
{
    use Filterable, LogsActivity;

    protected string $default_filters = PayoutFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rider_id',
        'order_id',
        'processed_by',
        'updated_by',
        'amount',
        'status',
        'payout_method',
        'reference',
        'paid_at',
        'payout_proof',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payout_method' => PayoutMethod::class,
            'paid_at'       => 'datetime',
            'amount'        => 'decimal:2',
            'status'        => PayoutStatus::class,
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /** 
     * A payout belongs to a rider
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /** 
     * The payout is processed by an admin user
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** 
     * The payout is updated by an admin user
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** 
     * A payout belongs to an order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Accessor that can access the payout proof screenshot URL
     */
    protected function payoutProofUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->payout_proof ? asset('storage/' . $this->payout_proof) : null
        );
    }

    /**** ***************** ****/
    /**** ActivityLog Usage ****/
    /**** ***************** ****/
    /**
     * Define activity log behavior for RiderPayout model.
     *
     * Logs changes only for specific attributes and only when they are modified,
     * avoiding empty log entries.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rider_id', 'status', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Customize the activity log entry before it's saved.
     *
     * This method allows to add custom properties to the activity log entry, such as the rider_id.
     */
    public function tapActivity(Activity $activity)
    {
        $activity->properties = $activity->properties->merge([
            'rider_id' => $this->rider_id,
        ]);
    }
}