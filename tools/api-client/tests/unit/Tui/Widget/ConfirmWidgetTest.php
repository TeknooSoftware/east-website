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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Ansi\AnsiUtils;
use Symfony\Component\Tui\Render\RenderContext;
use Teknoo\East\Website\Tools\Tui\Widget\ConfirmWidget;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the question answered by yes or no of the interactive mode: only an explicit yes is a yes
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfirmWidget::class)]
class ConfirmWidgetTest extends TestCase
{
    /**
     * @param array<string> $lines
     * @return list<string> the lines without their styles
     */
    private static function plain(array $lines): array
    {
        return array_values(array_map(AnsiUtils::stripAnsiCodes(...), $lines));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideAnswers(): iterable
    {
        yield 'y' => ['y', true];
        yield 'Y' => ['Y', true];
        yield 'n' => ['n', false];
        yield 'N' => ['N', false];
        yield 'Enter' => [Keys::ENTER, false];
        yield 'Escape' => [Keys::ESCAPE, false];
    }

    #[DataProvider('provideAnswers')]
    public function testAKeyAnswersTheQuestion(string $key, bool $expected): void
    {
        $answers = [];
        $widget = (new ConfirmWidget('Delete the post ?'))->onAnswer(
            static function (bool $answer) use (&$answers): void {
                $answers[] = $answer;
            },
        );

        $harness = new TuiHarness(40, 8);
        $harness->mount($widget)->keys($key);

        self::assertSame([$expected], $answers);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIgnoredKeys(): iterable
    {
        yield 'letter' => ['x'];
        yield 'o of ok' => ['o'];
        yield 'digit' => ['1'];
        yield 'space' => [Keys::SPACE];
        yield 'tab' => [Keys::TAB];
        yield 'shift+tab' => [Keys::SHIFT_TAB];
        yield 'backspace' => [Keys::BACKSPACE];
        yield 'up' => [Keys::UP];
        yield 'down' => [Keys::DOWN];
        yield 'left' => [Keys::LEFT];
        yield 'right' => [Keys::RIGHT];
        yield 'ctrl+s' => [Keys::CTRL_S];
        yield 'F2' => [Keys::F2];
    }

    #[DataProvider('provideIgnoredKeys')]
    public function testAnotherKeyIsIgnored(string $key): void
    {
        $answers = [];
        $widget = (new ConfirmWidget('Delete the post ?'))->onAnswer(
            static function (bool $answer) use (&$answers): void {
                $answers[] = $answer;
            },
        );

        $harness = new TuiHarness(40, 8);
        $harness->mount($widget)->keys($key);

        self::assertSame([], $answers);
        self::assertSame(['', 'Delete the post ?', '', '[y] yes   [n] no'], $harness->lines());
    }

    public function testEveryAnswerIsReported(): void
    {
        $answers = [];
        $widget = new ConfirmWidget('Delete the post ?');
        self::assertSame($widget, $widget->onAnswer(static function (bool $answer) use (&$answers): void {
            $answers[] = $answer;
        }));

        $harness = new TuiHarness(40, 8);
        $harness->mount($widget)->keys('x', 'y', 'n', Keys::SPACE, 'Y', Keys::ENTER);

        self::assertSame([true, false, true, false], $answers);
    }

    public function testNothingHappensWithoutCallback(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount(new ConfirmWidget('Delete the post ?'))->keys('y', 'n', Keys::ENTER, Keys::ESCAPE, 'x');

        self::assertSame(['', 'Delete the post ?', '', '[y] yes   [n] no'], $harness->lines());
    }

    public function testItDisplaysTheQuestionAndTheKeys(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount(new ConfirmWidget('Delete the post "Hello" ?'));

        self::assertSame(['', 'Delete the post "Hello" ?', '', '[y] yes   [n] no'], $harness->lines());
    }

    public function testAQuestionWiderThanTheTerminalIsCut(): void
    {
        $harness = new TuiHarness(12, 8);
        $harness->mount(new ConfirmWidget('Delete the post "Hello" ?'));

        self::assertSame(['', 'Delete the …', '', '[y] yes   […'], $harness->lines());
    }

    public function testItIsDisplayedOnATerminalOfOneColumn(): void
    {
        $harness = new TuiHarness(1, 8);
        $harness->mount(new ConfirmWidget('Delete the post ?'));

        self::assertSame(['', '…', '', '…'], $harness->lines());
    }

    public function testItFillsTheHeightByDefault(): void
    {
        $widget = new ConfirmWidget('Sure ?');

        self::assertTrue($widget->isVerticallyExpanded());
        self::assertSame(
            ['', 'Sure ?', '', '[y] yes   [n] no', '', '', '', ''],
            self::plain($widget->render(new RenderContext(40, 8))),
        );
    }

    public function testItIsNotHigherThanItsLinesWhenItIsNotExpanded(): void
    {
        $widget = new ConfirmWidget('Sure ?');

        self::assertSame($widget, $widget->expandVertically(false));
        self::assertFalse($widget->isVerticallyExpanded());
        self::assertSame(
            ['', 'Sure ?', '', '[y] yes   [n] no'],
            self::plain($widget->render(new RenderContext(40, 8))),
        );

        self::assertTrue($widget->expandVertically(true)->isVerticallyExpanded());
        self::assertCount(8, $widget->render(new RenderContext(40, 8)));
    }

    public function testItNeverDrawsMoreLinesThanItsHeight(): void
    {
        $widget = new ConfirmWidget('Sure ?');

        self::assertSame(['', 'Sure ?'], self::plain($widget->render(new RenderContext(40, 2))));
        self::assertCount(1, $widget->render(new RenderContext(40, 1)));
        self::assertCount(1, $widget->render(new RenderContext(40, 0)));

        $widget->expandVertically(false);
        self::assertSame(['', 'Sure ?', ''], self::plain($widget->render(new RenderContext(40, 3))));
    }
}
