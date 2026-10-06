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

namespace Teknoo\Tests\East\Website\Tools\Support;

use function mb_str_split;

/**
 * Bytes sent by a terminal for the keys used by the tests of the interactive mode.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Keys
{
    public const string UP = "\x1b[A";
    public const string DOWN = "\x1b[B";
    public const string RIGHT = "\x1b[C";
    public const string LEFT = "\x1b[D";
    public const string HOME = "\x1b[H";
    public const string END = "\x1b[F";
    public const string PAGE_UP = "\x1b[5~";
    public const string PAGE_DOWN = "\x1b[6~";
    public const string ENTER = "\r";
    public const string ESCAPE = "\x1b";
    public const string TAB = "\t";
    public const string SHIFT_TAB = "\x1b[Z";
    public const string SPACE = ' ';
    public const string BACKSPACE = "\x7f";
    public const string CTRL_C = "\x03";
    public const string CTRL_S = "\x13";
    public const string F2 = "\x1bOQ";
    public const string F6 = "\x1b[17~";

    /**
     * @return list<string> a key by character of the text
     */
    public static function text(string $text): array
    {
        return mb_str_split($text);
    }
}
