<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCategory extends BaseModel
{
    use HasSlug;
    
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