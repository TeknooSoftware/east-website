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

namespace Teknoo\Tests\East\Website\Tools\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Warnings;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;
use Teknoo\Tests\East\Website\Tools\Support\FixedClock;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function array_shift;

/**
 * Tests of the authenticator: the JWT of the configuration file while it is valid, else a new login with the API key
 * of this file, stored back in it
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Authenticator::class)]
class AuthenticatorTest extends TestCase
{
    private const string URL = 'https://site.test';

    private const string USERNAME = 'key:me@site.test';

    private TempDir $temp;

    private FixedClock $clock;

    private Warnings $warnings;

    /**
     * @var list<array{method: string, url: string, body: string}>
     */
    private array $sent = [];

    protected function setUp(): void
    {
        $this->temp = new TempDir();
        $this->clock = new FixedClock();
        $this->warnings = new Warnings();
        $this->sent = [];
    }

    protected function tearDown(): void
    {
        $this->temp->remove();
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function authenticator(array $responses = []): Authenticator
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->sent[] = ['method' => $method, 'url' => $url, 'body' => (string) ($options['body'] ?? '')];
            $response = array_shift($responses);
            self::assertInstanceOf(MockResponse::class, $response, 'Unexpected request ' . $method . ' ' . $url);

            return $response;
        });

        return new Authenticator(new Transport($http), $this->clock, $this->warnings);
    }

    private function configPath(): string
    {
        return $this->temp->path(ConfigFile::DEFAULT_NAME);
    }

    private function connection(
        ?Credentials $credentials = null,
        bool $configured = true,
        bool $anonymous = false,
        string $usernameField = 'username',
        string $tokenField = 'token',
        ?string $configFile = null,
    ): Connection {
        return new Connection(
            self::URL,
            new Endpoints(),
            $credentials ?? new Credentials(),
            $configFile ?? $this->configPath(),
            $configured,
            anonymous: $anonymous,
            usernameField: $usernameField,
            tokenField: $tokenField,
        );
    }

    private function valid(): int
    {
        return $this->clock->now()->getTimestamp() + 3600;
    }

    private function expired(): int
    {
        return $this->clock->now()->getTimestamp() - 10;
    }

    private function loginResponse(string $jwt): MockResponse
    {
        return new MockResponse('{"meta":{"error":false},"data":{"token":"' . $jwt . '"}}', ['http_code' => 200]);
    }

    public function testAnonymousConnectionHasNoBearer(): void
    {
        $authenticator = $this->authenticator();

        $connection = $this->connection(new Credentials(self::USERNAME, 'secret', 'jwt', $this->valid()), anonymous: true);

        self::assertNull($authenticator->bearer($connection));
        self::assertSame([], $this->sent);
    }

    public function testNotConfiguredConnectionHasNoBearerAndNeverLogsIn(): void
    {
        $authenticator = $this->authenticator();

        $connection = $this->connection(new Credentials(self::USERNAME, 'secret', 'jwt', $this->valid()), configured: false);

        self::assertNull($authenticator->bearer($connection));
        self::assertSame([], $this->sent);
    }

    public function testValidJwtOfTheConfigurationIsReused(): void
    {
        $authenticator = $this->authenticator();

        $bearer = $authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret', 'stored-jwt', $this->valid())));

        self::assertSame('stored-jwt', $bearer);
        self::assertSame([], $this->sent);
        self::assertFileDoesNotExist($this->configPath(), 'Nothing is written without a new login');
    }

    public function testJwtWithoutExpirationIsReused(): void
    {
        self::assertSame('stored-jwt', $this->authenticator()->bearer($this->connection(new Credentials(token: 'stored-jwt'))));
    }

    public function testJwtIsReusedWhenItHasMoreThanTheLeewayLeft(): void
    {
        $expiresAt = $this->clock->now()->getTimestamp() + Credentials::LEEWAY + 1;

        self::assertSame(
            'stored-jwt',
            $this->authenticator()->bearer($this->connection(new Credentials(token: 'stored-jwt', expiresAt: $expiresAt))),
        );
    }

    public function testJwtWithLessThanTheLeewayLeftIsReplacedByANewLoginStoredInTheConfigurationFile(): void
    {
        $expiresAt = $this->clock->now()->getTimestamp() + Credentials::LEEWAY;
        $fresh = ApiHarness::jwt($this->valid());
        $authenticator = $this->authenticator([$this->loginResponse($fresh)]);

        $bearer = $authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret', 'stored-jwt', $expiresAt)));

        self::assertSame($fresh, $bearer);
        self::assertCount(1, $this->sent);

        $stored = (new ConfigFile($this->configPath()))->read();
        self::assertNotNull($stored);
        self::assertSame($fresh, $stored->credentials->token);
        self::assertSame($this->valid(), $stored->credentials->expiresAt);
        self::assertSame('secret', $stored->credentials->apiKey());
        self::assertSame([], $this->warnings->all());
    }

    public function testExpiredJwtWithoutApiKeyGivesNoBearer(): void
    {
        $connection = $this->connection(new Credentials(self::USERNAME, null, 'stored-jwt', $this->expired()));

        self::assertNull($this->authenticator()->bearer($connection));
        self::assertSame([], $this->sent);
    }

    public function testNoJwtAndNoApiKeyGivesNoBearer(): void
    {
        self::assertNull($this->authenticator()->bearer($this->connection()));
        self::assertNull($this->authenticator()->bearer($this->connection(new Credentials(token: ''))));
        self::assertSame([], $this->sent);
    }

    public function testNoJwtWithAnApiKeyLogsIn(): void
    {
        $jwt = ApiHarness::jwt($this->valid());
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);

        self::assertSame($jwt, $authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret'))));
        self::assertCount(1, $this->sent);
    }

    public function testBearerLogsInOnlyOnceForSeveralCalls(): void
    {
        $jwt = ApiHarness::jwt($this->valid());
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret', 'stored-jwt', $this->expired()));

        self::assertSame($jwt, $authenticator->bearer($connection));
        self::assertSame($jwt, $authenticator->bearer($connection), 'The second call must reuse the JWT of the login');
        self::assertCount(1, $this->sent);
    }

    public function testTheJwtOfTheLastLoginWinsOverTheOneOfTheConnection(): void
    {
        $jwt = ApiHarness::jwt($this->valid());
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret', 'stored-jwt', $this->valid()));

        $authenticator->relogin($connection);

        self::assertSame($jwt, $authenticator->bearer($connection));
    }

    public function testAnExpiredJwtOfTheLastLoginFallsBackToTheConnection(): void
    {
        $authenticator = $this->authenticator([$this->loginResponse(ApiHarness::jwt($this->valid()))]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret', 'stored-jwt', $this->valid() + 7200));

        $authenticator->login($connection);
        $this->clock->advance(3600);

        self::assertSame('stored-jwt', $authenticator->bearer($connection));
        self::assertCount(1, $this->sent);
    }

    public function testLoginPostsTheCredentialsAndReturnsTheConnectionWithTheNewJwt(): void
    {
        $jwt = ApiHarness::jwt(1_800_003_600);
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret', 'old-jwt', $this->expired()));

        $logged = $authenticator->login($connection);

        self::assertSame('POST', $this->sent[0]['method']);
        self::assertSame(self::URL . '/api/v1/login', $this->sent[0]['url']);
        self::assertSame('{"username":"key:me@site.test","token":"secret"}', $this->sent[0]['body']);

        self::assertSame($jwt, $logged->credentials->token);
        self::assertSame(1_800_003_600, $logged->credentials->expiresAt);
        self::assertSame(self::USERNAME, $logged->credentials->username);
        self::assertSame('secret', $logged->credentials->apiKey());
        self::assertSame(self::URL, $logged->baseUrl);
        self::assertSame($this->configPath(), $logged->configFile);
        self::assertTrue($logged->configured);
        self::assertSame('old-jwt', $connection->credentials->token, 'The connection is immutable');
        self::assertFileDoesNotExist($this->configPath(), 'The login alone writes nothing');
    }

    public function testLoginUsesTheConfiguredFieldNames(): void
    {
        $authenticator = $this->authenticator([$this->loginResponse(ApiHarness::jwt(1_800_003_600))]);

        $authenticator->login(
            $this->connection(new Credentials(self::USERNAME, 'secret'), usernameField: 'login', tokenField: 'apikey'),
        );

        self::assertSame('{"login":"key:me@site.test","apikey":"secret"}', $this->sent[0]['body']);
    }

    public function testLoginWithATokenWithoutExpirationClaim(): void
    {
        $authenticator = $this->authenticator([$this->loginResponse('opaque-token')]);

        $logged = $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret')));

        self::assertSame('opaque-token', $logged->credentials->token);
        self::assertNull($logged->credentials->expiresAt);
    }

    /**
     * @return iterable<string, array{Credentials}>
     */
    public static function incompleteCredentials(): iterable
    {
        yield 'no api key' => [new Credentials(self::USERNAME)];
        yield 'empty api key' => [new Credentials(self::USERNAME, '')];
        yield 'no username' => [new Credentials(null, 'secret')];
        yield 'empty username' => [new Credentials('', 'secret')];
    }

    #[DataProvider('incompleteCredentials')]
    public function testLoginWithoutCredentialsIsAUsageErrorWithTheHint(Credentials $credentials): void
    {
        $authenticator = $this->authenticator();

        try {
            $authenticator->login($this->connection($credentials));
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('API key', $error->getMessage());
            self::assertStringContainsString('<keyName>:<email>', $error->getMessage());
        }

        self::assertSame([], $this->sent);
    }

    public function testLoginRejectedWithA401AddsTheHint(): void
    {
        $authenticator = $this->authenticator([
            new MockResponse('{"meta":{"error":true},"data":{"code":401,"message":"Invalid credentials."}}', ['http_code' => 401]),
        ]);

        try {
            $authenticator->login($this->connection(new Credentials('me@site.test', 'secret')));
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Auth, $error->kind);
            self::assertSame('Invalid credentials.', $error->getMessage());
            self::assertSame(Authenticator::USERNAME_HINT, $error->extra['hint']);
        }

        self::assertFileDoesNotExist($this->configPath());
    }

    public function testLoginFailureWhichIsNotAnAuthErrorHasNoHint(): void
    {
        $authenticator = $this->authenticator([new MockResponse('Bad gateway', ['http_code' => 502])]);

        try {
            $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret')));
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Server, $error->kind);
            self::assertArrayNotHasKey('hint', $error->extra);
        }
    }

    public function testLoginRedirectedToAnHtmlFormIsAServerError(): void
    {
        $authenticator = $this->authenticator([
            new MockResponse('', ['http_code' => 302, 'response_headers' => ['Location: /login']]),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unexpected HTTP status 302');

        $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidLoginPayloads(): iterable
    {
        yield 'no token' => ['{"meta":{"error":false},"data":{}}'];
        yield 'empty token' => ['{"meta":{"error":false},"data":{"token":""}}'];
        yield 'token is not a string' => ['{"meta":{"error":false},"data":{"token":42}}'];
        yield 'data is not an object' => ['{"meta":{"error":false},"data":"token"}'];
        yield 'no data' => ['{"meta":{"error":false}}'];
        yield 'not json' => ['OK'];
    }

    #[DataProvider('invalidLoginPayloads')]
    public function testLoginResponseWithoutTokenIsAServerError(string $payload): void
    {
        $authenticator = $this->authenticator([new MockResponse($payload, ['http_code' => 200])]);

        try {
            $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret')));
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Server, $error->kind);
            self::assertSame('The login response does not contain a token', $error->getMessage());
        }

        self::assertFileDoesNotExist($this->configPath());
    }

    public function testCanRelogin(): void
    {
        $authenticator = $this->authenticator();

        self::assertTrue($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME, 'secret'))));
        self::assertTrue($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME, 'secret', 'jwt', $this->valid()))));
        self::assertFalse($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME))));
        self::assertFalse($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME, 'secret'), configured: false)));
        self::assertFalse($authenticator->canRelogin($this->connection()));
    }

    public function testCanNotReloginAfterALoginOfThisProcess(): void
    {
        $authenticator = $this->authenticator([$this->loginResponse(ApiHarness::jwt(1_800_003_600))]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret'));

        self::assertTrue($authenticator->canRelogin($connection));
        $authenticator->login($connection);

        self::assertFalse($authenticator->canRelogin($connection));
    }

    public function testReloginLogsInAndStoresTheWholeConfiguration(): void
    {
        $jwt = ApiHarness::jwt(1_800_003_600);
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);
        $connection = new Connection(
            self::URL,
            new Endpoints('/api/v2', '/api/v2/cms', '/auth'),
            new Credentials(self::USERNAME, 'secret', 'revoked', $this->valid()),
            $this->configPath(),
            true,
            insecure: true,
            timeout: 12,
            usernameField: 'login',
            tokenField: 'apikey',
        );

        $logged = $authenticator->relogin($connection);

        self::assertSame($jwt, $logged->credentials->token);
        self::assertSame(self::URL . '/auth', $this->sent[0]['url']);

        $stored = (new ConfigFile($this->configPath()))->read();
        self::assertNotNull($stored);
        self::assertSame($jwt, $stored->credentials->token);
        self::assertSame(1_800_003_600, $stored->credentials->expiresAt);
        self::assertSame(self::USERNAME, $stored->credentials->username);
        self::assertSame('secret', $stored->credentials->apiKey());
        self::assertSame('/api/v2', $stored->endpoints->apiPrefix());
        self::assertSame('/api/v2/cms', $stored->endpoints->adminPrefix());
        self::assertSame('/auth', $stored->endpoints->login());
        self::assertTrue($stored->insecure);
        self::assertSame(12, $stored->timeout);
        self::assertSame('login', $stored->usernameField);
        self::assertSame('apikey', $stored->tokenField);
        self::assertSame([], $this->warnings->all());
    }

    public function testPersistWritesTheConfigurationFile(): void
    {
        $this->authenticator()->persist($this->connection(new Credentials(self::USERNAME, 'secret', 'jwt', 1_800_003_600)));

        $stored = (new ConfigFile($this->configPath()))->read();
        self::assertNotNull($stored);
        self::assertSame('jwt', $stored->credentials->token);
        self::assertSame([], $this->warnings->all());
    }

    public function testPersistFailureIsAWarningAndNotAnError(): void
    {
        $blocker = $this->temp->write('blocker', 'I am a file');

        $this->authenticator()->persist(
            $this->connection(new Credentials(self::USERNAME, 'secret', 'jwt'), configFile: $blocker . '/sub/east-website.json'),
        );

        self::assertCount(1, $this->warnings->all());
        self::assertStringContainsString('can not be written', $this->warnings->all()[0]);
    }

    public function testBearerSurvivesAnUnwritableConfigurationFile(): void
    {
        $blocker = $this->temp->write('blocker', 'I am a file');
        $jwt = ApiHarness::jwt($this->valid());
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);

        $bearer = $authenticator->bearer(
            $this->connection(new Credentials(self::USERNAME, 'secret'), configFile: $blocker . '/sub/east-website.json'),
        );

        self::assertSame($jwt, $bearer);
        self::assertCount(1, $this->warnings->all());
    }

    public function testUsernameHintDescribesTheFormat(): void
    {
        self::assertStringContainsString('<keyName>:<email>', Authenticator::USERNAME_HINT);
    }
}
