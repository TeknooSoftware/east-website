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

namespace Teknoo\Tests\East\Website\Tools\Tui;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Teknoo\East\Website\Tools\Tui\Navigator;
use Teknoo\East\Website\Tools\Tui\Screen\ScreenInterface;
use Teknoo\East\Website\Tools\Tui\Widget\ConfirmWidget;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the stack of the screens of the interactive mode
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Navigator::class)]
class NavigatorTest extends TestCase
{
    /**
     * @param list<mixed> $resumed results received by the screen
     */
    private function screen(string $name, array &$resumed = [], ?callable $onResume = null): ScreenInterface
    {
        return new class ($name, $resumed, $onResume) implements ScreenInterface {
            private readonly ConfirmWidget $widget;

            /**
             * @param list<mixed> $resumed
             * @param (callable(mixed): void)|null $onResume
             */
            public function __construct(
                private readonly string $name,
                private array &$resumed,
                private $onResume,
            ) {
                $this->widget = new ConfirmWidget('Body of ' . $name);
            }

            public function title(): string
            {
                return 'Title of ' . $this->name;
            }

            public function hints(): string
            {
                return 'Hints of ' . $this->name;
            }

            public function widget(): AbstractWidget&FocusableInterface
            {
                return $this->widget;
            }

            public function resume(mixed $result): void
            {
                $this->resumed[] = $result;
                if (null !== $this->onResume) {
                    ($this->onResume)($result);
                }
            }
        };
    }

    public function testAScreenIsDisplayedBetweenItsTitleAndItsHintsOnTheWholeHeight(): void
    {
        $harness = new TuiHarness(40, 8);
        $screen = $this->screen('one');
        $harness->open($screen);

        $lines = $harness->lines();
        self::assertCount(8, $lines);
        self::assertSame('Title of one', $lines[0]);
        self::assertSame('Body of one', $lines[2]);
        self::assertSame('', $lines[6]);
        self::assertSame('Hints of one', $lines[7]);

        self::assertSame($screen, $harness->navigator->current());
        self::assertSame(1, $harness->navigator->depth());
        self::assertSame($screen->widget(), $harness->tui->getFocus());
    }

    public function testTheLastOpenedScreenIsDisplayedAndClosingItResumesThePreviousOneWithItsResult(): void
    {
        $harness = new TuiHarness(40, 8);
        $resumed = [];
        $first = $this->screen('one', $resumed);
        $second = $this->screen('two');

        $harness->open($first)->open($second);
        self::assertSame('Title of two', $harness->lines()[0]);
        self::assertSame(2, $harness->navigator->depth());
        self::assertSame($second->widget(), $harness->tui->getFocus());

        $harness->navigator->pop('result');
        $harness->render();

        self::assertSame(['result'], $resumed);
        self::assertSame('Title of one', $harness->lines()[0]);
        self::assertSame($first->widget(), $harness->tui->getFocus());
        self::assertFalse($harness->navigator->isStopped());
    }

    public function testAResumedScreenCanOpenAnotherScreen(): void
    {
        $harness = new TuiHarness(40, 8);
        $third = $this->screen('three');
        $resumed = [];
        $first = $this->screen('one', $resumed, static function () use ($harness, $third): void {
            $harness->navigator->push($third);
        });

        $harness->open($first)->open($this->screen('two'));
        $harness->navigator->pop();
        $harness->render();

        self::assertSame([null], $resumed);
        self::assertSame($third, $harness->navigator->current());
        self::assertSame('Title of three', $harness->lines()[0]);
    }

    public function testClosingTheFirstScreenStopsTheInterface(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->open($this->screen('one'));
        self::assertTrue($harness->tui->isRunning());

        $harness->navigator->pop();

        self::assertTrue($harness->navigator->isStopped());
        self::assertFalse($harness->tui->isRunning());
        self::assertNull($harness->navigator->current());
        self::assertSame(0, $harness->navigator->depth());
    }

    public function testQuitStopsTheInterfaceWhateverTheStackAndNothingIsDisplayedAfter(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->open($this->screen('one'))->open($this->screen('two'));

        $harness->navigator->quit();
        self::assertTrue($harness->navigator->isStopped());
        self::assertFalse($harness->tui->isRunning());

        // Late calls of a screen are ignored
        $harness->navigator->status('ignored');
        $harness->navigator->refresh();
        $harness->navigator->push($this->screen('three'));
        self::assertFalse($harness->tui->isRunning());
    }

    public function testTheStatusLineIsAboveTheHints(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->open($this->screen('one'));

        $harness->navigator->status("Saved\x1b[31m\nok");
        $harness->render();
        self::assertSame('Saved[31m ok', $harness->lines()[6]);
        self::assertSame('Saved[31m ok', $harness->navigator->statusText());

        $harness->navigator->status('Refused', true);
        $harness->render();
        self::assertSame('Refused', $harness->lines()[6]);
        self::assertStringContainsString("\x1b[31m", $harness->navigator->statusText());

        $harness->navigator->status('', true);
        $harness->render();
        self::assertSame('', $harness->lines()[6]);
        self::assertSame('', $harness->navigator->statusText());
    }

    public function testRefreshDisplaysTheNewWidgetOfTheCurrentScreen(): void
    {
        $harness = new TuiHarness(40, 8);
        $screen = new class implements ScreenInterface {
            public ConfirmWidget $widget;

            public string $title = 'Before';

            public function __construct()
            {
                $this->widget = new ConfirmWidget('Old body');
            }

            public function title(): string
            {
                return $this->title;
            }

            public function hints(): string
            {
                return '';
            }

            public function widget(): AbstractWidget&FocusableInterface
            {
                return $this->widget;
            }

            public function resume(mixed $result): void
            {
            }
        };

        $harness->open($screen);
        self::assertSame('Old body', $harness->lines()[2]);

        $screen->widget = new ConfirmWidget('New body');
        $screen->title = 'After';
        $harness->navigator->refresh();
        $harness->render();

        self::assertSame('After', $harness->lines()[0]);
        self::assertSame('New body', $harness->lines()[2]);
        self::assertSame($screen->widget, $harness->tui->getFocus());
    }

    public function testABlockingWorkIsAnnouncedOnTheScreenBeforeItRunsAndItsResultIsReturned(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->open($this->screen('one'));

        $during = null;
        $result = $harness->navigator->busy('Loading…', static function () use ($harness, &$during): string {
            $during = $harness->lines()[6];

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertSame('Loading…', $during);
        self::assertSame('', $harness->navigator->statusText());
    }

    public function testTheMessageOfABlockingWorkIsRemovedEvenWhenItFails(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->open($this->screen('one'));

        try {
            $harness->navigator->busy('Loading…', static function (): never {
                throw new RuntimeException('failure');
            });
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertSame('failure', $error->getMessage());
        }

        self::assertSame('', $harness->navigator->statusText());
    }

    public function testABlockingWorkRunsAlsoBeforeTheInterfaceIsStarted(): void
    {
        $harness = new TuiHarness(40, 8);

        self::assertSame(3, $harness->navigator->busy('Loading…', static fn (): int => 3));
        self::assertFalse($harness->tui->isRunning());
        self::assertSame('', $harness->terminal->getOutput());
    }
}
