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
use Teknoo\East\Website\Tools\Command\Auth\LoginCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function is_file;
use function json_decode;

use const PHP_OS_FAMILY;

/**
 * Tests of the login with a username and an API key: JWT stored in a private session file, never printed by default
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoginCommand::class)]
class LoginCommandTest extends TestCase
{
    private const int EXPIRATION = 1_800_003_600;

    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    private function harness(array $env = []): ApiHarness
    {
        return (new ApiHarness($env + ['EAST_WEBSITE_API_KEY' => 'secret']))
            ->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::EXPIRATION)]]);
    }

    public function testLoginPostsTheCredentialsAndStoresThePrivateSession(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);

        self::assertCount(1, $harness->requests);
        $request = $harness->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertSame('https://site.test/api/v1/login', $request['url']);
        self::assertSame('application/json', $request['headers']['content-type']);
        self::assertArrayNotHasKey('authorization', $request['headers']);
        self::assertSame('{"username":"key:me@site.test","token":"secret"}', $request['body']);

        $sessionFile = $harness->temp()->path('session.json');
        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'baseUrl' => 'https://site.test',
                    'username' => 'key:me@site.test',
                    'expiresAt' => (new DateTimeImmutable('@' . self::EXPIRATION))->format(DateTimeInterface::ATOM),
                    'sessionFile' => $sessionFile,
                ],
            ],
            AbstractCommandTest::decode($stdout),
        );
        self::assertStringNotContainsString(ApiHarness::jwt(self::EXPIRATION), $stdout);
        self::assertStringNotContainsString('secret', $stdout);

        self::assertFileExists($sessionFile);
        if ('Windows' !== PHP_OS_FAMILY) {
            self::assertSame(0600, fileperms($sessionFile) & 0777);
        }

        self::assertSame(
            [
                'version' => 1,
                'baseUrl' => 'https://site.test',
                'username' => 'key:me@site.test',
                'token' => ApiHarness::jwt(self::EXPIRATION),
                'expiresAt' => self::EXPIRATION,
            ],
            json_decode((string) file_get_contents($sessionFile), true),
        );
        self::assertStringNotContainsString('secret', (string) file_get_contents($sessionFile));
    }

    public function testPrintTokenAddsTheJwtToTheResult(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--print-token', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame(ApiHarness::jwt(self::EXPIRATION), AbstractCommandTest::decode($stdout)['data']['token']);
    }

    public function testTheUsernameIsComposedFromTheKeyNameAndTheEmail(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--key-name=key', '--email=me@site.test', '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"username":"key:me@site.test","token":"secret"}', $harness->requests[0]['body']);
        self::assertSame('key:me@site.test', AbstractCommandTest::decode($stdout)['data']['username']);
    }

    public function testTheKeyNameAndTheEmailOverrideTheUsername(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_USERNAME' => 'old:old@site.test']);

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:login', '--key-name=key', '--email=me@site.test']);

        self::assertSame(0, $code);
        self::assertSame('{"username":"key:me@site.test","token":"secret"}', $harness->requests[0]['body']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function halfComposedUsernames(): iterable
    {
        yield 'key name without email' => ['--key-name=key'];
        yield 'email without key name' => ['--email=me@site.test'];
    }

    #[DataProvider('halfComposedUsernames')]
    public function testKeyNameAndEmailMustBeUsedTogether(string $option): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', $option]);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The options --key-name and --email must be used together',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testTheUsernameComesFromTheEnvironment(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_USERNAME' => 'env:env@site.test']);

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:login']);

        self::assertSame(0, $code);
        self::assertSame('{"username":"env:env@site.test","token":"secret"}', $harness->requests[0]['body']);
    }

    public function testTheUsernameOptionWinsOverTheEnvironment(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_USERNAME' => 'env:env@site.test']);

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=opt:opt@site.test']);

        self::assertSame(0, $code);
        self::assertSame('{"username":"opt:opt@site.test","token":"secret"}', $harness->requests[0]['body']);
    }

    public function testTheApiKeyIsReadFromAFileAndTrimmed(): void
    {
        $this->temp = new TempDir();
        $file = $this->temp->write('apikey.txt', "from-file\n");
        $harness = $this->harness(['EAST_WEBSITE_API_KEY' => 'from-env']);

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--api-key-file=' . $file],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"username":"key:me@site.test","token":"from-file"}', $harness->requests[0]['body']);
    }

    public function testTheApiKeyIsReadFromStdin(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_API_KEY' => '']);

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--api-key-file=-'],
            ['from-stdin'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"username":"key:me@site.test","token":"from-stdin"}', $harness->requests[0]['body']);
    }

    public function testAnUnreadableApiKeyFileIsAUsageError(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--api-key-file=/not/here.txt'],
        );

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The file "/not/here.txt" does not exist or is not readable',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testAnEmptyApiKeyFileIsAMissingApiKey(): void
    {
        $this->temp = new TempDir();
        $file = $this->temp->write('apikey.txt', "  \n");
        $harness = $this->harness(['EAST_WEBSITE_API_KEY' => '']);

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--api-key-file=' . $file],
        );

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    /**
     * @return iterable<string, array{array<string, string>, list<string>}>
     */
    public static function missingCredentials(): iterable
    {
        yield 'no api key' => [['EAST_WEBSITE_API_KEY' => ''], ['--username=key:me@site.test']];
        yield 'no username' => [['EAST_WEBSITE_API_KEY' => 'hidden-key-123'], []];
        yield 'nothing' => [['EAST_WEBSITE_API_KEY' => ''], []];
    }

    /**
     * @param array<string, string> $env
     * @param list<string> $options
     */
    #[DataProvider('missingCredentials')]
    public function testMissingCredentialsAreAUsageErrorWithTheHintAboutTheUsernameFormat(array $env, array $options): void
    {
        $harness = $this->harness($env);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', ...$options]);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        $message = AbstractCommandTest::decode($stderr)['data']['message'];
        self::assertStringContainsString('A username and its API key are required to login', $message);
        self::assertStringContainsString("'<keyName>:<email>'", $message);
        self::assertStringNotContainsString('hidden-key-123', $stderr);
    }

    public function testInvalidCredentialsAreExitCode3WithAHintAndNoSession(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_API_KEY' => 'wrong-secret']))->respond(
            'POST /api/v1/login',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid credentials.']],
            ['WWW-Authenticate' => 'Bearer'],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=me@site.test']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        $error = AbstractCommandTest::decode($stderr)['data'];
        self::assertSame('auth', $error['kind']);
        self::assertSame(401, $error['code']);
        self::assertSame('Invalid credentials.', $error['message']);
        self::assertStringContainsString("'<keyName>:<email>'", $error['hint']);
        self::assertStringNotContainsString('wrong-secret', $stderr);
        self::assertFileDoesNotExist($harness->temp()->path('session.json'));
    }

    public function testAServerErrorOfTheLoginIsExitCode1(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_API_KEY' => 'secret']))->respond(
            'POST /api/v1/login',
            500,
            ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Boom']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test']);

        self::assertSame(1, $code);
        self::assertArrayNotHasKey('hint', AbstractCommandTest::decode($stderr)['data']);
    }

    public function testALoginResponseWithoutTokenIsAServerError(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_API_KEY' => 'secret']))
            ->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => ['other' => 'x']]);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test']);

        self::assertSame(1, $code);
        self::assertSame('The login response does not contain a token', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertFileDoesNotExist($harness->temp()->path('session.json'));
    }

    public function testNoSessionIsWrittenWithTheNoSessionOption(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--no-session', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertNull(AbstractCommandTest::decode($stdout)['data']['sessionFile']);
        self::assertFileDoesNotExist($harness->temp()->path('session.json'));
    }

    public function testTheSessionFileCanBeChosenWithAnOption(): void
    {
        $this->temp = new TempDir();
        $path = $this->temp->path('custom/dir/session.json');
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--session-file=' . $path, '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame($path, AbstractCommandTest::decode($stdout)['data']['sessionFile']);
        self::assertTrue(is_file($path));
        self::assertFileDoesNotExist($harness->temp()->path('session.json'));
    }

    public function testAWarningIsWrittenWhenNoSessionPathCanBeDetermined(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_SESSION_FILE' => '']);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertNull(AbstractCommandTest::decode($stdout)['data']['sessionFile']);
        self::assertStringStartsWith('warning: The session can not be stored', $stderr);
        self::assertStringNotContainsString('{', $stderr);
    }

    public function testAWarningIsWrittenWhenTheSessionCanNotBeWritten(): void
    {
        $this->temp = new TempDir();
        $blocker = $this->temp->write('blocker', 'a file, not a directory');
        $harness = $this->harness(['EAST_WEBSITE_SESSION_FILE' => $blocker . '/sub/session.json']);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame(ApiHarness::URL, AbstractCommandTest::decode($stdout)['data']['baseUrl']);
        self::assertStringStartsWith('warning: The session file "' . $blocker . '/sub/session.json" can not be written', $stderr);
    }

    public function testLoginIgnoresAStoredSessionAndReplacesIt(): void
    {
        $harness = $this->harness();
        $session = $harness->temp()->path('session.json');
        file_put_contents($session, '{"version":1,"baseUrl":"https://site.test","username":"key:me@site.test","token":"stale","expiresAt":1800003600}');

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test']);

        self::assertSame(0, $code);
        self::assertCount(1, $harness->requests);
        self::assertStringContainsString(ApiHarness::jwt(self::EXPIRATION), (string) file_get_contents($session));
        self::assertStringNotContainsString('stale', (string) file_get_contents($session));
    }

    public function testCustomLoginPathAndFieldNamesComeFromTheEnvironment(): void
    {
        $harness = (new ApiHarness([
            'EAST_WEBSITE_API_KEY' => 'secret',
            'EAST_WEBSITE_LOGIN_PATH' => '/auth/login_check',
            'EAST_WEBSITE_LOGIN_USERNAME_FIELD' => 'user',
            'EAST_WEBSITE_LOGIN_SECRET_FIELD' => 'key',
        ]))->respond('POST /auth/login_check', 200, ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::EXPIRATION)]]);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('/auth/login_check', $harness->requests[0]['path']);
        self::assertSame('{"user":"key:me@site.test","key":"secret"}', $harness->requests[0]['body']);
    }

    public function testTheUrlOptionOverridesTheEnvironmentAndTheTrailingSlashIsIgnored(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--url=https://other.test/', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame('https://other.test/api/v1/login', $harness->requests[0]['url']);
        self::assertSame('https://other.test', AbstractCommandTest::decode($stdout)['data']['baseUrl']);
    }

    public function testPlainHttpToARemoteHostIsRefusedUnlessAllowed(): void
    {
        $refused = $this->harness();
        $allowed = $this->harness();

        [$refusedCode, , $stderr] = AbstractCommandTest::execute(
            $refused,
            ['website:auth:login', '--username=key:me@site.test', '--url=http://remote.test'],
        );
        [$allowedCode] = AbstractCommandTest::execute(
            $allowed,
            ['website:auth:login', '--username=key:me@site.test', '--url=http://remote.test', '--allow-http'],
        );

        self::assertSame(2, $refusedCode);
        self::assertSame([], $refused->requests);
        self::assertStringContainsString('Refusing to send credentials over plain http', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame(0, $allowedCode);
        self::assertSame('http://remote.test/api/v1/login', $allowed->requests[0]['url']);
    }

    public function testPlainHttpToALoopbackHostIsAllowed(): void
    {
        $harness = $this->harness();

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--url=http://127.0.0.1:8080'],
        );

        self::assertSame(0, $code);
        self::assertSame('http://127.0.0.1:8080/api/v1/login', $harness->requests[0]['url']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidUrls(): iterable
    {
        yield 'not http' => ['--url=ftp://site.test', 'The base URL must be an absolute http(s) URL, like https://example.com'];
        yield 'no host' => ['--url=https://', 'The base URL must be an absolute http(s) URL, like https://example.com'];
        yield 'relative' => ['--url=site.test/cms', 'The base URL must be an absolute http(s) URL, like https://example.com'];
    }

    #[DataProvider('invalidUrls')]
    public function testAnInvalidBaseUrlIsAUsageError(string $option, string $message): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test', $option]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testAMissingBaseUrlIsAUsageError(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_URL' => '']);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertStringContainsString('No base URL configured', AbstractCommandTest::decode($stderr)['data']['message']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTimeouts(): iterable
    {
        yield 'text' => ['abc'];
        yield 'zero' => ['0'];
        yield 'negative' => ['-5'];
    }

    #[DataProvider('invalidTimeouts')]
    public function testAnInvalidTimeoutIsAUsageError(string $timeout): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--timeout=' . $timeout],
        );

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The timeout must be a positive number of seconds, "' . $timeout . '" given',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testAValidTimeoutFromTheEnvironmentIsAccepted(): void
    {
        $harness = $this->harness(['EAST_WEBSITE_TIMEOUT' => '5']);

        [$code] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=key:me@site.test']);

        self::assertSame(0, $code);
    }

    public function testTheTableFormatDisplaysTheResultWithoutAnySecret(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--format=table'],
        );

        self::assertSame(0, $code);
        self::assertStringContainsString('| username', $stdout);
        self::assertStringContainsString('key:me@site.test', $stdout);
        self::assertStringNotContainsString('secret', $stdout);
        self::assertStringNotContainsString(ApiHarness::jwt(self::EXPIRATION), $stdout);
    }
}
