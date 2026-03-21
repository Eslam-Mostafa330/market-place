<?php

namespace App\Models;

use App\Enums\BooleanStatus;
use App\Enums\DefineStatus;
use App\Filters\ProductFilters;
use App\Traits\HasSlug;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends BaseModel
{
    use HasSlug, Filterable, HasFactory;

    protected string $default_filters = ProductFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'product_category_id',
        'name',
        'slug',
        'image',
        'description',
        'price',
        'sale_price',
        'quantity',
        'preparation_time',
        'is_featured',
        'status',
    ];

    /**
     * The attributes that should be defaulted when creating a new model instance.
     *
     * @var list<string>
     */
    protected $attributes = [
        'preparation_time' => 0,
        'is_featured'      => BooleanStatus::NO,
        'status'           => DefineStatus::ACTIVE,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured'  => BooleanStatus::class,
            'sale_price'   => 'decimal:2',
            'price'        => 'decimal:2',
            'status'       => DefineStatus::class,
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The product is belongs to a category
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * The product is belongs to a store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The product can be in many order items
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The product can be favored by many customers
     */
    public function favoredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'product_id', 'customer_id')
            ->withTimestamps();
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Accessor that can access the article image
     */
    public function getImageUrlAttribute(): ?string 
    {
        if (! $this->image) return null;

        return asset('storage/' . $this->image);
    }
}