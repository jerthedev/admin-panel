<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Color Field
 *
 * A color picker field with support for hex, rgb, hsl formats,
 * color palettes, and alpha channel.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Color extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'ColorField';
}
