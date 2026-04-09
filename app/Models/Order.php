<?php

namespace App\Models;

use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filters\OrderFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends BaseModel
{
    use Filterable;
    
    protected string $default_filters = OrderFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'store_id',
        'store_branch_id',
        'rider_id',
        'coupon_id',
        'order_number',
        'delivery_fee',
        'discount',
        'subtotal',
        'total',
        'notes',
        'payment_method',
        'order_status',
        'payment_status',
        'delivered_at',
        'commission_rate',
        'commission_amount',
        'vendor_earnings',
        'rider_earnings',
        'delivery_address_line',
        'delivery_city',
        'delivery_state',
        'delivery_country',
        'delivery_postal_code',
        'delivery_notes',
        'delivery_phone',
        'delivery_latitude',
        'delivery_longitude',
        'rider_assignment_attempts',
        'rider_search_started_at',
        'cancelled_by',
        'cancellation_reason',
        'cancellation_note',
        'payment_intent_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rider_search_started_at' => 'datetime',
            'cancellation_reason'     => CancellationReason::class,
            'delivery_longitude'      => 'decimal:8',
            'delivery_latitude'       => 'decimal:8',
            'commission_amount'       => 'decimal:2',
            'commission_rate'         => 'decimal:2',
            'vendor_earnings'         => 'decimal:2',
            'payment_method'          => PaymentMethod::class,
            'rider_earnings'          => 'decimal:2',
            'payment_status'          => PaymentStatus::class,
            'order_status'            => OrderStatus::class,
            'delivery_fee'            => 'decimal:2',
            'delivered_at'            => 'datetime',
            'discount'                => 'decimal:2',
            'subtotal'                => 'decimal:2',
            'total'                   => 'decimal:2',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The order can be placed by a customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * The order is fulfilled by a store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The order is fulfilled by a store branch
     */
    public function storeBranch(): BelongsTo
    {
        return $this->belongsTo(StoreBranch::class);
    }

    /**
     * The order can be delivered by a rider
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * The order can have a coupon applied
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * The order can have many items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}