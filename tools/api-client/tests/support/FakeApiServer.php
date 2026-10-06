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

use function escapeshellarg;
use function explode;
use function fclose;
use function file;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_resource;
use function json_decode;
use function microtime;
use function proc_close;
use function proc_open;
use function proc_terminate;
use function getenv;
use function str_contains;
use function stream_socket_get_name;
use function stream_socket_server;
use function usleep;

use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;
use const PHP_BINARY;

/**
 * Fake East Website API served by the built-in server of PHP (tests/support/fake-api/router.php), to test the CLI with the real HTTP client of Symfony (curl). It records each received request.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FakeApiServer
{
    /**
     * @var resource|null
     */
    private $process;

    private function __construct(
        public readonly string $url,
        private readonly string $log,
    ) {
    }

    /**
     * @throws RuntimeException when the server can not be started, the tests using it are then skipped
     */
    public static function start(TempDir $temp): self
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if (false === $socket) {
            throw new RuntimeException('No free port: ' . $errorMessage);
        }

        $port = explode(':', (string) stream_socket_get_name($socket, false))[1];
        fclose($socket);

        $log = $temp->path('fake-api.log');
        $output = $temp->path('fake-api.out');
        $server = new self('http://127.0.0.1:' . $port, $log);
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/fake-api/router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $output, 'a'], 2 => ['file', $output, 'a']],
            $pipes,
            null,
            ['FAKE_API_LOG' => $log, 'XDEBUG_MODE' => 'off', 'PATH' => (string) getenv('PATH')],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('The PHP built-in server can not be started');
        }

        $server->process = $process;
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if (file_exists($output) && str_contains((string) file_get_contents($output), 'started')) {
                return $server;
            }

            usleep(50_000);
        }

        $server->stop();

        throw new RuntimeException('The PHP built-in server did not start');
    }

    /**
     * @return list<array{method: string, path: string, query: string, headers: array<string, string>, contentType: string, body: string, post: array<string, mixed>, files: array<string, mixed>}>
     */
    public function requests(): array
    {
        if (!file_exists($this->log)) {
            return [];
        }

        $requests = [];
        foreach (file($this->log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $request = json_decode($line, true);
            if (is_array($request)) {
                $requests[] = $request;
            }
        }

        return $requests;
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
    }

    public function __destruct()
    {
        $this->stop();
    }
}
