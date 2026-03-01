<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_profile_id',
        'business_category_id',
        'name',
        'slug',
        'description',
        'logo',
        'cover_image',
    ];

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The store belongs to a vendor profile
     */
    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    /**
     * The store belongs to a business category
     */
    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    /**
     * The store can has many branches
     */
    public function branches(): HasMany
    {
        return $this->hasMany(StoreBranch::class);
    }

    /**
     * The store can has many products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * The store can create many orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The store can receive many reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Accessor that can access the store image
     */
    public function getImageUrlAttribute(): ?string 
    {
        if (! $this->image) return null;

        return asset('storage/' . $this->image);
    }

    /**
     * Accessor that can access the store logo
     */
    public function getLogoUrlAttribute(): ?string 
    {
        if (! $this->logo) return null;

        return asset('storage/' . $this->logo);
    }
}