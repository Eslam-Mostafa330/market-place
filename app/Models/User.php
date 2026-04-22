<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\DefineStatus;
use App\Enums\RiderAvailability;
use App\Enums\UserRole;
use App\Filters\UserFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends BaseAuthenticatableModel
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, Filterable, LogsActivity;

    protected string $default_filters = UserFilters::class;

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
        'email_verified_at',
        'phone_verified_at',
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

    /**
     * Get the store owned by this vendor user.
     */
    public function store(): HasOneThrough
    {
        return $this->hasOneThrough(Store::class, VendorProfile::class, 'user_id', 'vendor_profile_id');
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

    /**
     * Scope a query to only retrieves the available riders.
     */
    #[Scope]
    protected function availableRiders(Builder $query): void
    {
        $query
            ->join('rider_profiles', 'users.id', '=', 'rider_profiles.user_id')
            ->where('users.role', UserRole::RIDER->value)
            ->where('rider_profiles.rider_availability', RiderAvailability::AVAILABLE->value)
            ->select('users.*');
    }

    /****************************/
    /**** Role Check Methods ****/
    /****************************/
    /**
     * Ensure the user is vendor
     */
    public function isVendor(): bool
    {
        return $this->role === UserRole::VENDOR;
    }

    /**
     * Ensure the user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Ensure the user is customer
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    /**
     * Ensure the user is rider
     */
    public function isRider(): bool
    {
        return $this->role === UserRole::RIDER;
    }

    /*******************************/
    /*** Rider Business Methods ***/
    /******************************/
    /**
     * Check if the user is an available rider.
     */
    public function isAvailableRider(): bool
    {
        return $this->isRider()
            && $this->riderProfile?->rider_availability === RiderAvailability::AVAILABLE;
    }

    /**** ***************** ****/
    /**** ActivityLog Usage ****/
    /**** ***************** ****/
    /**
     * Define activity log behavior for User model.
     *
     * Logs changes only for specific attributes and only when they are modified,
     * avoiding empty log entries.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Determine if the given event should be logged.
     *
     * Only log events for users who are not customers, as customers typically have less critical changes.
     */
    public function shouldLogEvent(string $eventName): bool
    {
        return $this->role !== UserRole::CUSTOMER;
    }
}