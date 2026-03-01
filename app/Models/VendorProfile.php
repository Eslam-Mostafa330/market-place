<?php

namespace App\Models;

use App\Enums\VendorVerificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

class VendorProfile extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_license',
        'business_description',
        'business_phone',
        'business_email',
        'rating',
        'total_orders',
        'verification_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => VendorVerificationStatus::class,
            'rating'              => 'decimal:2',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The vendor profile belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->where('verification_status', VendorVerificationStatus::VERIFIED->value);
    }
}