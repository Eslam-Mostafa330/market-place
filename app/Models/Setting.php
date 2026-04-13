<?php

namespace App\Models;

use App\Enums\SettingKey;

class Setting extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => SettingKey::class,
        ];
    }

    /*************************/
    /**** Custom Methods ****/
    /************************/
    /**
     * Get the loyalty points value as integer.
     */
    public static function loyaltyPoints(): int
    {
        $value = self::where('key', SettingKey::LOYALTY_POINTS->value)->value('value');

        return (int) $value;
    }
}