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

use function array_merge;
use function fclose;
use function fwrite;
use function getenv;
use function is_resource;
use function proc_close;
use function proc_open;
use function stream_get_contents;

use const PHP_BINARY;

/**
 * Runs the CLI (the script of the sources or the phar) in a real PHP process.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Cli
{
    public function __construct(
        private readonly string $binary,
        private readonly string $url,
        private readonly string $sessionFile,
    ) {
    }

    /**
     * @param list<string> $arguments
     * @param array<string, string> $env
     * @return array{int, string, string} exit code, stdout, stderr
     */
    public function run(array $arguments, array $env = [], ?string $stdin = null): array
    {
        $process = proc_open(
            array_merge([PHP_BINARY, $this->binary], $arguments),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env + [
                'PATH' => (string) getenv('PATH'),
                'XDEBUG_MODE' => 'off',
                'EAST_WEBSITE_URL' => $this->url,
                'EAST_WEBSITE_SESSION_FILE' => $this->sessionFile,
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
}
