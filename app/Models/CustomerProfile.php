<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'preferences',
        'wallet_balance',
        'loyalty_points',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wallet_balance' => 'decimal:2',
            'date_of_birth'  => 'date',
            'preferences'    => 'array',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The customer profile belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}