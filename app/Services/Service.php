<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class Service
{
    public function sanitizeNumber(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        $cleaned = preg_replace('/[^\\d.]+/', '', $value);
        if (is_numeric($cleaned)) {
            return floatval($cleaned);
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
