<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'product_id',
    ];
}