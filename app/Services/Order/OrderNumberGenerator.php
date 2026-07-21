<?php

namespace App\Services\Order;

class OrderNumberGenerator
{
    /**
     * Format: ORD-YYYYMMDD-XXXXXXXXXX
     */
    private const PREFIX = 'ORD-';

    /**
     * Length of the random suffix.
     */
    private const SUFFIX_LENGTH = 10;

    /**
     * Suffix alphabet, excluding characters that are easily confused when a number
     * is read aloud or typed from a receipt: 0/O and 1/I/L.
     */
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Generate a unique order number.
     *
     * Uses a random suffix to avoid sequential IDs and relies on the
     * unique database constraint to guard against collisions.
     */
    public function generate(): string
    {
        return self::PREFIX . now()->format('Ymd') . '-' . $this->randomSuffix();
    }

    /**
     * Build the random portion using a cryptographically secure source.
     */
    private function randomSuffix(): string
    {
        $max    = strlen(self::ALPHABET) - 1;
        $suffix = '';

        for ($i = 0; $i < self::SUFFIX_LENGTH; $i++) {
            $suffix .= self::ALPHABET[random_int(0, $max)];
        }

        return $suffix;
    }
}
