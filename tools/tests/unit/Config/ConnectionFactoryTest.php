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

namespace Teknoo\Tests\East\Website\Tools\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\ConnectionFactory;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function array_map;
use function array_unique;
use function count;
use function fopen;
use function fwrite;
use function rewind;

/**
 * Tests of the connection built from the global options and from the environment
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConnectionFactory::class)]
class ConnectionFactoryTest extends TestCase
{
    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    private function temp(): TempDir
    {
        return $this->temp ??= new TempDir();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function input(array $parameters = []): InputInterface
    {
        return new ArrayInput($parameters, new InputDefinition(ConnectionFactory::options()));
    }

    /**
     * @param array<string, string> $env
     * @param array<string, mixed> $parameters
     */
    private function create(array $env = [], array $parameters = []): Connection
    {
        return (new ConnectionFactory($env))->create($this->input($parameters));
    }

    public function testOptionsAreUniqueAndAllOptional(): void
    {
        $options = ConnectionFactory::options();
        $names = array_map(static fn (InputOption $option): string => $option->getName(), $options);

        self::assertSame(count($names), count(array_unique($names)));
        self::assertEqualsCanonicalizing(
            ['url', 'username', 'api-key-file', 'token-file', 'session-file', 'no-session', 'anonymous', 'insecure', 'allow-http', 'timeout'],
            $names,
        );

        foreach ($options as $option) {
            self::assertNotSame('api-key', $option->getName(), 'A secret must never be an option value');
            self::assertNotSame('token', $option->getName(), 'A secret must never be an option value');
        }
    }

    public function testDefaultsWithEmptyEnvironment(): void
    {
        $connection = $this->create();

        self::assertSame('', $connection->baseUrl);
        self::assertNull($connection->credentials->username);
        self::assertNull($connection->credentials->apiKey());
        self::assertNull($connection->credentials->token);
        self::assertTrue($connection->useSession);
        self::assertNull($connection->sessionPath);
        self::assertFalse($connection->insecure);
        self::assertFalse($connection->allowHttp);
        self::assertFalse($connection->anonymous);
        self::assertSame(30, $connection->timeout);
        self::assertSame('/api/v1', $connection->endpoints->apiPrefix());
        self::assertSame('/api/v1/admin', $connection->endpoints->adminPrefix());
        self::assertSame('/api/v1/login', $connection->endpoints->login());
        self::assertSame('username', $connection->usernameField);
        self::assertSame('token', $connection->tokenField);
    }

    public function testDefaultSessionPathComesFromTheEnvironment(): void
    {
        $connection = $this->create(['HOME' => '/home/agent']);

        self::assertSame('/home/agent/.local/state/east-website-cli/session.json', $connection->sessionPath);
    }

    public function testEnvironment(): void
    {
        $connection = $this->create([
            'EAST_WEBSITE_URL' => 'https://site.test/',
            'EAST_WEBSITE_USERNAME' => 'key:me@site.test',
            'EAST_WEBSITE_API_KEY' => "  secret-key\n",
            'EAST_WEBSITE_TOKEN' => 'jwt-token',
            'EAST_WEBSITE_TIMEOUT' => '7',
            'EAST_WEBSITE_SESSION_FILE' => '/tmp/east/session.json',
        ]);

        self::assertSame('https://site.test', $connection->baseUrl);
        self::assertSame('key:me@site.test', $connection->credentials->username);
        self::assertSame('secret-key', $connection->credentials->apiKey());
        self::assertSame('jwt-token', $connection->credentials->token);
        self::assertSame(7, $connection->timeout);
        self::assertSame('/tmp/east/session.json', $connection->sessionPath);
    }

    public function testOptionsWinOverTheEnvironment(): void
    {
        $connection = $this->create(
            [
                'EAST_WEBSITE_URL' => 'https://env.test',
                'EAST_WEBSITE_USERNAME' => 'env:user@site.test',
                'EAST_WEBSITE_TIMEOUT' => '7',
                'EAST_WEBSITE_SESSION_FILE' => '/tmp/env/session.json',
            ],
            [
                '--url' => 'https://option.test',
                '--username' => 'option:user@site.test',
                '--timeout' => '15',
                '--session-file' => '/tmp/option/session.json',
            ],
        );

        self::assertSame('https://option.test', $connection->baseUrl);
        self::assertSame('option:user@site.test', $connection->credentials->username);
        self::assertSame(15, $connection->timeout);
        self::assertSame('/tmp/option/session.json', $connection->sessionPath);
    }

    public function testEmptyOptionsFallBackToTheEnvironment(): void
    {
        $connection = $this->create(
            ['EAST_WEBSITE_URL' => 'https://env.test', 'EAST_WEBSITE_USERNAME' => 'env:user@site.test'],
            ['--url' => '', '--username' => ''],
        );

        self::assertSame('https://env.test', $connection->baseUrl);
        self::assertSame('env:user@site.test', $connection->credentials->username);
    }

    public function testEmptyEnvironmentValuesAreIgnored(): void
    {
        $connection = $this->create([
            'EAST_WEBSITE_URL' => '',
            'EAST_WEBSITE_API_KEY' => '   ',
            'EAST_WEBSITE_TOKEN' => '',
        ]);

        self::assertSame('', $connection->baseUrl);
        self::assertNull($connection->credentials->apiKey());
        self::assertNull($connection->credentials->token);
    }

    public function testFlags(): void
    {
        $connection = $this->create(
            ['HOME' => '/home/agent'],
            ['--no-session' => true, '--anonymous' => true, '--insecure' => true, '--allow-http' => true],
        );

        self::assertFalse($connection->useSession);
        self::assertNull($connection->sessionPath);
        self::assertTrue($connection->anonymous);
        self::assertTrue($connection->insecure);
        self::assertTrue($connection->allowHttp);
    }

    public function testNoSessionIgnoresTheSessionFileOfTheEnvironment(): void
    {
        $connection = $this->create(['EAST_WEBSITE_SESSION_FILE' => '/tmp/east/session.json'], ['--no-session' => true]);

        self::assertNull($connection->sessionPath);
    }

    public function testApiKeyAndTokenFromFiles(): void
    {
        $keyFile = $this->temp()->write('key.txt', "file-key\n");
        $tokenFile = $this->temp()->write('token.txt', 'file-jwt');

        $connection = $this->create(
            ['EAST_WEBSITE_API_KEY' => 'env-key', 'EAST_WEBSITE_TOKEN' => 'env-jwt'],
            ['--api-key-file' => $keyFile, '--token-file' => $tokenFile],
        );

        self::assertSame('file-key', $connection->credentials->apiKey());
        self::assertSame('file-jwt', $connection->credentials->token);
    }

    public function testEmptySecretFileGivesNoSecret(): void
    {
        $connection = $this->create([], ['--api-key-file' => $this->temp()->write('empty.txt', "\n")]);

        self::assertNull($connection->credentials->apiKey());
    }

    public function testMissingSecretFileIsAUsageError(): void
    {
        try {
            $this->create([], ['--api-key-file' => $this->temp()->path('missing.txt')]);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('missing.txt', $error->getMessage());
        }
    }

    public function testApiKeyFromStandardInput(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, "piped-key\n");
        rewind($stream);

        $input = new ArgvInput(['east-website', '--api-key-file=-'], new InputDefinition(ConnectionFactory::options()));
        $input->setStream($stream);

        $connection = (new ConnectionFactory([]))->create($input);

        self::assertSame('piped-key', $connection->credentials->apiKey());
    }

    public function testTokenFromStandardInput(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'piped-jwt');
        rewind($stream);

        $input = new ArgvInput(['east-website', '--token-file=-'], new InputDefinition(ConnectionFactory::options()));
        $input->setStream($stream);

        self::assertSame('piped-jwt', (new ConnectionFactory([]))->create($input)->credentials->token);
    }

    public function testEndpointsAndLoginFieldsFromTheEnvironment(): void
    {
        $connection = $this->create([
            'EAST_WEBSITE_API_PREFIX' => '/rest',
            'EAST_WEBSITE_ADMIN_PREFIX' => '/rest/back',
            'EAST_WEBSITE_LOGIN_PATH' => '/auth/login_check',
            'EAST_WEBSITE_LOGIN_USERNAME_FIELD' => 'login',
            'EAST_WEBSITE_LOGIN_SECRET_FIELD' => 'apikey',
        ]);

        self::assertSame('/rest', $connection->endpoints->apiPrefix());
        self::assertSame('/rest/back', $connection->endpoints->adminPrefix());
        self::assertSame('/auth/login_check', $connection->endpoints->login());
        self::assertSame('login', $connection->usernameField);
        self::assertSame('apikey', $connection->tokenField);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTimeouts(): iterable
    {
        yield 'text' => ['soon'];
        yield 'zero' => ['0'];
        yield 'negative' => ['-5'];
        yield 'decimal' => ['1.5'];
    }

    #[DataProvider('invalidTimeouts')]
    public function testInvalidTimeoutIsAUsageError(string $timeout): void
    {
        try {
            $this->create([], ['--timeout' => $timeout]);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString($timeout, $error->getMessage());
        }
    }

    public function testInvalidTimeoutFromTheEnvironmentIsAUsageError(): void
    {
        $this->expectException(ApiException::class);

        $this->create(['EAST_WEBSITE_TIMEOUT' => 'abc']);
    }

    private function sessionFile(string $baseUrl, string $username): string
    {
        return $this->temp()->write(
            'session.json',
            '{"version":1,"baseUrl":"' . $baseUrl . '","username":"' . $username . '","token":"jwt","expiresAt":1800003600}',
        );
    }

    public function testBaseUrlFallsBackToTheUrlOfTheStoredSession(): void
    {
        $path = $this->sessionFile('https://stored.test', 'key:me@site.test');

        $connection = $this->create(['EAST_WEBSITE_SESSION_FILE' => $path]);

        self::assertSame('https://stored.test', $connection->baseUrl);
    }

    public function testBaseUrlOfTheSessionIsUsedWhenTheUsernameMatches(): void
    {
        $path = $this->sessionFile('https://stored.test', 'key:me@site.test');

        $connection = $this->create([], ['--session-file' => $path, '--username' => 'key:me@site.test']);

        self::assertSame('https://stored.test', $connection->baseUrl);
    }

    public function testBaseUrlOfTheSessionIsIgnoredForAnotherUsername(): void
    {
        $path = $this->sessionFile('https://stored.test', 'key:me@site.test');

        $connection = $this->create([], ['--session-file' => $path, '--username' => 'other:someone@site.test']);

        self::assertSame('', $connection->baseUrl);
    }

    public function testExplicitUrlWinsOverTheStoredSession(): void
    {
        $path = $this->sessionFile('https://stored.test', 'key:me@site.test');

        $connection = $this->create(['EAST_WEBSITE_URL' => 'https://env.test'], ['--session-file' => $path]);

        self::assertSame('https://env.test', $connection->baseUrl);
    }

    public function testNoBaseUrlFromTheSessionWhenSessionsAreDisabled(): void
    {
        $path = $this->sessionFile('https://stored.test', 'key:me@site.test');

        $connection = $this->create([], ['--session-file' => $path, '--no-session' => true]);

        self::assertSame('', $connection->baseUrl);
    }

    public function testMissingSessionFileGivesNoBaseUrl(): void
    {
        $connection = $this->create([], ['--session-file' => $this->temp()->path('missing.json')]);

        self::assertSame('', $connection->baseUrl);
    }

    public function testOptionsAreReadOnlyWhenTheInputDoesNotDefineThem(): void
    {
        $connection = (new ConnectionFactory(['EAST_WEBSITE_URL' => 'https://env.test']))
            ->create(new ArrayInput([], new InputDefinition()));

        self::assertSame('https://env.test', $connection->baseUrl);
        self::assertTrue($connection->useSession);
    }
}
