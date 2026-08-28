<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class Service
{
    protected function sanitizeNumber(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return intval($value);
        }
        if (is_string($value)) {
            $cleaned = preg_replace('/[^\\d.]+/', '', $value);

            return is_numeric($cleaned) ? intval(floatval($cleaned)) : null;
        }

        return null;
    }

    protected function logError(\Exception|\Throwable $e): void
    {
        Log::error(sprintf('[%s][%s][%s] %s',
            $e->getCode(),
            $e->getFile(),
            $e->getLine(),
            $e->getMessage()),
            $e->getTrace());
    }
}
