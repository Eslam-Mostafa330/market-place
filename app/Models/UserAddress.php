<?php

namespace App\Models;

use App\Enums\AddressType;
use App\Enums\BooleanStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

class UserAddress extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'country',
        'city',
        'state',
        'postal_code',
        'address_line_1',
        'address_line_2',
        'additional_info',
        'latitude',
        'longitude',
        'is_default',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => BooleanStatus::class,
            'longitude'  => 'decimal:8',
            'latitude'   => 'decimal:8',
            'type'       => AddressType::class,
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The customer that owns this address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function default(Builder $query): void
    {
        $query->where('is_default', BooleanStatus::YES->value);
    }
}