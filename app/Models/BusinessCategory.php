<?php

namespace App\Models;

use App\Filters\BusinessCategoryFilters;
use App\Traits\HasSlug;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCategory extends BaseModel
{
    use HasSlug, Filterable, HasFactory;

    protected string $default_filters = BusinessCategoryFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
    ];

    /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /**
     * Business category can contain many stores
     * EG: Groceries can have market 1 , market 2 ..etc
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
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