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
use Symfony\Component\Tui\Input\Keybindings;
use Symfony\Component\Tui\Terminal\ScreenBuffer;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\Navigator;
use Teknoo\East\Website\Tools\Tui\Screen\ScreenInterface;
use Teknoo\East\Website\Tools\Tui\Session;

use function array_key_last;
use function array_pop;
use function explode;
use function implode;
use function rtrim;

/**
 * An interface of the interactive mode on a virtual terminal, to test a widget or a screen alone: the keys are sent
 * one by one, the screen is read as the lines displayed by the terminal. The screens use the API of an ApiStub.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TuiHarness
{
    public readonly VirtualTerminal $terminal;

    public readonly Tui $tui;

    public readonly Navigator $navigator;

    public readonly ApiStub $api;

    public readonly Session $session;

    public function __construct(
        private readonly int $columns = 80,
        private readonly int $rows = 16,
    ) {
        $this->terminal = new VirtualTerminal($columns, $rows);
        // Like the launcher: the keys moving the focus between the widgets are disabled
        $this->tui = new Tui(
            terminal: $this->terminal,
            keybindings: new Keybindings(['focus_next' => [], 'focus_previous' => []]),
        );
        $this->navigator = new Navigator($this->tui);
        $this->api = new ApiStub();
        $this->session = new Session(
            $this->api->connection,
            $this->api->gateway,
            new Registry(),
            new PayloadBuilder(),
            $this->navigator,
        );
    }

    public function __destruct()
    {
        $this->tui->stop();
    }

    public function definition(string $name): ResourceDefinition
    {
        return $this->session->registry->resource($name)
            ?? throw new LogicException('Unknown resource ' . $name);
    }

    /**
     * Displays a widget alone, with the focus.
     */
    public function mount(AbstractWidget $widget): static
    {
        $this->tui->clear();
        $this->tui->add($widget);
        $this->tui->setFocus($widget);

        return $this->render();
    }

    /**
     * Opens a screen on the stack of the navigator.
     */
    public function open(ScreenInterface $screen): static
    {
        $this->navigator->push($screen);

        return $this->render();
    }

    public function keys(string ...$keys): static
    {
        foreach ($keys as $key) {
            $this->terminal->simulateInput($key);
            $this->render();
        }

        return $this;
    }

    public function type(string $text): static
    {
        return $this->keys(...Keys::text($text));
    }

    public function render(): static
    {
        if (!$this->navigator->isStopped()) {
            if (!$this->tui->isRunning()) {
                $this->tui->start();
            }

            $this->tui->requestRender();
            $this->tui->processRender();
        }

        return $this;
    }

    /**
     * @return list<string> the lines of the screen without their trailing spaces, nor the empty lines at its end
     */
    public function lines(): array
    {
        $buffer = new ScreenBuffer($this->columns, $this->rows);
        $buffer->write($this->terminal->getOutput());

        $lines = [];
        foreach (explode("\n", $buffer->getScreen()) as $line) {
            $lines[] = rtrim($line);
        }

        while ([] !== $lines && '' === $lines[array_key_last($lines)]) {
            array_pop($lines);
        }

        return $lines;
    }

    public function screen(): string
    {
        return implode("\n", $this->lines());
    }
}
