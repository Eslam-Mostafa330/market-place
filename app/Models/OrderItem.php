<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal'   => 'decimal:2',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The order item belongs to an order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The order item belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}