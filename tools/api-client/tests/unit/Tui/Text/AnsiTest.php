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

namespace Teknoo\Tests\East\Website\Tools\Tui\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;

/**
 * Tests of the sizing and of the styling of the lines of the interactive mode: a text is always fitted by its
 * displayed width, a wide character counts for two columns and a style for none
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Ansi::class)]
class AnsiTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideWidths(): iterable
    {
        yield 'empty' => ['', 0];
        yield 'ascii' => ['abc', 3];
        yield 'accents' => ['été', 3];
        yield 'wide characters' => ['日本語', 6];
        yield 'mixed' => ['a日b', 4];
        yield 'ellipsis' => [Ansi::ELLIPSIS, 1];
        yield 'styles are not displayed' => ["\x1b[1mabc\x1b[22m", 3];
        yield 'combining character' => ["e\u{301}", 1];
    }

    #[DataProvider('provideWidths')]
    public function testWidthIsTheDisplayedWidth(string $text, int $expected): void
    {
        self::assertSame($expected, Ansi::width($text));
    }

    /**
     * @return iterable<string, array{string, int, bool, string}>
     */
    public static function provideFits(): iterable
    {
        yield 'shorter, not padded' => ['ab', 5, false, 'ab'];
        yield 'shorter, padded' => ['ab', 5, true, 'ab   '];
        yield 'exact, not padded' => ['abcde', 5, false, 'abcde'];
        yield 'exact, padded' => ['abcde', 5, true, 'abcde'];
        yield 'longer' => ['abcdef', 5, false, 'abcd…'];
        yield 'longer, padded' => ['abcdef', 5, true, 'abcd…'];
        yield 'two columns' => ['abcdef', 2, false, 'a…'];
        yield 'one column' => ['abcdef', 1, false, '…'];
        yield 'no column' => ['abcdef', 0, false, ''];
        yield 'no column, padded' => ['abcdef', 0, true, ''];
        yield 'negative width' => ['abcdef', -3, true, ''];
        yield 'empty, padded' => ['', 3, true, '   '];
        yield 'empty, not padded' => ['', 3, false, ''];
        yield 'accents are one column wide' => ['éééééé', 4, false, 'ééé…'];
        yield 'wide characters, exact' => ['日本語', 6, true, '日本語'];
        yield 'wide characters, cut between two of them' => ['日本語', 5, false, '日本…'];
        yield 'wide characters, never cut in the middle' => ['日本語', 4, false, '日…'];
        yield 'wide characters, padded after the ellipsis' => ['日本語', 4, true, '日… '];
        yield 'wide characters, two columns' => ['日本語', 2, true, '… '];
        yield 'wide characters, one column' => ['日本語', 1, true, '…'];
    }

    #[DataProvider('provideFits')]
    public function testFitCutsATextToADisplayedWidth(string $text, int $width, bool $pad, string $expected): void
    {
        $fitted = Ansi::fit($text, $width, $pad);

        self::assertSame($expected, $fitted);
        self::assertLessThanOrEqual(max(0, $width), Ansi::width($fitted));
        // With the padding, the text always has the asked width: the columns of a table stay aligned
        self::assertSame(max(0, $width), Ansi::width(Ansi::fit($text, $width, true)));
    }

    public function testClipKeepsALineWhichIsNotTooWide(): void
    {
        self::assertSame('abc', Ansi::clip('abc', 3));
        self::assertSame('abc', Ansi::clip('abc', 10));
        self::assertSame('', Ansi::clip('', 0));
        self::assertSame('日本語', Ansi::clip('日本語', 6));

        $styled = Ansi::bold('abc');
        self::assertSame($styled, Ansi::clip($styled, 3));
    }

    public function testClipCutsALineWithoutEllipsis(): void
    {
        self::assertSame('abc', Ansi::clip('abcdef', 3));
        self::assertSame('a', Ansi::clip('abcdef', 1));
    }

    public function testClipGivesNothingWithoutAnyColumn(): void
    {
        self::assertSame('', Ansi::clip('abcdef', 0));
        self::assertSame('', Ansi::clip('abcdef', -1));
        self::assertSame('', Ansi::clip(Ansi::reverse('abcdef'), 0));
    }

    /**
     * @return iterable<string, array{string, int, int, string}>
     */
    public static function provideClippedLines(): iterable
    {
        yield 'styled line' => [Ansi::bold('abcdef'), 3, 3, 'abc'];
        yield 'reversed line' => [Ansi::reverse('> abcdef'), 4, 4, '> ab'];
        yield 'wide characters' => ['日本語', 4, 4, '日本'];
        yield 'wide characters, never cut in the middle' => ['日本語', 3, 2, '日'];
        yield 'wide characters, one column' => ['日本語', 1, 0, ''];
        yield 'styled wide characters' => [Ansi::dim('日本語'), 5, 4, '日本'];
        yield 'mixed' => ['a日本語', 4, 3, 'a日'];
    }

    #[DataProvider('provideClippedLines')]
    public function testClipIsNeverWiderThanTheWidth(string $line, int $width, int $expectedWidth, string $text): void
    {
        $clipped = Ansi::clip($line, $width);

        self::assertSame($expectedWidth, Ansi::width($clipped));
        self::assertLessThanOrEqual($width, Ansi::width($clipped));
        // Only the styles are removed here to read the text: what is displayed is the beginning of the line
        self::assertSame($text, (string) preg_replace('/\x1b\[[0-9;]*m/', '', $clipped));
    }

    public function testBold(): void
    {
        self::assertSame("\x1b[1mtext\x1b[22m", Ansi::bold('text'));
    }

    public function testDim(): void
    {
        self::assertSame("\x1b[2mtext\x1b[22m", Ansi::dim('text'));
    }

    public function testReverse(): void
    {
        self::assertSame("\x1b[7mtext\x1b[27m", Ansi::reverse('text'));
    }

    public function testError(): void
    {
        self::assertSame("\x1b[31mtext\x1b[39m", Ansi::error('text'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideStyles(): iterable
    {
        yield 'bold' => [Ansi::bold('日本 text')];
        yield 'dim' => [Ansi::dim('日本 text')];
        yield 'reverse' => [Ansi::reverse('日本 text')];
        yield 'error' => [Ansi::error('日本 text')];
        yield 'nested' => [Ansi::bold(Ansi::error('日本') . ' ' . Ansi::dim('text'))];
    }

    #[DataProvider('provideStyles')]
    public function testAStyleDoesNotChangeTheDisplayedWidth(string $styled): void
    {
        self::assertSame(9, Ansi::width($styled));
    }

    public function testAStyleOfAnEmptyTextHasNoWidth(): void
    {
        self::assertSame(0, Ansi::width(Ansi::bold('') . Ansi::dim('') . Ansi::reverse('') . Ansi::error('')));
    }
}
