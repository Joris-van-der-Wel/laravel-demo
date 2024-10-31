<?php
declare(strict_types=1);

namespace App\Services;

use \RandomLib;

class SecureRandom {
    private \RandomLib\Generator $mediumStrengthGenerator;

    public function __construct()
    {
        $factory = new RandomLib\Factory;
        $this->mediumStrengthGenerator = $factory->getMediumStrengthGenerator();
    }

    /**
     * Generates a secure random token of the specified length with characters that do not require url encoding.
     * Each characters represents 6 bits of randomness
     */
    public function urlSafeToken(int $length = 64): string {
        return $this->mediumStrengthGenerator->generateString(
            $length,
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_',
        );
    }
}
