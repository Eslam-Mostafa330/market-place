<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'store_id',
        'order_id',
        'rate',
        'full_review',
    ];

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/

    /**
    * The review could be given by a customer
    */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * The review could be given to an order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The review could be given to a store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}