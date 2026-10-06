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
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Command\Auth\LoginCommand;
use Teknoo\East\Website\Tools\Runtime;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function defined;
use function file_get_contents;
use function fileperms;
use function fopen;
use function json_encode;
use function stream_isatty;

use const PHP_OS_FAMILY;
use const STDIN;

/**
 * Tests of the login with a username and an API key: it is the only command configuring the connection, it writes
 * the private configuration file read by the other commands, and never prints the API key nor, by default, the JWT
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoginCommand::class)]
class LoginCommandTest extends TestCase
{
    private const int EXPIRATION = 1_800_003_600;

    /**
     * Configuration file of a previous login, with settings different from the defaults.
     */
    private const array STORED = [
        'username' => 'key:me@site.test',
        'apiKey' => 'stored-key',
        'token' => 'stored-token',
        'expiresAt' => 1_800_000_100,
        'insecure' => true,
        'allowHttp' => false,
        'timeout' => 12,
        'apiPrefix' => '/cms/api',
        'adminPrefix' => '/cms/admin',
        'loginPath' => '/cms/login',
        'usernameField' => 'login',
        'tokenField' => 'secret',
    ];

    private const array LOGIN = ['website:auth:login', '--url=https://site.test', '--username=key:me@site.test'];

    /**
     * @param array<string, mixed>|null $config
     */
    private function harness(?array $config = null, string $route = 'POST /api/v1/login'): ApiHarness
    {
        return (new ApiHarness($config))
            ->respond($route, 200, ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(self::EXPIRATION)]]);
    }

    /**
     * @param list<string> $options
     * @param list<string> $inputs
     * @return array{int, string, string}
     */
    private function login(ApiHarness $harness, array $options = [], array $inputs = ['secret']): array
    {
        return AbstractCommandTest::execute($harness, [...self::LOGIN, '--api-key-file=-', ...$options], $inputs);
    }

    /**
     * Replaces the login command by one which considers (or not) that stdin is a terminal.
     */
    private function onTerminal(ApiHarness $harness, bool $terminal): void
    {
        $application = $harness->application();
        $runtime = (new ReflectionProperty(AbstractCommand::class, 'runtime'))
            ->getValue($application->find('website:auth:login'));
        self::assertInstanceOf(Runtime::class, $runtime);

        $application->addCommand(new class ($runtime, $terminal) extends LoginCommand {
            public function __construct(Runtime $runtime, private readonly bool $terminal)
            {
                parent::__construct($runtime);
            }

            protected function isTerminal(): bool
            {
                return $this->terminal;
            }
        });
    }

    /**
     * Content of the configuration file written by a login, in the order of its fields.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private static function merge(array $changes): array
    {
        $written = [
            'version' => 1,
            'url' => 'https://site.test',
            'username' => 'key:me@site.test',
            'apiKey' => 'secret',
            'token' => ApiHarness::jwt(self::EXPIRATION),
            'expiresAt' => self::EXPIRATION,
            'insecure' => false,
            'allowHttp' => false,
            'timeout' => 30,
            'apiPrefix' => '/api/v1',
            'adminPrefix' => '/api/v1/admin',
            'loginPath' => '/api/v1/login',
            'usernameField' => 'username',
            'tokenField' => 'token',
        ];

        foreach ($changes as $field => $value) {
            $written[$field] = $value;
        }

        return $written;
    }

    public function testLoginPostsTheCredentialsAndWritesThePrivateConfigurationFile(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = $this->login($harness, ['--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);

        self::assertCount(1, $harness->requests);
        $request = $harness->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertSame('https://site.test/api/v1/login', $request['url']);
        self::assertSame('application/json', $request['headers']['content-type']);
        self::assertArrayNotHasKey('authorization', $request['headers']);
        self::assertSame('{"username":"key:me@site.test","token":"secret"}', $request['body']);

        $configFile = $harness->configPath();
        self::assertSame(
            [
                'meta' => ['error' => false],
                'data' => [
                    'configFile' => $configFile,
                    'url' => 'https://site.test',
                    'username' => 'key:me@site.test',
                    'expiresAt' => (new DateTimeImmutable('@' . self::EXPIRATION))->format(DateTimeInterface::ATOM),
                ],
            ],
            AbstractCommandTest::decode($stdout),
        );
        self::assertStringNotContainsString(ApiHarness::jwt(self::EXPIRATION), $stdout);
        self::assertStringNotContainsString('secret', $stdout);

        self::assertFileExists($configFile);
        if ('Windows' !== PHP_OS_FAMILY) {
            self::assertSame(0600, fileperms($configFile) & 0777);
        }

        self::assertSame(self::merge([]), $harness->config());
    }

    public function testPrintTokenAddsTheJwtToTheResultButNeverTheApiKey(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = $this->login($harness, ['--print-token', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(ApiHarness::jwt(self::EXPIRATION), AbstractCommandTest::decode($stdout)['data']['token']);
        self::assertStringNotContainsString('secret', $stdout);
    }

    public function testTheUsernameIsComposedFromTheKeyNameAndTheEmail(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--url=https://site.test', '--key-name=key', '--email=me@site.test', '--api-key-file=-'],
            ['secret'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame(['username' => 'key:me@site.test', 'token' => 'secret'], AbstractCommandTest::body($harness, 0));
        self::assertSame('key:me@site.test', $harness->config()['username'] ?? null);
    }

    public function testTheKeyNameAndTheEmailOverrideTheUsername(): void
    {
        $harness = $this->harness();

        [$code] = $this->login($harness, ['--key-name=other', '--email=other@site.test']);

        self::assertSame(0, $code);
        self::assertSame('other:other@site.test', AbstractCommandTest::body($harness, 0)['username']);
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

        [$code, $stdout, $stderr] = $this->login($harness, [$option]);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame(
            'The options --key-name and --email must be used together',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame([], $harness->requests);
        self::assertNull($harness->config());
    }

    public function testTheApiKeyIsReadFromAFileAndTrimmed(): void
    {
        $harness = $this->harness();
        $file = $harness->temp()->write('api-key.txt', "  file-secret \n");

        [$code, , $stderr] = AbstractCommandTest::execute($harness, [...self::LOGIN, '--api-key-file=' . $file]);

        self::assertSame(0, $code, $stderr);
        self::assertSame('file-secret', AbstractCommandTest::body($harness, 0)['token']);
        self::assertSame('file-secret', $harness->config()['apiKey'] ?? null);
    }

    public function testAnUnreadableApiKeyFileIsAUsageError(): void
    {
        $harness = $this->harness();
        $file = $harness->temp()->path('missing.txt');

        [$code, , $stderr] = AbstractCommandTest::execute($harness, [...self::LOGIN, '--api-key-file=' . $file]);

        self::assertSame(2, $code);
        self::assertSame(
            'The file "' . $file . '" does not exist or is not readable',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame([], $harness->requests);
    }

    public function testAnEmptyApiKeyIsAUsageError(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = $this->login($harness, [], ['   ']);

        self::assertSame(2, $code);
        self::assertSame(
            'The API key is empty. ' . Authenticator::USERNAME_HINT,
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame([], $harness->requests);
        self::assertNull($harness->config());
    }

    public function testWithoutApiKeyANonInteractiveLoginIsAUsageErrorWithoutAnyRequest(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, self::LOGIN);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        $error = AbstractCommandTest::decode($stderr)['data'];
        self::assertSame('usage', $error['kind']);
        self::assertStringContainsString('--api-key-file=-', $error['message']);
        self::assertSame([], $harness->requests);
        self::assertNull($harness->config());
    }

    public function testWithoutTerminalTheApiKeyIsNotAsked(): void
    {
        $harness = $this->harness();
        $this->onTerminal($harness, false);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, self::LOGIN, ['typed-key'], true);

        self::assertSame(2, $code);
        self::assertStringContainsString('--api-key-file=-', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertStringNotContainsString('API key: ', $stderr);
        self::assertSame([], $harness->requests);
    }

    public function testOnATerminalTheApiKeyIsAskedWithoutBeingDisplayed(): void
    {
        QuestionHelper::disableStty();
        $harness = $this->harness();
        $this->onTerminal($harness, true);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, [...self::LOGIN, '--compact'], [' typed-key '], true);

        self::assertSame(0, $code, $stderr);
        self::assertStringContainsString('API key: ', $stderr, 'The question is written on stderr');
        self::assertStringNotContainsString('typed-key', $stderr);
        self::assertStringNotContainsString('typed-key', $stdout);
        self::assertSame('key:me@site.test', AbstractCommandTest::decode($stdout)['data']['username']);
        self::assertSame(['username' => 'key:me@site.test', 'token' => 'typed-key'], AbstractCommandTest::body($harness, 0));
        self::assertSame('typed-key', $harness->config()['apiKey'] ?? null);
    }

    public function testAnEmptyAnswerOnATerminalIsAUsageError(): void
    {
        QuestionHelper::disableStty();
        $harness = $this->harness();
        $this->onTerminal($harness, true);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, self::LOGIN, [''], true);

        self::assertSame(2, $code);
        self::assertStringContainsString(
            'The API key is empty. ' . Authenticator::USERNAME_HINT,
            $stderr,
        );
        self::assertSame([], $harness->requests);
    }

    public function testAQuestionWithoutAnswerIsAUsageError(): void
    {
        QuestionHelper::disableStty();
        $harness = $this->harness();
        $this->onTerminal($harness, true);

        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        $input = new ArrayInput(['command' => 'website:auth:login', '--url' => 'https://site.test', '--username' => 'key:me@site.test']);
        $input->setStream($stream);
        $input->setInteractive(true);
        $output = new BufferedOutput();

        $code = $harness->application()->run($input, $output);

        self::assertSame(2, $code);
        $display = $output->fetch();
        self::assertStringContainsString('The API key can not be typed without being displayed on this terminal', $display);
        self::assertSame([], $harness->requests);
    }

    public function testTheTerminalIsTheStandardInput(): void
    {
        $harness = $this->harness();
        $command = $harness->application()->find('website:auth:login');

        self::assertSame(
            defined('STDIN') && stream_isatty(STDIN),
            (new ReflectionMethod(LoginCommand::class, 'isTerminal'))->invoke($command),
        );
    }

    public function testInvalidCredentialsAreExitCode3WithAHintAndNothingIsWritten(): void
    {
        $harness = (new ApiHarness(null))->respond(
            'POST /api/v1/login',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid credentials.']],
        );

        [$code, $stdout, $stderr] = $this->login($harness, [], ['hidden-key-123']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        $error = AbstractCommandTest::decode($stderr)['data'];
        self::assertSame('auth', $error['kind']);
        self::assertSame('Invalid credentials.', $error['message']);
        self::assertSame(Authenticator::USERNAME_HINT, $error['hint']);
        self::assertStringNotContainsString('hidden-key-123', $stderr);
        self::assertNull($harness->config());
    }

    public function testAFailedLoginKeepsTheExistingConfigurationFile(): void
    {
        $harness = (new ApiHarness(self::STORED))->respond(
            'POST /cms/login',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid credentials.']],
        );
        $before = file_get_contents($harness->configPath());

        [$code] = $this->login($harness, [], ['wrong-key']);

        self::assertSame(3, $code);
        self::assertSame($before, file_get_contents($harness->configPath()));
    }

    public function testAServerErrorOfTheLoginIsExitCode1(): void
    {
        $harness = (new ApiHarness(null))->respond(
            'POST /api/v1/login',
            500,
            ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Boom']],
        );

        [$code, , $stderr] = $this->login($harness);

        self::assertSame(1, $code);
        self::assertSame('server', AbstractCommandTest::decode($stderr)['data']['kind']);
        self::assertArrayNotHasKey('hint', AbstractCommandTest::decode($stderr)['data']);
        self::assertNull($harness->config());
    }

    public function testALoginResponseWithoutTokenIsAServerError(): void
    {
        $harness = (new ApiHarness(null))->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => []]);

        [$code, , $stderr] = $this->login($harness);

        self::assertSame(1, $code);
        self::assertSame('The login response does not contain a token', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertNull($harness->config());
    }

    public function testTheConfigurationFileCanBeChosenWithARelativePath(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = $this->login($harness, ['--config=sites/other.json', '--compact']);

        self::assertSame(0, $code, $stderr);
        $path = $harness->temp()->path('sites/other.json');
        self::assertSame($path, AbstractCommandTest::decode($stdout)['data']['configFile']);
        self::assertSame(self::merge([]), $harness->config('sites/other.json'));
        self::assertNull($harness->config());
    }

    public function testTheConfigurationFileCanBeChosenWithAnAbsolutePath(): void
    {
        $harness = $this->harness();
        $path = $harness->temp()->path('absolute.json');

        [$code, $stdout] = $this->login($harness, ['--config=' . $path, '--compact']);

        self::assertSame(0, $code);
        self::assertSame($path, AbstractCommandTest::decode($stdout)['data']['configFile']);
        self::assertFileExists($path);
    }

    public function testAConfigurationFileWhichCanNotBeWrittenIsAUsageError(): void
    {
        $harness = $this->harness();
        $harness->temp()->write('blocker', 'a file');

        [$code, $stdout, $stderr] = $this->login($harness, ['--config=blocker/east-website.json'], ['hidden-key-123']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        $error = AbstractCommandTest::decode($stderr)['data'];
        self::assertSame('usage', $error['kind']);
        self::assertStringStartsWith(
            'The configuration file "' . $harness->temp()->path('blocker/east-website.json') . '" can not be written',
            $error['message'],
        );
        self::assertStringNotContainsString('hidden-key-123', $stderr);
    }

    public function testRunAgainWithoutOptionsTheLoginReusesTheStoredConfiguration(): void
    {
        $harness = $this->harness(self::STORED, 'POST /cms/login');

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertCount(1, $harness->requests);
        self::assertSame('https://site.test/cms/login', $harness->requests[0]['url']);
        self::assertSame(['login' => 'key:me@site.test', 'secret' => 'stored-key'], AbstractCommandTest::body($harness, 0));
        self::assertSame('https://site.test', AbstractCommandTest::decode($stdout)['data']['url']);
        self::assertSame(
            self::merge([
                'apiKey' => 'stored-key',
                'insecure' => true,
                'timeout' => 12,
                'apiPrefix' => '/cms/api',
                'adminPrefix' => '/cms/admin',
                'loginPath' => '/cms/login',
                'usernameField' => 'login',
                'tokenField' => 'secret',
            ]),
            $harness->config(),
        );
    }

    public function testTheOptionsOverrideTheStoredConfiguration(): void
    {
        $harness = $this->harness(self::STORED, 'POST /v2/login');

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            [
                'website:auth:login',
                '--no-insecure',
                '--allow-http',
                '--timeout=5',
                '--api-prefix=v2/',
                '--admin-prefix=/v2/admin',
                '--login-path=/v2/login',
                '--login-username-field=user',
                '--login-secret-field=key',
            ],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('https://site.test/v2/login', $harness->requests[0]['url']);
        self::assertSame(['user' => 'key:me@site.test', 'key' => 'stored-key'], AbstractCommandTest::body($harness, 0));
        self::assertSame(
            self::merge([
                'apiKey' => 'stored-key',
                'insecure' => false,
                'allowHttp' => true,
                'timeout' => 5,
                'apiPrefix' => '/v2',
                'adminPrefix' => '/v2/admin',
                'loginPath' => '/v2/login',
                'usernameField' => 'user',
                'tokenField' => 'key',
            ]),
            $harness->config(),
        );
    }

    public function testTheInsecureOptionIsStored(): void
    {
        $harness = $this->harness();

        [$code] = $this->login($harness, ['--insecure']);

        self::assertSame(0, $code);
        self::assertTrue($harness->config()['insecure'] ?? null);
    }

    public function testAnotherUsernameOnTheSameServerNeedsItsOwnApiKey(): void
    {
        $harness = $this->harness(self::STORED, 'POST /cms/login');

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--username=other:x@site.test']);

        self::assertSame(2, $code);
        self::assertStringContainsString('--api-key-file=-', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=other:x@site.test', '--api-key-file=-'],
            ['other-key'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame(['login' => 'other:x@site.test', 'secret' => 'other-key'], AbstractCommandTest::body($harness, 0));
        self::assertSame(
            self::merge([
                'username' => 'other:x@site.test',
                'apiKey' => 'other-key',
                'insecure' => true,
                'timeout' => 12,
                'apiPrefix' => '/cms/api',
                'adminPrefix' => '/cms/admin',
                'loginPath' => '/cms/login',
                'usernameField' => 'login',
                'tokenField' => 'secret',
            ]),
            $harness->config(),
        );
    }

    public function testAnotherServerReusesNeitherTheSettingsNorTheUsernameNorTheApiKey(): void
    {
        $harness = $this->harness(self::STORED);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:auth:login', '--url=https://other.test']);

        self::assertSame(2, $code);
        self::assertSame(
            'The option --username (or --key-name with --email) is required. ' . Authenticator::USERNAME_HINT,
            AbstractCommandTest::decode($stderr)['data']['message'],
        );

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--url=https://other.test', '--username=key:me@site.test'],
        );

        self::assertSame(2, $code);
        self::assertStringContainsString('--api-key-file=-', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests, 'The stored API key is never sent to another server');

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--url=https://other.test', '--username=key:me@site.test', '--api-key-file=-'],
            ['secret'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('https://other.test/api/v1/login', $harness->requests[0]['url']);
        self::assertSame(['username' => 'key:me@site.test', 'token' => 'secret'], AbstractCommandTest::body($harness, 0));
        self::assertSame(self::merge(['url' => 'https://other.test']), $harness->config());
        self::assertStringNotContainsString('stored-key', (string) json_encode($harness->requests));
    }

    public function testTheTrailingSlashOfTheUrlIsIgnored(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--url=https://site.test/', '--username=key:me@site.test', '--api-key-file=-', '--compact'],
            ['secret'],
        );

        self::assertSame(0, $code);
        self::assertSame('https://site.test', AbstractCommandTest::decode($stdout)['data']['url']);
        self::assertSame('https://site.test/api/v1/login', $harness->requests[0]['url']);
        self::assertSame('https://site.test', $harness->config()['url'] ?? null);
    }

    public function testPlainHttpToARemoteHostIsRefusedUnlessAllowed(): void
    {
        $harness = $this->harness();
        $options = ['website:auth:login', '--url=http://site.test', '--username=key:me@site.test', '--api-key-file=-'];

        [$refusedCode, , $stderr] = AbstractCommandTest::execute($harness, $options, ['secret']);

        self::assertSame(2, $refusedCode);
        self::assertSame(
            'Refusing to send credentials over plain http to a remote host, use https or the --allow-http option',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame([], $harness->requests);
        self::assertNull($harness->config());

        [$allowedCode] = AbstractCommandTest::execute($harness, [...$options, '--allow-http'], ['secret']);

        self::assertSame(0, $allowedCode);
        self::assertSame('http://site.test/api/v1/login', $harness->requests[0]['url']);
        self::assertTrue($harness->config()['allowHttp'] ?? null);
    }

    public function testPlainHttpToALoopbackHostIsAllowed(): void
    {
        $harness = $this->harness();

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--url=http://localhost:8080', '--username=key:me@site.test', '--api-key-file=-'],
            ['secret'],
        );

        self::assertSame(0, $code);
        self::assertSame('http://localhost:8080/api/v1/login', $harness->requests[0]['url']);
        self::assertFalse($harness->config()['allowHttp'] ?? null);
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

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--api-key-file=-', $option],
            ['secret'],
        );

        self::assertSame(2, $code);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);
        self::assertNull($harness->config());
    }

    public function testAMissingBaseUrlIsAUsageError(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--username=key:me@site.test', '--api-key-file=-'],
            ['secret'],
        );

        self::assertSame(2, $code);
        self::assertSame(
            'The option --url is required, like --url=https://example.com',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame([], $harness->requests);
    }

    public function testAMissingUsernameIsAUsageErrorWithTheHint(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:auth:login', '--url=https://site.test', '--api-key-file=-'],
            ['secret'],
        );

        self::assertSame(2, $code);
        self::assertSame(
            'The option --username (or --key-name with --email) is required. ' . Authenticator::USERNAME_HINT,
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
        self::assertSame([], $harness->requests);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidTimeouts(): iterable
    {
        yield 'text' => ['abc', 'The option --timeout expects an integer, "abc" given'];
        yield 'zero' => ['0', 'The timeout must be a positive number of seconds, "0" given'];
        yield 'negative' => ['-5', 'The timeout must be a positive number of seconds, "-5" given'];
    }

    #[DataProvider('invalidTimeouts')]
    public function testAnInvalidTimeoutIsAUsageError(string $timeout, string $message): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = $this->login($harness, ['--timeout=' . $timeout]);

        self::assertSame(2, $code);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);
    }

    public function testAValidTimeoutIsStored(): void
    {
        $harness = $this->harness();

        [$code] = $this->login($harness, ['--timeout=7']);

        self::assertSame(0, $code);
        self::assertSame(7, $harness->config()['timeout'] ?? null);
    }

    public function testTheTableFormatDisplaysTheResultWithoutAnySecret(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = $this->login($harness, ['--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('key:me@site.test', $stdout);
        self::assertStringContainsString($harness->configPath(), $stdout);
        self::assertStringNotContainsString(ApiHarness::jwt(self::EXPIRATION), $stdout);
        self::assertStringNotContainsString('secret', $stdout);
    }
}
