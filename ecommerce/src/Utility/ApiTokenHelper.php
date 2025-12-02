<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Utility;

class ApiTokenHelper
{
    private const TOKEN_LENGTH = 48;

    private const CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Generate a random 48-character alphanumeric token (A-Za-z0-9)
     */
    public static function generate(): string
    {
        $token = '';
        $maxIndex = strlen(self::CHARACTERS) - 1;

        for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
            $token .= self::CHARACTERS[random_int(0, $maxIndex)];
        }

        return $token;
    }
}
