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
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function file_get_contents;
use function file_put_contents;
use function json_decode;

/**
 * Tests of the renewal of the JWT from the current one, there is no refresh token
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

    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    private function harness(array $env = [], int $expiration = self::OLD_EXPIRATION): ApiHarness
    {
        $harness = (new ApiHarness($env))->respond(
            'POST /api/v1/jwt/create-token',
            200,
            ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::NEW_EXPIRATION)]],
        );

        file_put_contents(
            $harness->temp()->path('session.json'),
            '{"version":1,"baseUrl":"https://site.test","username":"key:me@site.test","token":"old-token","expiresAt":' . $expiration . '}',
        );

        return $harness;
    }

    public function testRenewWithoutDateSendsAnEmptyObjectWithTheCurrentJwtAndStoresTheNewOne(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertCount(1, $harness->requests);
        $request = $harness->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertSame('/api/v1/jwt/create-token', $request['path']);
        self::assertSame('Bearer old-token', $request['headers']['authorization']);
        self::assertSame('application/json', $request['headers']['content-type']);
        self::assertSame('{}', $request['body']);

        $sessionFile = $harness->temp()->path('session.json');
        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'baseUrl' => 'https://site.test',
                    'username' => 'key:me@site.test',
                    'expiresAt' => (new DateTimeImmutable('@' . self::NEW_EXPIRATION))->format(DateTimeInterface::ATOM),
                    'sessionFile' => $sessionFile,
                ],
            ],
            AbstractCommandTest::decode($stdout),
        );
        self::assertStringNotContainsString(ApiHarness::jwt(self::NEW_EXPIRATION), $stdout);

        $session = json_decode((string) file_get_contents($sessionFile), true);
        self::assertSame(ApiHarness::jwt(self::NEW_EXPIRATION), $session['token']);
        self::assertSame(self::NEW_EXPIRATION, $session['expiresAt']);
        self::assertSame('key:me@site.test', $session['username']);
        self::assertSame('https://site.test', $session['baseUrl']);
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
    }

    public function testTheExplicitTokenIsUsedAndTheNewJwtIsStored(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'static-token']))->respond(
            'POST /api/v1/jwt/create-token',
            200,
            ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::NEW_EXPIRATION)]],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('Bearer static-token', $harness->requests[0]['headers']['authorization']);
        self::assertSame($harness->temp()->path('session.json'), AbstractCommandTest::decode($stdout)['data']['sessionFile']);
        self::assertStringContainsString(ApiHarness::jwt(self::NEW_EXPIRATION), (string) file_get_contents($harness->temp()->path('session.json')));
    }

    public function testAnExpiredSessionIsReplacedByANewLoginBeforeTheRenewal(): void
    {
        $harness = $this->harness(
            ['EAST_WEBSITE_USERNAME' => 'key:me@site.test', 'EAST_WEBSITE_API_KEY' => 'secret'],
            self::NOW - 1000,
        );
        $harness->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::OLD_EXPIRATION)]]);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(0, $code, $stderr);
        self::assertCount(2, $harness->requests);
        self::assertSame('/api/v1/login', $harness->requests[0]['path']);
        self::assertSame('/api/v1/jwt/create-token', $harness->requests[1]['path']);
        self::assertSame('Bearer ' . ApiHarness::jwt(self::OLD_EXPIRATION), $harness->requests[1]['headers']['authorization']);
    }

    public function testDryRunSendsNothing(): void
    {
        $harness = $this->harness();

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
        self::assertStringContainsString('old-token', (string) file_get_contents($harness->temp()->path('session.json')));
    }

    public function testDryRunWithoutDateShowsAnEmptyObject(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--dry-run', '--compact']);

        self::assertSame(0, $code);
        self::assertStringContainsString('"body":{}', $stdout);
    }

    public function testAResponseWithoutTokenIsAServerErrorAndTheSessionIsKept(): void
    {
        $harness = (new ApiHarness())->respond('POST /api/v1/jwt/create-token', 200, ['meta' => ['error' => false], 'data' => ['x' => 1]]);
        file_put_contents(
            $harness->temp()->path('session.json'),
            '{"version":1,"baseUrl":"https://site.test","username":"key:me@site.test","token":"old-token","expiresAt":' . self::OLD_EXPIRATION . '}',
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(1, $code);
        self::assertSame('The response does not contain a token', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertStringContainsString('old-token', (string) file_get_contents($harness->temp()->path('session.json')));
    }

    public function testARejectedJwtIsExitCode3(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'bad']))->respond(
            'POST /api/v1/jwt/create-token',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid JWT Token']],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertSame('Invalid JWT Token', AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testWithoutAnyCredentialTheRenewalIsExitCode3(): void
    {
        $harness = new ApiHarness();

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:renew']);

        self::assertSame(3, $code);
        self::assertSame([], $harness->requests);
    }

    public function testAnInvalidDateOfTheServerIsAValidationError(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']))->respond(
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

    public function testAnonymousRenewalSendsNoBearerAndKeepsTheSession(): void
    {
        $harness = $this->harness();

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--anonymous']);

        self::assertSame(0, $code);
        self::assertArrayNotHasKey('authorization', $harness->requests[0]['headers']);
        self::assertStringContainsString('old-token', (string) file_get_contents($harness->temp()->path('session.json')));
    }

    public function testNoSessionIsWrittenWithTheNoSessionOption(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']))->respond(
            'POST /api/v1/jwt/create-token',
            200,
            ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::NEW_EXPIRATION)]],
        );

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--no-session', '--compact', '--print-token']);

        self::assertSame(0, $code);
        $data = AbstractCommandTest::decode($stdout)['data'];
        self::assertNull($data['sessionFile']);
        self::assertSame(ApiHarness::jwt(self::NEW_EXPIRATION), $data['token']);
        self::assertFileDoesNotExist($harness->temp()->path('session.json'));
    }

    public function testAWarningIsWrittenWhenTheNewSessionCanNotBeStored(): void
    {
        $this->temp = new TempDir();
        $blocker = $this->temp->write('blocker', 'a file');
        $harness = (new ApiHarness([
            'EAST_WEBSITE_TOKEN' => 'jwt',
            'EAST_WEBSITE_SESSION_FILE' => $blocker . '/sub/session.json',
        ]))->respond(
            'POST /api/v1/jwt/create-token',
            200,
            ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::NEW_EXPIRATION)]],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:renew', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(ApiHarness::URL, AbstractCommandTest::decode($stdout)['data']['baseUrl']);
        self::assertStringStartsWith('warning: The session file', $stderr);
    }
}
