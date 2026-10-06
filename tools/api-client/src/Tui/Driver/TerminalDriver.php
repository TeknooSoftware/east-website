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

namespace Teknoo\East\Website\Tools\Tui\Driver;

use Revolt\EventLoop\UncaughtThrowable;
use Symfony\Component\Tui\Terminal\Terminal;
use Symfony\Component\Tui\Terminal\TerminalInterface;
use Symfony\Component\Tui\Tui;
use Teknoo\East\Website\Tools\Http\ApiException;

use function defined;
use function register_shutdown_function;
use function stream_isatty;

use const STDIN;
use const STDOUT;

/**
 * The real terminal. The TUI component writes directly on the standard output and reads the standard input,
 * without checking them: both must be a terminal, or the escape sequences would be sent to a pipe or a file.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TerminalDriver implements DriverInterface
{
    protected function isInteractive(): bool
    {
        return defined('STDIN') && defined('STDOUT') && stream_isatty(STDIN) && stream_isatty(STDOUT);
    }

    public function assertInteractive(): void
    {
        if (!$this->isInteractive()) {
            throw ApiException::usage(
                'The format "tui" needs an interactive terminal on the standard input and on the standard output, '
                . 'use --format=json or --format=table instead'
            );
        }
    }

    public function terminal(): TerminalInterface
    {
        return new Terminal();
    }

    public function loop(Tui $tui): void
    {
        // The terminal is in raw mode while the interface runs: it must be restored even after a fatal error,
        // which is not caught by the "finally" of the interface
        register_shutdown_function($this->restore(...), $tui);

        try {
            $tui->run();
        } catch (UncaughtThrowable $error) {
            // The event loop wraps the exceptions of the handlers: the original one follows the usual contract
            throw $error->getPrevious() ?? $error;
        }
    }

    /**
     * Stops an interface still running, to give back the terminal in its initial mode.
     */
    public function restore(Tui $tui): void
    {
        if ($tui->isRunning()) {
            $tui->stop();
        }
    }
}
