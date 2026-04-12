<?php

namespace App\Enums;

enum SettingKey: int
{
    case EMAIL = 1;
    case PHONE = 2;
    case WHATSAPP = 3;
    case FACEBOOK = 4;
    case INSTAGRAM = 5;
    case X = 6;
    case LOYALTY_POINTS = 7;

    /**
    * Get the string key representation of the enum case.
    */
    public function key(): string
    {
        return match ($this) {
            self::EMAIL => 'email',
            self::PHONE => 'phone',
            self::WHATSAPP => 'whatsapp',
            self::FACEBOOK => 'facebook',
            self::INSTAGRAM => 'instagram',
            self::X => 'x',
            self::LOYALTY_POINTS => 'loyalty_points',
        };
    }

    /**
     * Get the contact related cases as an array.
     */
    public static function contactCases(): array
    {
        return [
            self::EMAIL,
            self::PHONE,
            self::WHATSAPP,
        ];
    }

    /**
     * Get the social related cases as an array.
     */
    public static function socialCases(): array
    {
        return [
            self::FACEBOOK,
            self::INSTAGRAM,
            self::X,
        ];
    }

    /**
     * Get the public cases (contact info & social media)
     * that should be displayed publicly.
     */
    public static function publicCases(): array
    {
        return [
            self::EMAIL,
            self::PHONE,
            self::WHATSAPP,
            self::FACEBOOK,
            self::INSTAGRAM,
            self::X,
        ];
    }

    /**
     * Get all the possible values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}