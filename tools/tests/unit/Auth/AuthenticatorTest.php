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
use Teknoo\East\Website\Tools\Auth\Session;
use Teknoo\East\Website\Tools\Auth\SessionFile;
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
 * Tests of the authenticator: a JWT given explicitly, then a valid stored session, then a new login
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

    private function connection(
        ?Credentials $credentials = null,
        bool $useSession = true,
        ?string $sessionPath = null,
        bool $anonymous = false,
        string $usernameField = 'username',
        string $tokenField = 'token',
        ?string $baseUrl = null,
    ): Connection {
        return new Connection(
            $baseUrl ?? self::URL,
            new Endpoints(),
            $credentials ?? new Credentials(),
            $useSession,
            $sessionPath ?? $this->temp->path('session.json'),
            anonymous: $anonymous,
            usernameField: $usernameField,
            tokenField: $tokenField,
        );
    }

    private function store(int $expiresAt, string $token = 'stored-jwt', string $baseUrl = self::URL): void
    {
        (new SessionFile($this->temp->path('session.json')))->write(new Session($baseUrl, self::USERNAME, $token, $expiresAt));
    }

    private function loginResponse(string $jwt): MockResponse
    {
        return new MockResponse('{"meta":{"error":false},"data":{"token":"' . $jwt . '"}}', ['http_code' => 200]);
    }

    public function testAnonymousConnectionHasNoBearer(): void
    {
        $authenticator = $this->authenticator();

        self::assertNull($authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret', 'jwt'), anonymous: true)));
        self::assertSame([], $this->sent);
    }

    public function testExplicitTokenWinsOverEverything(): void
    {
        $this->store($this->clock->now()->getTimestamp() + 3600);
        $authenticator = $this->authenticator();

        $bearer = $authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret', 'given-jwt')));

        self::assertSame('given-jwt', $bearer);
        self::assertSame([], $this->sent);
    }

    public function testEmptyExplicitTokenIsIgnored(): void
    {
        $authenticator = $this->authenticator();

        self::assertNull($authenticator->bearer($this->connection(new Credentials(token: ''))));
    }

    public function testValidStoredSessionIsReused(): void
    {
        $this->store($this->clock->now()->getTimestamp() + 3600);
        $authenticator = $this->authenticator();

        $bearer = $authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret')));

        self::assertSame('stored-jwt', $bearer);
        self::assertSame([], $this->sent);
    }

    public function testStoredSessionIsReusedWithoutAnyCredentials(): void
    {
        $this->store($this->clock->now()->getTimestamp() + 3600);

        self::assertSame('stored-jwt', $this->authenticator()->bearer($this->connection()));
    }

    public function testStoredSessionIsReusedWhenItHasMoreThanTheLeewayLeft(): void
    {
        $this->store($this->clock->now()->getTimestamp() + Session::LEEWAY + 1);

        self::assertSame('stored-jwt', $this->authenticator()->bearer($this->connection()));
    }

    public function testStoredSessionWithLessThanTheLeewayLeftIsNotReused(): void
    {
        $this->store($this->clock->now()->getTimestamp() + Session::LEEWAY);
        $fresh = ApiHarness::jwt($this->clock->now()->getTimestamp() + 3600);
        $authenticator = $this->authenticator([$this->loginResponse($fresh)]);

        $bearer = $authenticator->bearer($this->connection(new Credentials(self::USERNAME, 'secret')));

        self::assertSame($fresh, $bearer);
        self::assertCount(1, $this->sent);
    }

    public function testExpiredStoredSessionWithoutCredentialsGivesNoBearer(): void
    {
        $this->store($this->clock->now()->getTimestamp() - 10);

        self::assertNull($this->authenticator()->bearer($this->connection()));
        self::assertSame([], $this->sent);
    }

    public function testStoredSessionOfAnotherSiteOrUserIsNotUsed(): void
    {
        $this->store($this->clock->now()->getTimestamp() + 3600, baseUrl: 'https://other.test');

        self::assertNull($this->authenticator()->bearer($this->connection()));
        self::assertNull($this->authenticator()->bearer($this->connection(new Credentials('other:someone@site.test'))));
    }

    public function testSessionsCanBeDisabled(): void
    {
        $this->store($this->clock->now()->getTimestamp() + 3600);
        $authenticator = $this->authenticator();

        self::assertNull($authenticator->bearer($this->connection(useSession: false)));
        self::assertNull($authenticator->stored($this->connection(useSession: false)));
    }

    public function testStoredWithoutPathIsNoSession(): void
    {
        $connection = new Connection(self::URL, new Endpoints(), new Credentials(), true, null);

        self::assertNull($this->authenticator()->stored($connection));
    }

    public function testStoredReturnsTheMatchingSession(): void
    {
        $this->store(1_800_003_600);

        $session = $this->authenticator()->stored($this->connection(new Credentials(self::USERNAME)));

        self::assertNotNull($session);
        self::assertSame('stored-jwt', $session->token);
    }

    public function testNoCredentialsAndNoSessionGivesNoBearer(): void
    {
        self::assertNull($this->authenticator()->bearer($this->connection()));
    }

    public function testLoginPostsTheCredentialsAndStoresTheSession(): void
    {
        $jwt = ApiHarness::jwt(1_800_003_600);
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);

        $session = $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret')));

        self::assertSame('POST', $this->sent[0]['method']);
        self::assertSame(self::URL . '/api/v1/login', $this->sent[0]['url']);
        self::assertSame('{"username":"key:me@site.test","token":"secret"}', $this->sent[0]['body']);

        self::assertSame($jwt, $session->token);
        self::assertSame(self::URL, $session->baseUrl);
        self::assertSame(self::USERNAME, $session->username);
        self::assertSame(1_800_003_600, $session->expiresAt);

        $stored = (new SessionFile($this->temp->path('session.json')))->read();
        self::assertNotNull($stored);
        self::assertSame($jwt, $stored->token);
        self::assertSame([], $this->warnings->all());
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

        $session = $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret')));

        self::assertNull($session->expiresAt);
    }

    public function testLoginWithoutSessionStorageStoresNothing(): void
    {
        $authenticator = $this->authenticator([$this->loginResponse('opaque-token')]);

        $authenticator->login($this->connection(new Credentials(self::USERNAME, 'secret'), useSession: false));

        self::assertFileDoesNotExist($this->temp->path('session.json'));
        self::assertSame([], $this->warnings->all());
    }

    public function testLoginWithoutCredentialsIsAUsageErrorWithTheHint(): void
    {
        $authenticator = $this->authenticator();

        try {
            $authenticator->login($this->connection(new Credentials(self::USERNAME)));
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

        self::assertFileDoesNotExist($this->temp->path('session.json'));
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

        self::assertFileDoesNotExist($this->temp->path('session.json'));
    }

    public function testCanRelogin(): void
    {
        $authenticator = $this->authenticator();

        self::assertTrue($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME, 'secret'))));
        self::assertFalse($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME))));
        self::assertFalse($authenticator->canRelogin($this->connection()));
        self::assertFalse($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME, 'secret', 'given-jwt'))));
        self::assertTrue($authenticator->canRelogin($this->connection(new Credentials(self::USERNAME, 'secret', ''))));
    }

    public function testCanNotReloginAfterALoginOfThisProcess(): void
    {
        $authenticator = $this->authenticator([$this->loginResponse(ApiHarness::jwt(1_800_003_600))]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret'));

        self::assertTrue($authenticator->canRelogin($connection));
        $authenticator->login($connection);

        self::assertFalse($authenticator->canRelogin($connection));
    }

    public function testBearerLogsInOnlyOnceForSeveralCalls(): void
    {
        $jwt = ApiHarness::jwt(1_800_003_600);
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);
        $connection = $this->connection(new Credentials(self::USERNAME, 'secret'));

        self::assertSame($jwt, $authenticator->bearer($connection));
        self::assertSame($jwt, $authenticator->bearer($connection), 'The second call must reuse the stored session');
        self::assertCount(1, $this->sent);
    }

    public function testPersistWithoutSessionsDoesNothing(): void
    {
        $this->authenticator()->persist(
            $this->connection(useSession: false),
            new Session(self::URL, self::USERNAME, 'jwt', null),
        );

        self::assertFileDoesNotExist($this->temp->path('session.json'));
        self::assertSame([], $this->warnings->all());
    }

    public function testPersistWithoutPathWarns(): void
    {
        $connection = new Connection(self::URL, new Endpoints(), new Credentials(), true, null);

        $this->authenticator()->persist($connection, new Session(self::URL, self::USERNAME, 'jwt', null));

        self::assertCount(1, $this->warnings->all());
        self::assertStringContainsString('EAST_WEBSITE_SESSION_FILE', $this->warnings->all()[0]);
    }

    public function testPersistWritesTheSession(): void
    {
        $this->authenticator()->persist($this->connection(), new Session(self::URL, self::USERNAME, 'jwt', 1_800_003_600));

        self::assertSame('jwt', (new SessionFile($this->temp->path('session.json')))->read()?->token);
        self::assertSame([], $this->warnings->all());
    }

    public function testPersistFailureIsAWarningAndNotAnError(): void
    {
        $blocker = $this->temp->write('blocker', 'I am a file');
        $connection = $this->connection(sessionPath: $blocker . '/state/session.json');

        $this->authenticator()->persist($connection, new Session(self::URL, self::USERNAME, 'jwt', null));

        self::assertCount(1, $this->warnings->all());
        self::assertStringContainsString('can not be written', $this->warnings->all()[0]);
    }

    public function testLoginSurvivesAnUnwritableSessionStorage(): void
    {
        $blocker = $this->temp->write('blocker', 'I am a file');
        $jwt = ApiHarness::jwt(1_800_003_600);
        $authenticator = $this->authenticator([$this->loginResponse($jwt)]);

        $session = $authenticator->login(
            $this->connection(new Credentials(self::USERNAME, 'secret'), sessionPath: $blocker . '/state/session.json'),
        );

        self::assertSame($jwt, $session->token);
        self::assertCount(1, $this->warnings->all());
    }

    public function testUsernameHintDescribesTheFormat(): void
    {
        self::assertStringContainsString('<keyName>:<email>', Authenticator::USERNAME_HINT);
    }
}
