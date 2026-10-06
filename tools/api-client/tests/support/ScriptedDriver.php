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

use LogicException;
use Symfony\Component\Tui\Terminal\ScreenBuffer;
use Symfony\Component\Tui\Terminal\TerminalInterface;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Teknoo\East\Website\Tools\Tui\Driver\TerminalDriver;

use function array_key_last;
use function array_slice;
use function count;
use function sprintf;

/**
 * Driver of the interactive mode for the tests: a virtual terminal and a script of keys, played without any event
 * loop. A wrong script can not block the tests: the interface must be stopped by the last key, and all the keys must
 * be used, or the run fails with the last screen.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ScriptedDriver extends TerminalDriver
{
    public readonly VirtualTerminal $terminal;

    /**
     * @var list<string> the screen at the start, then after each key
     */
    public array $screens = [];

    public int $runs = 0;

    /**
     * @param list<string> $keys
     */
    public function __construct(
        private readonly array $keys = [],
        private readonly bool $interactive = true,
        private readonly int $columns = 80,
        private readonly int $rows = 24,
    ) {
        $this->terminal = new VirtualTerminal($columns, $rows);
    }

    protected function isInteractive(): bool
    {
        return $this->interactive;
    }

    public function terminal(): TerminalInterface
    {
        return $this->terminal;
    }

    public function loop(Tui $tui): void
    {
        ++$this->runs;
        $played = 0;

        try {
            $tui->start();
            $tui->processRender();
            $this->capture();

            foreach ($this->keys as $key) {
                if (!$tui->isRunning()) {
                    break;
                }

                $this->terminal->simulateInput($key);
                ++$played;

                if ($tui->isRunning()) {
                    $tui->processRender();
                    $this->capture();
                }
            }

            if ($tui->isRunning()) {
                throw new LogicException("The script is over but the interface still runs, on the screen:\n" . $this->screen());
            }

            if ($played < count($this->keys)) {
                throw new LogicException(sprintf(
                    "The interface was stopped by the key #%d, %d key(s) were not used, last screen:\n%s",
                    $played,
                    count(array_slice($this->keys, $played)),
                    $this->screen(),
                ));
            }
        } finally {
            $tui->stop();
        }
    }

    /**
     * @return string the last screen displayed before the interface was stopped
     */
    public function screen(): string
    {
        $last = array_key_last($this->screens);

        return null !== $last ? $this->screens[$last] : '';
    }

    private function capture(): void
    {
        $buffer = new ScreenBuffer($this->columns, $this->rows);
        $buffer->write($this->terminal->getOutput());

        $this->screens[] = $buffer->getScreen();
    }
}
