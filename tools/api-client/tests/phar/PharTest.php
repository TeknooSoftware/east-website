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

namespace Teknoo\Tests\East\Website\Tools\Phar;

use Phar;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use Teknoo\Tests\East\Website\Tools\Support\Cli;
use Teknoo\Tests\East\Website\Tools\Support\CliScenariosTrait;

use function file_exists;
use function json_decode;
use function str_contains;

/**
 * Tests of the built phar (make phar): the same scenarios than the sources, plus the content of the archive
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversNothing]
class PharTest extends TestCase
{
    use CliScenariosTrait {
        setUp as private setUpScenarios;
    }

    private const string PHAR = __DIR__ . '/../../dist/east-website.phar';

    protected function binary(): string
    {
        return self::PHAR;
    }

    protected function setUp(): void
    {
        if (!file_exists(self::PHAR)) {
            self::markTestSkipped('The phar is not built, run make phar');
        }

        $this->setUpScenarios();
    }

    public function testVersion(): void
    {
        [$code, $stdout] = $this->cli->run(['--version']);

        self::assertSame(0, $code);
        self::assertStringContainsString('East Website CLI', $stdout);
    }

    public function testDryRunOfACreation(): void
    {
        [$code, $stdout] = $this->cli->run(['website:tag:create', '--name=x', '--dry-run', '--compact']);
        $request = json_decode($stdout, true)['requests'][0];

        self::assertSame(0, $code);
        self::assertSame('POST', $request['method']);
        self::assertStringEndsWith('/api/v1/admin/tag/new', $request['url']);
        self::assertSame(['name' => 'x'], $request['body']);
        self::assertSame('Bearer ***', $request['headers']['Authorization']);
    }

    public function testThePharEmbedsNoDevelopmentDependency(): void
    {
        $phar = new Phar(self::PHAR);
        $paths = [];
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $paths[] = (string) $file;
        }

        self::assertNotEmpty($paths);
        self::assertTrue(\count(\array_filter($paths, static fn (string $path): bool => str_contains($path, '/symfony/console/Application.php'))) > 0);
        self::assertTrue(\count(\array_filter($paths, static fn (string $path): bool => str_contains($path, '/src/Application.php'))) > 0);

        foreach (['/phpunit/', '/phpstan/', '/squizlabs/', '/tests/unit/', '/.phpunit.cache'] as $forbidden) {
            self::assertSame(
                [],
                \array_values(\array_filter($paths, static fn (string $path): bool => str_contains($path, $forbidden))),
                'The phar must not embed ' . $forbidden,
            );
        }
    }
}
