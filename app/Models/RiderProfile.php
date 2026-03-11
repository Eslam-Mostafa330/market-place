<?php

namespace App\Models;

use App\Enums\RiderAvailability;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

class RiderProfile extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry',
        'vehicle_type',
        'vehicle_number',
        'rider_availability',
        'current_latitude',
        'current_longitude',
        'total_deliveries',
    ];

    /**
     * The attributes that should be defaulted when creating a new model instance.
     *
     * @var list<string>
     */
    protected $attributes = [
        'rider_availability' => RiderAvailability::UNAVAILABLE->value,
        'total_deliveries'   => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rider_availability' => RiderAvailability::class,
            'current_longitude'  => 'decimal:8',
            'current_latitude'   => 'decimal:8',
            'license_expiry'     => 'date',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The rider profile belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('rider_availability', RiderAvailability::AVAILABLE->value);
    }
}