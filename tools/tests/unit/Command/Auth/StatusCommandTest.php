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
 * Tests of the display of the configuration file written by the login: offline, and without any secret
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(StatusCommand::class)]
class StatusCommandTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    private const int VALID_EXPIRATION = 1_800_003_600;

    private const array CONFIG = [
        'username' => 'key:me@site.test',
        'apiKey' => 'api-key-value',
        'token' => 'config-secret-token',
        'expiresAt' => self::VALID_EXPIRATION,
    ];

    /**
     * @return array<mixed>
     */
    private function statusOf(ApiHarness $harness, string ...$options): array
    {
        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:status', '--compact', ...$options]);

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);
        self::assertSame([], $harness->requests, 'The status must not use the network');
        self::assertStringNotContainsString('config-secret-token', $stdout);
        self::assertStringNotContainsString('api-key-value', $stdout);

        return AbstractCommandTest::decode($stdout);
    }

    private static function date(int $timestamp): string
    {
        return (new DateTimeImmutable('@' . $timestamp))->format(DateTimeInterface::ATOM);
    }

    public function testWithoutConfigurationFileNothingIsConfigured(): void
    {
        $harness = new ApiHarness(null);

        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'configFile' => $harness->configPath(),
                    'configured' => false,
                    'url' => null,
                    'username' => null,
                    'hasApiKey' => false,
                    'insecure' => false,
                    'expiresAt' => null,
                    'expired' => null,
                ],
            ],
            $this->statusOf($harness),
        );
    }

    public function testAValidJwtOfTheConfigurationFile(): void
    {
        $harness = new ApiHarness(self::CONFIG);

        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'configFile' => $harness->configPath(),
                    'configured' => true,
                    'url' => 'https://site.test',
                    'username' => 'key:me@site.test',
                    'hasApiKey' => true,
                    'insecure' => false,
                    'expiresAt' => self::date(self::VALID_EXPIRATION),
                    'expired' => false,
                ],
            ],
            $this->statusOf($harness),
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function expirations(): iterable
    {
        yield 'far' => [self::VALID_EXPIRATION, false];
        yield 'beyond the leeway' => [self::NOW + 31, false];
        yield 'within the leeway' => [self::NOW + 30, true];
        yield 'now' => [self::NOW, true];
        yield 'past' => [self::NOW - 3600, true];
    }

    #[DataProvider('expirations')]
    public function testAJwtAboutToExpireIsConsideredExpired(int $expiresAt, bool $expired): void
    {
        $harness = new ApiHarness(['expiresAt' => $expiresAt] + self::CONFIG);

        $data = $this->statusOf($harness)['data'];

        self::assertSame($expired, $data['expired']);
        self::assertSame(self::date($expiresAt), $data['expiresAt']);
    }

    public function testAJwtWithoutExpirationIsValid(): void
    {
        $harness = new ApiHarness(['username' => 'key:me@site.test', 'token' => 'config-secret-token']);

        $data = $this->statusOf($harness)['data'];

        self::assertNull($data['expiresAt']);
        self::assertFalse($data['expired']);
        self::assertFalse($data['hasApiKey']);
    }

    public function testAConfigurationWithoutJwtIsExpired(): void
    {
        $harness = new ApiHarness(['username' => 'key:me@site.test', 'apiKey' => 'api-key-value']);

        $data = $this->statusOf($harness)['data'];

        self::assertTrue($data['configured']);
        self::assertTrue($data['hasApiKey']);
        self::assertTrue($data['expired']);
    }

    public function testTheInsecureOptionOfTheConfigurationIsDisplayed(): void
    {
        $harness = new ApiHarness(['insecure' => true] + self::CONFIG);

        self::assertTrue($this->statusOf($harness)['data']['insecure']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function brokenFiles(): iterable
    {
        yield 'not json' => ['not json'];
        yield 'other version' => ['{"version":2,"url":"https://site.test","token":"config-secret-token"}'];
        yield 'no url' => ['{"version":1,"token":"config-secret-token"}'];
    }

    #[DataProvider('brokenFiles')]
    public function testABrokenConfigurationFileIsNotConfigured(string $content): void
    {
        $harness = new ApiHarness(null);
        file_put_contents($harness->configPath(), $content);

        $data = $this->statusOf($harness)['data'];

        self::assertFalse($data['configured']);
        self::assertNull($data['url']);
        self::assertNull($data['expired']);
    }

    public function testTheConfigurationFileCanBeChosenWithAnOption(): void
    {
        $harness = new ApiHarness(null);
        $harness->writeConfig(['insecure' => true] + self::CONFIG, 'other.json');

        $data = $this->statusOf($harness, '--config=other.json')['data'];

        self::assertSame($harness->configPath('other.json'), $data['configFile']);
        self::assertTrue($data['configured']);
        self::assertTrue($data['insecure']);
        self::assertFalse($this->statusOf($harness)['data']['configured'], 'The default file does not exist');
    }

    public function testTheTableFormat(): void
    {
        $harness = new ApiHarness(self::CONFIG);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:status', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('configured', $stdout);
        self::assertStringContainsString('key:me@site.test', $stdout);
        self::assertStringNotContainsString('config-secret-token', $stdout);
        self::assertStringNotContainsString('api-key-value', $stdout);
    }
}
