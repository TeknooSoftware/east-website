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

namespace Teknoo\Tests\East\Website\Tools\Tui\Widget;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Render\RenderContext;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;
use Teknoo\East\Website\Tools\Tui\Widget\LineWidget;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the line of text of the interactive mode: always one line high, never wider than the terminal
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LineWidget::class)]
class LineWidgetTest extends TestCase
{
    public function testItIsEmptyByDefault(): void
    {
        self::assertSame('', (new LineWidget())->getText());
    }

    public function testItKeepsItsText(): void
    {
        $line = new LineWidget('A title');

        self::assertSame('A title', $line->getText());
        self::assertSame($line, $line->setText('Another title'));
        self::assertSame('Another title', $line->getText());
        self::assertSame($line, $line->setText('Another title'));
        self::assertSame('Another title', $line->getText());
    }

    public function testItDisplaysItsText(): void
    {
        $harness = new TuiHarness(40, 4);
        $harness->mount(new LineWidget('Posts — page 1/3'));

        self::assertSame(['Posts — page 1/3'], $harness->lines());
    }

    public function testItDisplaysItsNewText(): void
    {
        $harness = new TuiHarness(40, 4);
        $harness->mount($line = new LineWidget('Loading...'));

        $line->setText('3 posts');
        $harness->render();
        self::assertSame(['3 posts'], $harness->lines());

        $line->setText('');
        $harness->render();
        self::assertSame([], $harness->lines());
    }

    public function testATextWiderThanTheTerminalIsCut(): void
    {
        $harness = new TuiHarness(10, 3);
        $harness->mount(new LineWidget('abcdefghijklmnop'));

        self::assertSame(['abcdefghij'], $harness->lines());
    }

    public function testAWideCharacterIsNeverCutInTheMiddle(): void
    {
        $harness = new TuiHarness(5, 3);
        $harness->mount(new LineWidget('日本語のタイトル'));

        self::assertSame(['日本'], $harness->lines());
    }

    public function testItIsAlwaysOneLineHigh(): void
    {
        $context = new RenderContext(10, 5);

        self::assertSame([''], (new LineWidget())->render($context));
        self::assertSame(['abc'], (new LineWidget('abc'))->render($context));
        self::assertCount(1, (new LineWidget(str_repeat('abc ', 20)))->render($context));
        self::assertSame([''], (new LineWidget('abc'))->render(new RenderContext(0, 0)));
    }

    public function testAStyledTextKeepsItsStyle(): void
    {
        $text = Ansi::bold('Posts');

        self::assertSame([$text], (new LineWidget($text))->render(new RenderContext(10, 1)));

        $lines = (new LineWidget(Ansi::bold('A long title')))->render(new RenderContext(6, 1));
        self::assertCount(1, $lines);
        self::assertSame(6, Ansi::width($lines[0]));
        self::assertStringStartsWith("\x1b[1mA long", $lines[0]);
    }
}
