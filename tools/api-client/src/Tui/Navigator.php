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

namespace Teknoo\East\Website\Tools\Tui;

use Symfony\Component\Tui\Tui;
use Teknoo\East\Website\Tools\Tui\Screen\ScreenInterface;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;
use Teknoo\East\Website\Tools\Tui\Widget\LineWidget;

use function array_key_last;
use function array_pop;
use function count;

/**
 * Stack of the screens of the interactive mode. The displayed screen is the last one, between a title, a status
 * line and the key hints; the interface stops when the first screen is closed. Each screen fits in the height of
 * the terminal: the title and the two last lines are one line high, the screen gets all the remaining lines.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Navigator
{
    /**
     * @var list<ScreenInterface>
     */
    private array $stack = [];

    private readonly LineWidget $title;

    private readonly LineWidget $status;

    private readonly LineWidget $hints;

    private bool $stopped = false;

    public function __construct(
        private readonly Tui $tui,
    ) {
        $this->title = new LineWidget();
        $this->status = new LineWidget();
        $this->hints = new LineWidget();
    }

    public function push(ScreenInterface $screen): void
    {
        $this->stack[] = $screen;
        $this->mount();
    }

    /**
     * Closes the displayed screen and gives its result to the previous one, or stops the interface when it was the
     * first one.
     */
    public function pop(mixed $result = null): void
    {
        array_pop($this->stack);

        $current = $this->current();
        if (null === $current) {
            $this->quit();

            return;
        }

        $current->resume($result);

        // The resumed screen may have opened another one, already displayed
        if ($current === $this->current()) {
            $this->mount();
        }
    }

    /**
     * Displays again the current screen, when its widget, its title or its hints were replaced.
     */
    public function refresh(): void
    {
        $this->mount();
    }

    public function quit(): void
    {
        $this->stopped = true;
        $this->tui->stop();
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function depth(): int
    {
        return count($this->stack);
    }

    public function current(): ?ScreenInterface
    {
        $last = array_key_last($this->stack);

        return null !== $last ? $this->stack[$last] : null;
    }

    public function status(string $message, bool $error = false): void
    {
        $message = CellFormatter::clean($message);
        $this->status->setText($error && '' !== $message ? Ansi::error($message) : $message);

        if (!$this->stopped) {
            $this->tui->requestRender();
        }
    }

    public function statusText(): string
    {
        return $this->status->getText();
    }

    /**
     * Runs a blocking work (a request to the API) after having displayed a message: the interface is frozen while
     * the work runs, the message tells why.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function busy(string $message, callable $work): mixed
    {
        $this->status($message);

        if ($this->tui->isRunning()) {
            $this->tui->processRender();
        }

        try {
            return $work();
        } finally {
            $this->status('');
        }
    }

    private function mount(): void
    {
        $screen = $this->current();
        if (null === $screen || $this->stopped) {
            return;
        }

        $body = $screen->widget();
        $this->title->setText(Ansi::bold(CellFormatter::clean($screen->title())));
        $this->hints->setText(Ansi::dim($screen->hints()));

        $this->tui->clear();
        $this->tui->add($this->title);
        $this->tui->add($body);
        $this->tui->add($this->status);
        $this->tui->add($this->hints);
        // The focus manager gives the focus to the first focusable widget it knows: the screen is the only target
        $this->tui->setFocus($body);
        $this->tui->requestRender();
    }
}
