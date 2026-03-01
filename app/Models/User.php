<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends BaseAuthenticatableModel
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => DefineStatus::class,
            'role'              => UserRole::class,
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The customer can have one profile
     */
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    /**
     * The rider can have one profile
     */
    public function riderProfile(): HasOne
    {
        return $this->hasOne(RiderProfile::class);
    }

    /**
     * The vendor can have one profile
     */
    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    /**
     * The customer can have many addresses
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * The vendor user can have many vendors/stores
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'vendor_id');
    }

    /**
     * The customer can place many orders
     */
    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * The rider can deliver many orders
     */
    public function riderOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'rider_id');
    }

    /**
     * The customer can review many orders
     */
    public function customerReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /**
     * The customer may add multiple products to wishlist
     */
    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites', 'customer_id', 'product_id')
            ->withTimestamps();
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function admin(Builder $query): void
    {
        $query->where('role', UserRole::ADMIN->value);
    }

    #[Scope]
    protected function vendor(Builder $query): void
    {
        $query->where('role', UserRole::VENDOR->value);
    }

    #[Scope]
    protected function customer(Builder $query): void
    {
        $query->where('role', UserRole::CUSTOMER->value);
    }

    #[Scope]
    protected function rider(Builder $query): void
    {
        $query->where('role', UserRole::RIDER->value);
    }
}
