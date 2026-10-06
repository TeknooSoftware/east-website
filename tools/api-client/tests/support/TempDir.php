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

use function array_diff;
use function bin2hex;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;

/**
 * Temporary directory of a test, removed with the test.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TempDir
{
    private readonly string $path;

    public function __construct()
    {
        $this->path = sys_get_temp_dir() . '/east-website-cli-tests-' . bin2hex(random_bytes(6));
        mkdir($this->path, 0700, true);
    }

    public function path(string $relative = ''): string
    {
        return '' === $relative ? $this->path : $this->path . '/' . $relative;
    }

    public function write(string $relative, string $content): string
    {
        $file = $this->path($relative);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0700, true);
        }

        file_put_contents($file, $content);

        return $file;
    }

    public function remove(): void
    {
        $this->delete($this->path);
    }

    private function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $this->delete($path . '/' . $entry);
        }

        rmdir($path);
    }
}
