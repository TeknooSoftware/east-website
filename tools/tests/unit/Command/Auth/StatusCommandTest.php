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

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Command\Auth\StatusCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function file_put_contents;

/**
 * Tests of the authentication state: offline, and without any secret
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(StatusCommand::class)]
class StatusCommandTest extends TestCase
{
    private const int VALID_EXPIRATION = 1_800_003_600;

    private function session(ApiHarness $harness, int $expiresAt = self::VALID_EXPIRATION, string $baseUrl = 'https://site.test'): string
    {
        $path = $harness->temp()->path('session.json');
        file_put_contents(
            $path,
            '{"version":1,"baseUrl":"' . $baseUrl . '","username":"key:me@site.test","token":"session-secret-token","expiresAt":' . $expiresAt . '}',
        );

        return $path;
    }

    /**
     * @return array<mixed>
     */
    private function statusOf(ApiHarness $harness, string ...$options): array
    {
        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:status', '--compact', ...$options]);

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);
        self::assertSame([], $harness->requests, 'The status must not use the network');
        self::assertStringNotContainsString('session-secret-token', $stdout);
        self::assertStringNotContainsString('super-secret-token', $stdout);
        self::assertStringNotContainsString('api-key-value', $stdout);

        return AbstractCommandTest::decode($stdout);
    }

    public function testNothingIsConfigured(): void
    {
        $harness = new ApiHarness();

        $document = $this->statusOf($harness);

        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'baseUrl' => 'https://site.test',
                    'username' => null,
                    'authentication' => 'none',
                    'hasApiKey' => false,
                    'sessionFile' => $harness->temp()->path('session.json'),
                    'session' => null,
                ],
            ],
            $document,
        );
    }

    public function testAnExplicitTokenIsUsedAsIs(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_TOKEN' => 'super-secret-token']);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('token', $data['authentication']);
        self::assertFalse($data['hasApiKey']);
    }

    public function testAValidSessionIsReused(): void
    {
        $harness = new ApiHarness();
        $this->session($harness);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('session', $data['authentication']);
        self::assertSame('key:me@site.test', $data['username']);
        self::assertSame(
            [
                'expiresAt' => (new DateTimeImmutable('@' . self::VALID_EXPIRATION))->format(DateTimeInterface::ATOM),
                'expired' => false,
            ],
            $data['session'],
        );
    }

    public function testASessionAboutToExpireIsConsideredExpired(): void
    {
        $harness = new ApiHarness();
        $this->session($harness, 1_800_000_010);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('none', $data['authentication']);
        self::assertTrue($data['session']['expired']);
    }

    public function testAnExpiredSessionGivesALoginWhenTheCredentialsAreAvailable(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_USERNAME' => 'key:me@site.test', 'EAST_WEBSITE_API_KEY' => 'api-key-value']);
        $this->session($harness, 1_700_000_000);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('login', $data['authentication']);
        self::assertTrue($data['hasApiKey']);
        self::assertTrue($data['session']['expired']);
    }

    public function testTheCredentialsGiveALoginOnTheNextCall(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_USERNAME' => 'key:me@site.test', 'EAST_WEBSITE_API_KEY' => 'api-key-value']);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('login', $data['authentication']);
        self::assertSame('key:me@site.test', $data['username']);
        self::assertTrue($data['hasApiKey']);
        self::assertNull($data['session']);
    }

    public function testAnApiKeyWithoutUsernameIsNotEnoughToLogin(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_API_KEY' => 'api-key-value']);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('none', $data['authentication']);
        self::assertTrue($data['hasApiKey']);
    }

    public function testAnonymousOptionWins(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_TOKEN' => 'super-secret-token']);
        $this->session($harness);

        $data = $this->statusOf($harness, '--anonymous')['data'];

        self::assertSame('anonymous', $data['authentication']);
    }

    public function testTheBaseUrlIsTakenFromTheSessionWhenNotConfigured(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_URL' => '']);
        $this->session($harness, baseUrl: 'https://other.test');

        $data = $this->statusOf($harness)['data'];

        self::assertSame('https://other.test', $data['baseUrl']);
        self::assertSame('session', $data['authentication']);
    }

    public function testNoBaseUrlAtAll(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_URL' => '']);

        $data = $this->statusOf($harness)['data'];

        self::assertNull($data['baseUrl']);
        self::assertSame('none', $data['authentication']);
    }

    public function testASessionOfAnotherServerIsIgnored(): void
    {
        $harness = new ApiHarness();
        $this->session($harness, baseUrl: 'https://other.test');

        $data = $this->statusOf($harness)['data'];

        self::assertSame('none', $data['authentication']);
        self::assertNull($data['session']);
    }

    public function testASessionOfAnotherUserIsIgnored(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_USERNAME' => 'someone:else@site.test']);
        $this->session($harness);

        $data = $this->statusOf($harness)['data'];

        self::assertNull($data['session']);
        self::assertSame('someone:else@site.test', $data['username']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function brokenSessions(): iterable
    {
        yield 'not json' => ['not json at all'];
        yield 'not an object' => ['"text"'];
        yield 'unknown version' => ['{"version":2,"baseUrl":"https://site.test","username":"u","token":"t","expiresAt":1}'];
        yield 'missing token' => ['{"version":1,"baseUrl":"https://site.test","username":"u","expiresAt":1}'];
        yield 'empty token' => ['{"version":1,"baseUrl":"https://site.test","username":"u","token":"","expiresAt":1}'];
        yield 'wrong types' => ['{"version":1,"baseUrl":1,"username":[],"token":true}'];
        yield 'empty file' => [''];
    }

    #[DataProvider('brokenSessions')]
    public function testABrokenSessionFileIsTheSameAsNoSession(string $content): void
    {
        $harness = new ApiHarness();
        file_put_contents($harness->temp()->path('session.json'), $content);

        $data = $this->statusOf($harness)['data'];

        self::assertNull($data['session']);
        self::assertSame('none', $data['authentication']);
    }

    public function testASessionWithoutExpirationIsValid(): void
    {
        $harness = new ApiHarness();
        file_put_contents(
            $harness->temp()->path('session.json'),
            '{"version":1,"baseUrl":"https://site.test","username":"key:me@site.test","token":"session-secret-token"}',
        );

        $data = $this->statusOf($harness)['data'];

        self::assertSame('session', $data['authentication']);
        self::assertSame(['expiresAt' => null, 'expired' => false], $data['session']);
    }

    public function testNoSessionOptionHidesTheSessionFile(): void
    {
        $harness = new ApiHarness();
        $this->session($harness);

        $data = $this->statusOf($harness, '--no-session')['data'];

        self::assertNull($data['sessionFile']);
        self::assertNull($data['session']);
        self::assertSame('none', $data['authentication']);
    }

    public function testTheSessionFileCanBeChosenWithAnOption(): void
    {
        $harness = new ApiHarness();
        $path = $harness->temp()->write('other/session.json', '{"version":1,"baseUrl":"https://site.test","username":"key:me@site.test","token":"session-secret-token","expiresAt":' . self::VALID_EXPIRATION . '}');

        $data = $this->statusOf($harness, '--session-file=' . $path)['data'];

        self::assertSame($path, $data['sessionFile']);
        self::assertSame('session', $data['authentication']);
    }

    public function testNoSessionPathCanBeDetermined(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_SESSION_FILE' => '']);

        $data = $this->statusOf($harness)['data'];

        self::assertNull($data['sessionFile']);
    }

    public function testTheDefaultSessionPathFollowsTheXdgStateDirectory(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_SESSION_FILE' => '', 'XDG_STATE_HOME' => '/var/state/']);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('/var/state/east-website-cli/session.json', $data['sessionFile']);
    }

    public function testTheDefaultSessionPathFallsBackToTheHomeDirectory(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_SESSION_FILE' => '', 'HOME' => '/home/jane']);

        $data = $this->statusOf($harness)['data'];

        self::assertSame('/home/jane/.local/state/east-website-cli/session.json', $data['sessionFile']);
    }

    public function testTheTableFormat(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_TOKEN' => 'super-secret-token']);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:status', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('| authentication | token', $stdout);
        self::assertStringNotContainsString('super-secret-token', $stdout);
    }
}
