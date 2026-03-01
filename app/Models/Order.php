<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends BaseModel
{
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'order_status'   => OrderStatus::class,
            'delivery_fee'   => 'decimal:2',
            'delivered_at'   => 'datetime',
            'discount'       => 'decimal:2',
            'subtotal'       => 'decimal:2',
            'total'          => 'decimal:2',
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
}