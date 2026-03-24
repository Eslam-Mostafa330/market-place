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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    /**
     * To display the active products only
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', DefineStatus::ACTIVE->value);
    }

    /**
     * Appends an `is_favorite` boolean to each product row indicating
     * whether the given user has favourited it.
     */
    // #[Scope]
    // protected function withFavoriteStatus(Builder $query, mixed $user): void
    // {
    //     if ($user) {
    //         $query->withExists([
    //             'favoredBy as is_favorite' => fn ($q) => $q->where('favorites.customer_id', $user->id),
    //         ]);
    //     }
    // }

    #[Scope]
    protected function withFavoriteStatus(Builder $query, mixed $user): void
    {
        if ($user) {
            $query->addSelect([
                'is_favorite' => DB::table('favorites')
                    ->whereColumn('favorites.product_id', 'products.id')
                    ->where('favorites.customer_id', $user->id)
                    ->selectRaw('exists (select 1)')
            ]);
        }
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Accessor that can access the product image
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image ? asset('storage/' . $this->image) : null
        );
    }

    /****************************/
    /****** Helper Methods ******/
    /****************************/
    /**
     * Load the `is_favorite` attribute for this product.
     *
     * Sets the attribute to true if the given customer has favorited this product,
     * or false if no customer is provided (e.g. guest or non-customer account).
     *
     * @param  \App\Models\User|null  $customer
     * @return static
     */
    public function loadFavoriteStatus(?User $customer): static
    {
        $this->setAttribute('is_favorite', $customer && DB::table('favorites')
            ->where('product_id', $this->id)
            ->where('customer_id', $customer->id)
            ->exists()
        );

        return $this;
    }
}