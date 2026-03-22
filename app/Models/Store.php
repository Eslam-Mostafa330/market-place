<?php

namespace App\Models;

use App\Filters\StoreFilters;
use App\Traits\HasSlug;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Store extends BaseModel
{
    use HasSlug, Filterable, HasFactory;

    protected string $default_filters = StoreFilters::class;

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
        'image',
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
     * Get active branches for the store.
     * Returns only active branches (using local scope `active`)
     */
    public function activeBranches(): HasMany
    {
        return $this->hasMany(StoreBranch::class)
            ->select('id', 'store_id', 'name', 'city', 'area')
            ->active()
            ->latest();
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

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    /**
     * To display the related stores for the authenticated vendor
     */
    #[Scope]
    protected function forAuthVendor(Builder $query): void
    {
        $query->where('vendor_profile_id', auth()->user()->vendorProfile->id);
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Accessor that can access the store image
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image ? asset('storage/' . $this->image) : null
        );
    }

    /**
     * Accessor that can access the store logo
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo ? asset('storage/' . $this->logo) : null
        );
    }
}