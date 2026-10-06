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

namespace Teknoo\Tests\East\Website\Tools\Tui\Driver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Symfony\Component\Tui\Event\InputEvent;
use Symfony\Component\Tui\Terminal\Terminal;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Tui\Driver\TerminalDriver;

use const STDIN;
use const STDOUT;

/**
 * Tests of the driver of the real terminal of the interactive mode
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TerminalDriver::class)]
class TerminalDriverTest extends TestCase
{
    private function driver(bool $interactive): TerminalDriver
    {
        return new class ($interactive) extends TerminalDriver {
            public function __construct(
                private readonly bool $interactive,
            ) {
            }

            protected function isInteractive(): bool
            {
                return $this->interactive;
            }
        };
    }

    public function testNothingIsOpenedWithoutAnInteractiveTerminal(): void
    {
        try {
            $this->driver(false)->assertInteractive();
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('needs an interactive terminal', $error->getMessage());
            self::assertStringContainsString('--format=json', $error->getMessage());
        }
    }

    public function testAnInteractiveTerminalIsAccepted(): void
    {
        $this->driver(true)->assertInteractive();

        $this->expectNotToPerformAssertions();
    }

    public function testTheStandardInputAndOutputMustBothBeATerminal(): void
    {
        // The tests can be run from a terminal or not: the driver must agree with the state of the streams
        $expected = stream_isatty(STDIN) && stream_isatty(STDOUT);

        try {
            (new TerminalDriver())->assertInteractive();
            $accepted = true;
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            $accepted = false;
        }

        self::assertSame($expected, $accepted);
    }

    public function testItGivesTheRealTerminal(): void
    {
        self::assertInstanceOf(Terminal::class, (new TerminalDriver())->terminal());
    }

    public function testTheLoopRunsUntilTheInterfaceIsStopped(): void
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);
        $received = [];
        $tui->addListener(static function (InputEvent $event) use ($tui, &$received): void {
            $received[] = $event->getData();
            if ('q' === $event->getData()) {
                $tui->stop();
            }
        });

        EventLoop::defer(static function () use ($terminal): void {
            $terminal->simulateInput('a');
            $terminal->simulateInput('q');
        });

        (new TerminalDriver())->loop($tui);

        self::assertSame(['a', 'q'], $received);
        self::assertFalse($tui->isRunning());
    }

    public function testAnErrorOfAHandlerLeavesTheLoopAsItIsAfterTheTerminalIsRestored(): void
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);
        $failure = new ApiException('The API is down', ErrorKind::Server, 503);
        $tui->addListener(static function (InputEvent $event) use ($failure): void {
            throw $failure;
        });

        EventLoop::defer(static function () use ($terminal): void {
            $terminal->simulateInput('a');
        });

        try {
            (new TerminalDriver())->loop($tui);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame($failure, $error);
        }

        self::assertFalse($tui->isRunning());
    }

    public function testAnInterfaceStillRunningIsStoppedToRestoreTheTerminal(): void
    {
        $tui = new Tui(terminal: new VirtualTerminal(40, 10));
        $driver = new TerminalDriver();

        $tui->start();
        self::assertTrue($tui->isRunning());

        $driver->restore($tui);
        self::assertFalse($tui->isRunning());

        $driver->restore($tui);
        self::assertFalse($tui->isRunning());
    }
}
