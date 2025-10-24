<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function format($date, bool $withTime = true): string
    {
        if (! $date) {
            return 'N/A';
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user) {
            return self::defaultFormat($date, $withTime);
        }

        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $date = $date->setTimezone($user->timezone ?? 'UTC');

        $format = $user->date_format ?? 'd/m/Y';

        if ($withTime) {
            $format .= ' H:i:s';
        }

        return $date->format($format);
    }

    public static function formatShort($date): string
    {
        return self::format($date, false);
    }

    protected static function defaultFormat($date, bool $withTime): string
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $format = 'd/m/Y';

        if ($withTime) {
            $format .= ' H:i:s';
        }

        return $date->format($format);
    }
}
