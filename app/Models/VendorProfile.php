<?php

namespace App\Models;

use App\Enums\VendorVerificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'rejection_reason',
    ];

    /**
     * The attributes that should be defaulted when creating a new model instance.
     *
     * @var list<string>
     */
    protected $attributes = [
        'rating'       => 0,
        'total_orders' => 0,
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

    /**
     * Get the store associated with this vendor profile.
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'vendor_profile_id');
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->where('verification_status', VendorVerificationStatus::VERIFIED->value);
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Get the vendor's preferred display name.
     * 
     * Returns the business_name if available, 
     * otherwise falls back to the user's personal name.
     */
    public function getVendorNameAttribute(): ?string
    {
        return $this->business_name ?: $this->user?->name;
    }
}