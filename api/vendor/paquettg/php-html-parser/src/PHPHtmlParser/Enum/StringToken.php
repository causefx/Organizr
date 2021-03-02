<?php

declare(strict_types=1);

namespace PHPHtmlParser\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static StringToken BLANK()
 * @method static StringToken EQUAL()
 * @method static StringToken SLASH()
 * @method static StringToken ATTR()
 * @method static StringToken CLOSECOMMENT()
 */
class StringToken extends Enum
{
    private const BLANK = " \t\r\n";
    private const EQUAL = ' =/>';
    private const SLASH = " />\r\n\t";
    private const ATTR = ' >';
    private const CLOSECOMMENT = '-->';
}
