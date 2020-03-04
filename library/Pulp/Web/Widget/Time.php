<?php

namespace Icinga\Module\Pulp\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use Icinga\Date\DateFormatter;

class Time
{
    public static function format($time)
    {
        if ($time === null || $time === 'never') {
            return new Badge('never');
        }

        $time = \strtotime($time);
        $formatted = DateFormatter::timeAgo($time, true);

        return $formatted;
    }

    public static function formatWithExpirationCheck($time, $expiration = 86400)
    {
        if ($time === null || $time === 'never') {
            return new Badge('never');
        }

        $time = \strtotime($time);
        $formatted = DateFormatter::timeAgo($time, true);

        if ((\time() - $time) > $expiration) {
            return [$formatted . ' ', Alert::critical()];
        } else {
            return [$formatted . ' ', Icon::create('ok', ['style' => 'color: green'])];
        }
    }
}
