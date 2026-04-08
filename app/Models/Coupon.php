<?php

namespace App\Models;

use App\Enums\CouponType;
use App\Enums\DefineStatus;
use App\Filters\CouponFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

class Coupon extends BaseModel
{
    use Filterable;

    protected string $default_filters = CouponFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'code',
        'name',
        'description',
        'minimum_order',
        'maximum_discount',
        'coupon_type',
        'value',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maximum_discount' => 'decimal:2',
            'minimum_order'    => 'decimal:2',
            'coupon_type'      => CouponType::class,
            'expires_at'       => 'datetime',
            'starts_at'        => 'datetime',
            'status'           => DefineStatus::class,
            'value'            => 'decimal:2',
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * Get the orders that have used this coupon.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
    * Get the store that owns the coupon.
    */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function active(Builder $query): void
    {
        $query
            ->where('status', DefineStatus::ACTIVE->value)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }
}