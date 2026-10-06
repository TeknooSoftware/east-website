<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Tools\Tui\Text;

use Symfony\Component\Tui\Ansi\AnsiUtils;

use function max;
use function str_repeat;

/**
 * Sizing and styling of the lines drawn by the widgets of the interactive mode. A line wider than the terminal
 * is refused by the TUI component, so every text is fitted by its displayed width (wide characters included).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Ansi
{
    public const string ELLIPSIS = '…';

    public static function width(string $text): int
    {
        return AnsiUtils::visibleWidth($text);
    }

    /**
     * Cuts a text without style to a displayed width, with an ellipsis when it is too long.
     */
    public static function fit(string $text, int $width, bool $pad = false): string
    {
        if ($width <= 0) {
            return '';
        }

        $current = self::width($text);
        if ($current > $width) {
            $text = AnsiUtils::sliceByColumn($text, 0, $width - 1, true) . self::ELLIPSIS;
            $current = self::width($text);
        }

        return $pad ? $text . str_repeat(' ', max(0, $width - $current)) : $text;
    }

    /**
     * Last guard of a line with styles: it is never wider than the terminal.
     */
    public static function clip(string $line, int $width): string
    {
        return self::width($line) > $width ? AnsiUtils::truncateToWidth($line, max(0, $width), '') : $line;
    }

    public static function bold(string $text): string
    {
        return "\x1b[1m" . $text . "\x1b[22m";
    }

    public static function dim(string $text): string
    {
        return "\x1b[2m" . $text . "\x1b[22m";
    }

    public static function reverse(string $text): string
    {
        return "\x1b[7m" . $text . "\x1b[27m";
    }

    public static function error(string $text): string
    {
        return "\x1b[31m" . $text . "\x1b[39m";
    }
}
