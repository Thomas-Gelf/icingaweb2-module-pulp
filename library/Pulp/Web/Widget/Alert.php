<?php

namespace Icinga\Module\Pulp\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use ipl\Html\Attributes;

class Alert
{
    public static function warning($attributes = null)
    {
        return static::withColor('orange', $attributes);
    }

    public static function critical($attributes = null)
    {
        return static::withColor('red', $attributes);
    }

    protected static function withColor($color, $attributes = null)
    {
        $attributes = Attributes::wantAttributes($attributes);
        $attributes->add('style', "color: $color;");
        return Icon::create('warning-empty', $attributes->getAttributes());
    }
}
