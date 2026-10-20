<?php

namespace App\Traits;

trait ClampsPerPage
{
    /**
     * Normalise a user-supplied page size to [1, $max]. Non-numeric, zero and
     * negative values would otherwise reach paginate() and break (division by zero).
     */
    protected function clampPerPage(mixed $value, int $default = 15, int $max = 100): int
    {
        $perPage = is_numeric($value) ? (int) $value : $default;

        return max(1, min($perPage, $max));
    }
}
