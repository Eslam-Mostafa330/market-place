<?php

namespace App\Models;

use App\Enums\DefineStatus;
use App\Filters\StoreBranchFilters;
use App\Traits\HasSlug;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreBranch extends BaseModel
{
    use HasSlug, Filterable, HasFactory;

    protected string $default_filters = StoreBranchFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'address',
        'city',
        'area',
        'phone',
        'delivery_fee',
        'delivery_time_max',
        'latitude',
        'longitude',
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
            'delivery_fee' => 'decimal:2',
            'longitude'    => 'decimal:8',
            'latitude'     => 'decimal:8',
            'status'       => DefineStatus::class,
        ];
    }

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * The branch belongs to a store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The branch can has many orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**** ************* ****/
    /**** Local Scopes  ****/
    /**** ************* ****/
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', DefineStatus::ACTIVE->value);
    }
}