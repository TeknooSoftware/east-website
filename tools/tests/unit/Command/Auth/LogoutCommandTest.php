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

namespace Teknoo\Tests\East\Website\Tools\Command\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Command\Auth\LogoutCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function mkdir;

/**
 * Tests of the logout: the configuration file written by the login is deleted, the next commands have no JWT anymore
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LogoutCommand::class)]
class LogoutCommandTest extends TestCase
{
    private const array CONFIG = [
        'username' => 'key:me@site.test',
        'apiKey' => 'api-key-value',
        'token' => 'config-secret-token',
        'expiresAt' => 1_800_003_600,
    ];

    public function testTheConfigurationFileIsDeleted(): void
    {
        $harness = new ApiHarness(self::CONFIG);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);
        self::assertSame(
            ['meta' => ['error' => false], 'data' => ['configFile' => $harness->configPath(), 'deleted' => true]],
            AbstractCommandTest::decode($stdout),
        );
        self::assertFileDoesNotExist($harness->configPath());
        self::assertSame([], $harness->requests, 'There is no logout endpoint on the server');
        self::assertStringNotContainsString('config-secret-token', $stdout);
        self::assertStringNotContainsString('api-key-value', $stdout);
    }

    public function testAfterTheLogoutTheCommandsHaveNoJwt(): void
    {
        $harness = new ApiHarness(self::CONFIG);

        AbstractCommandTest::execute($harness, ['website:auth:logout']);
        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:list']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertStringContainsString('website:auth:login', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);
    }

    public function testNothingToDelete(): void
    {
        $harness = new ApiHarness(null);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(
            ['meta' => ['error' => false], 'data' => ['configFile' => $harness->configPath(), 'deleted' => false]],
            AbstractCommandTest::decode($stdout),
        );
    }

    public function testADirectoryIsNeverDeleted(): void
    {
        $harness = new ApiHarness(null);
        mkdir($harness->configPath(), 0700);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--compact']);

        self::assertSame(0, $code);
        self::assertFalse(AbstractCommandTest::decode($stdout)['data']['deleted']);
        self::assertDirectoryExists($harness->configPath());
    }

    public function testTheConfigurationFileCanBeChosenWithAnOption(): void
    {
        $harness = new ApiHarness(self::CONFIG);
        $harness->writeConfig(self::CONFIG, 'other.json');

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--config=other.json', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(
            ['configFile' => $harness->configPath('other.json'), 'deleted' => true],
            AbstractCommandTest::decode($stdout)['data'],
        );
        self::assertFileDoesNotExist($harness->configPath('other.json'));
        self::assertFileExists($harness->configPath(), 'Only the chosen file is deleted');
    }

    public function testTheTableFormat(): void
    {
        $harness = new ApiHarness(self::CONFIG);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('deleted', $stdout);
        self::assertStringContainsString($harness->configPath(), $stdout);
    }
}
