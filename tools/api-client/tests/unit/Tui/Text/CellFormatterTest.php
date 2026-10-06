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
use stdClass;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;

/**
 * Tests of the text of the values of the API documents in the interactive mode: the content of the website is
 * never sent raw to the terminal, none of its control characters may reach it
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CellFormatter::class)]
class CellFormatterTest extends TestCase
{
    /**
     * No control character may be left: neither C0 (the line break is checked apart), nor DEL, nor C1.
     */
    private static function assertNoControlCharacter(string $text, bool $multiline = false): void
    {
        self::assertTrue(mb_check_encoding($text, 'UTF-8'), 'The text is not a valid UTF-8 string');
        self::assertDoesNotMatchRegularExpression(
            $multiline ? '/[\x{00}-\x{09}\x{0B}-\x{1F}\x{7F}-\x{9F}]/u' : '/[\x{00}-\x{1F}\x{7F}-\x{9F}]/u',
            $text,
        );
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideCells(): iterable
    {
        yield 'null' => [null, ''];
        yield 'true' => [true, 'yes'];
        yield 'false' => [false, 'no'];
        yield 'string' => ['A title', 'A title'];
        yield 'empty string' => ['', ''];
        yield 'integer' => [42, '42'];
        yield 'zero' => [0, '0'];
        yield 'float' => [1.5, '1.5'];
        yield 'accents and wide characters' => ['Été 日本語', 'Été 日本語'];
        yield 'text on several lines' => ["first\nsecond\r\nthird\tend", 'first second third end'];
        yield 'empty list' => [[], ''];
        yield 'list of strings' => [['ROLE_USER', 'ROLE_ADMIN'], 'ROLE_USER, ROLE_ADMIN'];
        yield 'list of scalars' => [['a', 2, true, false], 'a, 2, yes, no'];
        yield 'object with a name' => [['id' => 'abc', 'name' => 'A type'], 'A type'];
        yield 'object with a title' => [['id' => 'abc', 'title' => 'A post', 'slug' => 'a-post'], 'A post'];
        yield 'object with an email' => [['id' => 'abc', 'email' => 'ann@teknoo.software'], 'ann@teknoo.software'];
        yield 'object with a slug' => [['id' => 'abc', 'slug' => 'a-post'], 'a-post'];
        yield 'object with only an id' => [['id' => 'abc', 'other' => 'ignored'], 'abc'];
        yield 'object without label' => [['a' => 1, 'b' => ['c' => true]], '{"a":1,"b":{"c":true}}'];
        yield 'list of objects' => [
            [['id' => 't1', 'name' => 'First'], ['id' => 't2', 'title' => 'Second'], ['id' => 't3']],
            'First, Second, t3',
        ];
        yield 'list of lists' => [[['a', 'b'], ['c']], 'a, b, c'];
        yield 'not a value of a document' => [new stdClass(), ''];
    }

    #[DataProvider('provideCells')]
    public function testCellGivesTheTextOfAValueOnASingleLine(mixed $value, string $expected): void
    {
        self::assertSame($expected, CellFormatter::cell($value));
    }

    public function testCellNeverKeepsAnEscapeSequenceOfAString(): void
    {
        $cell = CellFormatter::cell("\x1b[31mred\x1b[0m\x07");

        self::assertNoControlCharacter($cell);
        self::assertStringNotContainsString("\x1b", $cell);
        self::assertStringNotContainsString("\x07", $cell);
        self::assertStringContainsString('red', $cell);
    }

    public function testCellCleansTheLabelOfANestedObject(): void
    {
        $cell = CellFormatter::cell(['id' => 'abc', 'name' => "\x1b]0;owned\x07An\u{9b}2J author\nhere"]);

        self::assertNoControlCharacter($cell);
        self::assertStringStartsWith(']0;owned', $cell);
        self::assertStringEndsWith(' author here', $cell);
    }

    public function testCellCleansEveryItemOfAList(): void
    {
        $cell = CellFormatter::cell(["\x1b[2Jfirst", ['name' => "se\x00cond\x1b"], "\u{85}third\r\n"]);

        self::assertNoControlCharacter($cell);
        self::assertSame('[2Jfirst, second, third ', $cell);
    }

    public function testCellCleansTheJsonOfAnObjectWithoutLabel(): void
    {
        $cell = CellFormatter::cell(['html' => "a\x1b[31mb\u{9b}c\nd"]);

        self::assertNoControlCharacter($cell);
        self::assertStringContainsString('"html"', $cell);
    }

    /**
     * @return iterable<string, array{array<mixed>, string|null, string|null}>
     */
    public static function provideLabels(): iterable
    {
        yield 'empty object' => [[], null, null];
        yield 'no known key' => [['other' => 'x'], null, null];
        yield 'name first' => [['id' => 'i', 'slug' => 's', 'email' => 'e', 'title' => 't', 'name' => 'n'], null, 'n'];
        yield 'then title' => [['id' => 'i', 'slug' => 's', 'email' => 'e', 'title' => 't'], null, 't'];
        yield 'then email' => [['id' => 'i', 'slug' => 's', 'email' => 'e'], null, 'e'];
        yield 'then slug' => [['id' => 'i', 'slug' => 's'], null, 's'];
        yield 'then id' => [['id' => 'i'], null, 'i'];
        yield 'label field before the others' => [['name' => 'n', 'lastName' => 'Doe'], 'lastName', 'Doe'];
        yield 'missing label field' => [['name' => 'n'], 'lastName', 'n'];
        yield 'empty label field' => [['name' => 'n', 'lastName' => ''], 'lastName', 'n'];
        yield 'empty name is skipped' => [['name' => '', 'id' => 'i'], null, 'i'];
        yield 'null name is skipped' => [['name' => null, 'id' => 'i'], null, 'i'];
        yield 'boolean is not a label' => [['name' => true, 'title' => false, 'id' => 'i'], null, 'i'];
        yield 'array is not a label' => [['name' => ['x'], 'id' => 'i'], null, 'i'];
        yield 'number is a label' => [['name' => 12], null, '12'];
        yield 'only values which are not labels' => [['name' => '', 'title' => null, 'id' => []], null, null];
        yield 'label on a single line' => [['title' => "A\ttitle\non two lines"], null, 'A title on two lines'];
    }

    /**
     * @param array<mixed> $object
     */
    #[DataProvider('provideLabels')]
    public function testLabelOfANestedObject(array $object, ?string $labelField, ?string $expected): void
    {
        self::assertSame($expected, CellFormatter::label($object, $labelField));
    }

    public function testLabelIsCleaned(): void
    {
        $label = CellFormatter::label(['name' => "\x1b[31mAnn\x1b[0m\x07\u{9b}"]);

        self::assertNotNull($label);
        self::assertNoControlCharacter($label);
        self::assertStringContainsString('Ann', $label);
    }

    public function testCleanRemovesTheEscapeAndTheBellOfAnEscapeSequence(): void
    {
        $text = CellFormatter::clean("\x1b[31mred\x1b[0m\x07");

        self::assertNoControlCharacter($text);
        self::assertStringNotContainsString("\x1b", $text);
        self::assertStringNotContainsString("\x07", $text);
        self::assertStringContainsString('red', $text);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideControlCharacters(): iterable
    {
        for ($code = 0x00; $code <= 0x1F; ++$code) {
            if (0x09 !== $code && 0x0A !== $code && 0x0D !== $code) {
                yield sprintf('C0 0x%02X', $code) => [mb_chr($code, 'UTF-8')];
            }
        }

        for ($code = 0x7F; $code <= 0x9F; ++$code) {
            yield sprintf('C1 0x%02X', $code) => [mb_chr($code, 'UTF-8')];
        }
    }

    #[DataProvider('provideControlCharacters')]
    public function testCleanRemovesEveryControlCharacter(string $control): void
    {
        self::assertSame('ab', CellFormatter::clean('a' . $control . 'b'));
        self::assertSame('ab', CellFormatter::clean('a' . $control . 'b', true));
    }

    public function testCleanRemovesTheControlSequenceIntroducerOnEightBits(): void
    {
        // U+009B is the CSI of the C1 set: "\u{9b}31m" colors a text like "\x1b[31m" on some terminals
        $text = CellFormatter::clean("a\u{9b}31mb\u{85}c\u{90}d\u{9f}e");

        self::assertNoControlCharacter($text);
        self::assertSame('a31mbcde', $text);
    }

    public function testCleanKeepsThePrintableCharacters(): void
    {
        $text = "A title, with « quotes », an é, a non-breaking\u{a0}space, 日本語 and ~ [31m";

        self::assertSame($text, CellFormatter::clean($text));
        self::assertSame($text, CellFormatter::clean($text, true));
    }

    public function testCleanOnASingleLineReplacesTheLineBreaksAndTheTabulationsBySpaces(): void
    {
        self::assertSame('a b c d e', CellFormatter::clean("a\r\nb\rc\td\ne"));
        self::assertSame('a  b', CellFormatter::clean("a\n\nb"));
        self::assertNoControlCharacter(CellFormatter::clean("a\r\nb\rc\td\ne\x0b\x0c"));
    }

    public function testCleanOnSeveralLinesKeepsTheLineBreaksAndIndentsTheTabulations(): void
    {
        $text = CellFormatter::clean("a\r\nb\rc\td\ne", true);

        self::assertSame("a\nb\nc    d\ne", $text);
        self::assertNoControlCharacter($text, true);
    }

    public function testCleanOnSeveralLinesStillRemovesTheEscapeSequences(): void
    {
        $text = CellFormatter::clean("first\x1b[31m\nsecond\x07\u{9b}\n\x00third", true);

        self::assertSame("first[31m\nsecond\nthird", $text);
        self::assertNoControlCharacter($text, true);
    }

    public function testCleanReplacesTheInvalidUtf8Bytes(): void
    {
        $text = CellFormatter::clean("a\xffb\x9bc\xc3");

        self::assertNoControlCharacter($text);
        self::assertSame('a?b?c?', $text);
    }

    public function testCleanOfAnInvalidUtf8TextStillRemovesItsControlCharacters(): void
    {
        $text = CellFormatter::clean("\xfe\x1b[31mred\x07\xff", true);

        self::assertNoControlCharacter($text, true);
        self::assertStringContainsString('red', $text);
    }

    public function testCleanOfAnEmptyText(): void
    {
        self::assertSame('', CellFormatter::clean(''));
        self::assertSame('', CellFormatter::clean('', true));
        self::assertSame('', CellFormatter::clean("\x1b\x07\u{9b}"));
    }
}
