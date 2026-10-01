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

use function file_put_contents;

/**
 * Tests of the logout: only the local session is deleted, the API has no logout endpoint
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LogoutCommand::class)]
class LogoutCommandTest extends TestCase
{
    private const string SESSION = '{"version":1,"baseUrl":"https://site.test","username":"key:me@site.test","token":"jwt","expiresAt":1800003600}';

    public function testTheSessionFileIsDeleted(): void
    {
        $harness = new ApiHarness();
        $path = $harness->temp()->path('session.json');
        file_put_contents($path, self::SESSION);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame(
            ['meta' => ['error' => false], 'data' => ['sessionFile' => $path, 'deleted' => true]],
            AbstractCommandTest::decode($stdout),
        );
        self::assertFileDoesNotExist($path);
        self::assertSame([], $harness->requests);
    }

    public function testNothingToDelete(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(
            ['meta' => ['error' => false], 'data' => ['sessionFile' => $harness->temp()->path('session.json'), 'deleted' => false]],
            AbstractCommandTest::decode($stdout),
        );
    }

    public function testNoSessionOptionKeepsTheFile(): void
    {
        $harness = new ApiHarness();
        $path = $harness->temp()->path('session.json');
        file_put_contents($path, self::SESSION);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--no-session', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(['sessionFile' => null, 'deleted' => false], AbstractCommandTest::decode($stdout)['data']);
        self::assertFileExists($path);
    }

    public function testTheSessionFileOptionOverridesTheEnvironment(): void
    {
        $harness = new ApiHarness();
        $environment = $harness->temp()->path('session.json');
        $chosen = $harness->temp()->write('chosen/session.json', self::SESSION);
        file_put_contents($environment, self::SESSION);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--session-file=' . $chosen, '--compact']);

        self::assertSame(0, $code);
        self::assertSame(['sessionFile' => $chosen, 'deleted' => true], AbstractCommandTest::decode($stdout)['data']);
        self::assertFileDoesNotExist($chosen);
        self::assertFileExists($environment);
    }

    public function testNoSessionPathCanBeDetermined(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_SESSION_FILE' => '']);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(['sessionFile' => null, 'deleted' => false], AbstractCommandTest::decode($stdout)['data']);
    }

    public function testTheTableFormat(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:logout', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('| deleted', $stdout);
        self::assertStringContainsString('false', $stdout);
    }
}
