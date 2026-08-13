<?php

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

trait Enum
{
    public function trans()
    {
        $key = 'enums'.'.'.class_basename(static::class).'.'.$this->name;
        if (Lang::has($key)) {
            return __($key);
        }

        $key = 'enums'.'.'.$this->name;
        if (Lang::has($key)) {
            return __($key);
        }

        return $this->name;
    }

    public static function toArray(): array
    {
        return once(function () {
            $result = [];
            foreach (self::cases() as $case) {
                $result[$case->name] = $case->trans();
            }

            return $result;
        });
    }

    public static function names(): array
    {
        return array_keys(static::toArray());
    }
}
