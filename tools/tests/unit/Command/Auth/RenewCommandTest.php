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
use Teknoo\East\Website\Tools\Command\Auth\RenewCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function chmod;
use function dirname;
use function file_get_contents;
use function is_writable;

/**
 * Tests of the renewal of the JWT of the configuration file from the current one, there is no refresh token
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RenewCommand::class)]
class RenewCommandTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    private const int OLD_EXPIRATION = 1_800_003_600;

    private const int NEW_EXPIRATION = 1_800_090_000;

    private const array CONFIG = [
        'username' => 'key:me@site.test',
        'apiKey' => 'api-key-value',
        'token' => 'old-token',
        'expiresAt' => self::OLD_EXPIRATION,
        'insecure' => true,
        'timeout' => 12,
    ];

    /**
     * @param array<string, mixed>|null $config
     */
    private function harness(?array $config = self::CONFIG): ApiHarness
    {
        return (new ApiHarness($config))->respond(
            'POST /api/v1/jwt/create-token',
            200,
            ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::NEW_EXPIRATION)]],
        );
    }

    private static function date(int $timestamp): string
    {
        return (new DateTimeImmutable('@' . $timestamp))->format(DateTimeInterface::ATOM);
    }

    public function testRenewWithoutDateSendsAnEmptyObjectWithTheCurrentJwtAndWritesTheNewOneInTheConfiguration(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);
        self::assertCount(1, $harness->requests);
        $request = $harness->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertSame('https://site.test/api/v1/jwt/create-token', $request['url']);
        self::assertSame('Bearer old-token', $request['headers']['authorization']);
        self::assertSame('application/json', $request['headers']['content-type']);
        self::assertSame('{}', $request['body']);

        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'configFile' => $harness->configPath(),
                    'url' => 'https://site.test',
                    'username' => 'key:me@site.test',
                    'expiresAt' => self::date(self::NEW_EXPIRATION),
                ],
            ],
            AbstractCommandTest::decode($stdout),
        );
        self::assertStringNotContainsString(ApiHarness::jwt(self::NEW_EXPIRATION), $stdout);
        self::assertStringNotContainsString('api-key-value', $stdout);

        self::assertSame(
            [
                'version' => 1,
                'url' => 'https://site.test',
                'username' => 'key:me@site.test',
                'apiKey' => 'api-key-value',
                'token' => ApiHarness::jwt(self::NEW_EXPIRATION),
                'expiresAt' => self::NEW_EXPIRATION,
                'insecure' => true,
                'allowHttp' => false,
                'timeout' => 12,
                'apiPrefix' => '/api/v1',
                'adminPrefix' => '/api/v1/admin',
                'loginPath' => '/api/v1/login',
                'usernameField' => 'username',
                'tokenField' => 'token',
            ],
            $harness->config(),
        );
    }

    public function testDaysAreConvertedToADateWithTheClock(): void
    {
        $harness = $this->harness();
        $expected = (new DateTimeImmutable('@' . self::NOW))->modify('+3 days')->format('Y-m-d');

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--days=3']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"expirationDate":"' . $expected . '"}', $harness->requests[0]['body']);
    }

    public function testTheExpirationDateIsSent(): void
    {
        $harness = $this->harness();

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--expiration-date=2030-01-31']);

        self::assertSame(0, $code);
        self::assertSame('{"expirationDate":"2030-01-31"}', $harness->requests[0]['body']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDates(): iterable
    {
        yield 'not a day' => ['2030-02-31'];
        yield 'text' => ['tomorrow'];
        yield 'no padding' => ['2030-1-1'];
        yield 'french format' => ['31/01/2030'];
        yield 'with time' => ['2030-01-31 10:00'];
    }

    #[DataProvider('invalidDates')]
    public function testAnInvalidExpirationDateIsAUsageErrorWithoutAnyRequest(string $date): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--expiration-date=' . $date]);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The expiration date must be a valid date as YYYY-MM-DD, "' . $date . '" given',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testDateAndDaysAreMutuallyExclusive(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--days=2', '--expiration-date=2030-01-31']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The options --expiration-date and --days are mutually exclusive',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDays(): iterable
    {
        yield 'zero' => ['0', 'The number of days must be greater or equal to 1'];
        yield 'negative' => ['-1', 'The number of days must be greater or equal to 1'];
        yield 'text' => ['abc', 'The option --days expects an integer, "abc" given'];
    }

    #[DataProvider('invalidDays')]
    public function testInvalidDaysAreAUsageError(string $days, string $message): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--days=' . $days]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testPrintTokenAddsTheNewJwtToTheResult(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--print-token', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(ApiHarness::jwt(self::NEW_EXPIRATION), AbstractCommandTest::decode($stdout)['data']['token']);
        self::assertStringNotContainsString('api-key-value', $stdout);
    }

    public function testAnExpiredJwtIsReplacedByANewLoginBeforeTheRenewal(): void
    {
        $harness = $this->harness(['expiresAt' => self::NOW - 1000] + self::CONFIG);
        $harness->respond(
            'POST /api/v1/login',
            200,
            ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::OLD_EXPIRATION)]],
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(0, $code, $stderr);
        self::assertCount(2, $harness->requests);
        self::assertSame('/api/v1/login', $harness->requests[0]['path']);
        self::assertSame(['username' => 'key:me@site.test', 'token' => 'api-key-value'], AbstractCommandTest::body($harness, 0));
        self::assertSame('/api/v1/jwt/create-token', $harness->requests[1]['path']);
        self::assertSame('Bearer ' . ApiHarness::jwt(self::OLD_EXPIRATION), $harness->requests[1]['headers']['authorization']);
        self::assertSame(ApiHarness::jwt(self::NEW_EXPIRATION), $harness->config()['token'] ?? null);
    }

    public function testAJwtRejectedByTheServerIsReplacedOnceWithTheApiKeyOfTheConfiguration(): void
    {
        $harness = (new ApiHarness(self::CONFIG))
            ->respond(
                'POST /api/v1/jwt/create-token',
                401,
                ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid JWT Token']],
            )
            ->respond(
                'POST /api/v1/jwt/create-token',
                200,
                ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::NEW_EXPIRATION)]],
            )
            ->respond(
                'POST /api/v1/login',
                200,
                ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::OLD_EXPIRATION)]],
            );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(0, $code, $stderr);
        self::assertCount(3, $harness->requests);
        self::assertSame('Bearer old-token', $harness->requests[0]['headers']['authorization']);
        self::assertSame('/api/v1/login', $harness->requests[1]['path']);
        self::assertSame('Bearer ' . ApiHarness::jwt(self::OLD_EXPIRATION), $harness->requests[2]['headers']['authorization']);
        self::assertSame(ApiHarness::jwt(self::NEW_EXPIRATION), $harness->config()['token'] ?? null);
    }

    public function testARejectedJwtWithoutApiKeyIsExitCode3AndTheConfigurationIsKept(): void
    {
        $harness = (new ApiHarness(['username' => 'key:me@site.test', 'token' => 'old-token', 'expiresAt' => self::OLD_EXPIRATION]))
            ->respond(
                'POST /api/v1/jwt/create-token',
                401,
                ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid JWT Token']],
            );
        $before = file_get_contents($harness->configPath());

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertSame('Invalid JWT Token', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertCount(1, $harness->requests);
        self::assertSame($before, file_get_contents($harness->configPath()));
    }

    public function testWithoutConfigurationFileTheRenewalIsExitCode3WithoutAnyRequest(): void
    {
        $harness = $this->harness(null);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertStringContainsString('website:auth:login', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);
        self::assertNull($harness->config());
    }

    public function testDryRunSendsNothing(): void
    {
        $harness = $this->harness();
        $before = file_get_contents($harness->configPath());

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--days=2', '--dry-run', '--compact']);

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $request = AbstractCommandTest::decode($stdout)['requests'][0];
        self::assertSame('POST', $request['method']);
        self::assertSame('https://site.test/api/v1/jwt/create-token', $request['url']);
        self::assertSame('Bearer ***', $request['headers']['Authorization']);
        self::assertSame(
            ['expirationDate' => (new DateTimeImmutable('@' . self::NOW))->modify('+2 days')->format('Y-m-d')],
            $request['body'],
        );
        self::assertSame($before, file_get_contents($harness->configPath()));
    }

    public function testDryRunWithoutDateShowsAnEmptyObject(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--dry-run', '--compact']);

        self::assertSame(0, $code);
        self::assertStringContainsString('"body":{}', $stdout);
    }

    public function testAResponseWithoutTokenIsAServerErrorAndTheConfigurationIsKept(): void
    {
        $harness = (new ApiHarness(self::CONFIG))
            ->respond('POST /api/v1/jwt/create-token', 200, ['meta' => ['error' => false], 'data' => ['x' => 1]]);
        $before = file_get_contents($harness->configPath());

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(1, $code);
        self::assertSame('The response does not contain a token', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame($before, file_get_contents($harness->configPath()));
    }

    public function testAnInvalidDateOfTheServerIsAValidationError(): void
    {
        $harness = (new ApiHarness(self::CONFIG))->respond(
            'POST /api/v1/jwt/create-token',
            400,
            ['meta' => ['errors' => true], 'data' => ['.expirationDate' => 'Please enter a valid date.']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--expiration-date=2030-01-31']);

        self::assertSame(2, $code);
        self::assertSame(
            ['.expirationDate' => 'Please enter a valid date.'],
            AbstractCommandTest::decode($stderr)['data']['fields'],
        );
    }

    public function testTheConfigurationFileCanBeChosenWithAnOption(): void
    {
        $harness = $this->harness(null);
        $harness->writeConfig(self::CONFIG, 'other.json');

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--config=other.json', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame($harness->configPath('other.json'), AbstractCommandTest::decode($stdout)['data']['configFile']);
        self::assertSame(ApiHarness::jwt(self::NEW_EXPIRATION), $harness->config('other.json')['token'] ?? null);
        self::assertNull($harness->config());
    }

    public function testAConfigurationFileWhichCanNotBeWrittenIsAUsageError(): void
    {
        $harness = $this->harness(null);
        $path = $harness->writeConfig(self::CONFIG, 'locked/east-website.json');
        $directory = dirname($path);
        chmod($directory, 0500);

        try {
            if (is_writable($directory)) {
                self::markTestSkipped('The directory stays writable (running as root or without POSIX permissions)');
            }

            [$code, $stdout, $stderr] = AbstractCommandTest::execute(
                $harness,
                ['website:auth:renew', '--config=locked/east-website.json'],
            );
        } finally {
            chmod($directory, 0700);
        }

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertStringStartsWith(
            'The configuration file "' . $path . '" can not be written',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame('old-token', $harness->config('locked/east-website.json')['token'] ?? null);
    }
}
