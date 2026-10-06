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

use RuntimeException;

use function array_map;
use function array_merge;
use function escapeshellarg;
use function explode;
use function fclose;
use function feof;
use function fread;
use function fwrite;
use function getenv;
use function implode;
use function is_executable;
use function is_resource;
use function microtime;
use function proc_close;
use function proc_open;
use function str_contains;
use function stream_get_contents;
use function stream_select;

use const PATH_SEPARATOR;
use const PHP_BINARY;

/**
 * Runs the CLI (the script of the sources or the phar) in a real PHP process, in a working directory where the login
 * writes its configuration file, and without any variable of the environment of the tests.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Cli
{
    public function __construct(
        private readonly string $binary,
        private readonly string $workingDirectory,
    ) {
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string, string} exit code, stdout, stderr
     */
    public function run(array $arguments, ?string $stdin = null): array
    {
        $process = proc_open(
            array_merge([PHP_BINARY, $this->binary], $arguments),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->workingDirectory,
            [
                'PATH' => (string) getenv('PATH'),
                'XDEBUG_MODE' => 'off',
            ],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('The CLI can not be started');
        }

        if (null !== $stdin) {
            fwrite($pipes[0], $stdin);
        }

        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    /**
     * True when the CLI can be run in a pseudo terminal: script (util-linux) and timeout are needed.
     */
    public static function hasTerminal(): bool
    {
        return self::isExecutable('script') && self::isExecutable('timeout');
    }

    /**
     * Runs the CLI in a pseudo terminal, so its standard input and output are a terminal like in a shell. Each
     * step waits for a text on the terminal, then types its keys: the keys can not be typed before the interface is
     * displayed, the terminal is not yet in raw mode (Ctrl+C would kill the process). The run is stopped after the
     * timeout: a scenario which does not quit can not block the tests.
     *
     * @param list<string> $arguments
     * @param list<array{string, string}> $steps the text to wait for, then the keys to type
     * @return array{int, string} exit code, and everything displayed on the terminal
     */
    public function runInTerminal(array $arguments, array $steps, int $timeout = 30): array
    {
        $command = implode(' ', array_map(escapeshellarg(...), [PHP_BINARY, $this->binary, ...$arguments]));

        $process = proc_open(
            ['timeout', (string) $timeout, 'script', '-qec', $command, '/dev/null'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w']],
            $pipes,
            $this->workingDirectory,
            [
                'PATH' => (string) getenv('PATH'),
                'XDEBUG_MODE' => 'off',
                'TERM' => 'xterm',
            ],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('The CLI can not be started in a terminal');
        }

        $display = '';
        $deadline = microtime(true) + $timeout;
        $read = static function () use ($pipes, &$display, $deadline): bool {
            $streams = [$pipes[1]];
            $write = null;
            $except = null;
            if (microtime(true) > $deadline || false === stream_select($streams, $write, $except, 1)) {
                return false;
            }

            if ([] === $streams) {
                return true;
            }

            $chunk = fread($pipes[1], 8192);
            if (false === $chunk || ('' === $chunk && feof($pipes[1]))) {
                return false;
            }

            $display .= $chunk;

            return true;
        };

        foreach ($steps as [$expected, $keys]) {
            while (!str_contains($display, $expected)) {
                if (!$read()) {
                    break 2;
                }
            }

            fwrite($pipes[0], $keys);
        }

        // Until the process exits and closes the terminal
        while ($read()) {
            continue;
        }

        fclose($pipes[0]);
        fclose($pipes[1]);

        return [proc_close($process), $display];
    }

    private static function isExecutable(string $name): bool
    {
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            if ('' !== $directory && is_executable($directory . '/' . $name)) {
                return true;
            }
        }

        return false;
    }
}
