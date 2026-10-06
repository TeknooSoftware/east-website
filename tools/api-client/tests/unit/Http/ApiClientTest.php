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

namespace Teknoo\Tests\East\Website\Tools\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiClient;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Warnings;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;
use Teknoo\Tests\East\Website\Tools\Support\FixedClock;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

/**
 * Tests of the client of the API: configuration file required, authentication, replay after a 401, and follow of the
 * redirection of a creation
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ApiClient::class)]
class ApiClientTest extends TestCase
{
    private const string URL = 'https://site.test';

    /**
     * @var list<array{method: string, url: string, auth: string|null}>
     */
    private array $sent = [];

    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    private function configFile(): string
    {
        $this->temp ??= new TempDir();

        return $this->temp->path('east-website.json');
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function client(array $responses, ?FixedClock $clock = null): ApiClient
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $auth = null;
            foreach ($options['headers'] ?? [] as $header) {
                if (0 === stripos((string) $header, 'authorization: ')) {
                    $auth = substr((string) $header, 15);
                }
            }

            $this->sent[] = ['method' => $method, 'url' => $url, 'auth' => $auth];
            $response = array_shift($responses);
            self::assertInstanceOf(MockResponse::class, $response, 'Unexpected request ' . $method . ' ' . $url);

            return $response;
        });

        $transport = new Transport($http);

        return new ApiClient($transport, new Authenticator($transport, $clock ?? new FixedClock(), new Warnings()));
    }

    /**
     * A connection read from its configuration file, like the commands get it after the login.
     */
    private function connection(Credentials $credentials, bool $configured = true): Connection
    {
        return new Connection(
            self::URL,
            new Endpoints(),
            $credentials,
            configFile: $this->configFile(),
            configured: $configured,
        );
    }

    private function json(int $status, string $body, array $headers = []): MockResponse
    {
        return new MockResponse($body, ['http_code' => $status, 'response_headers' => $headers]);
    }

    public function testWithoutConfigurationFileNothingIsSentEvenForThePublicApi(): void
    {
        $client = $this->client([]);

        foreach ([true, false] as $authRequired) {
            try {
                $client->call(
                    $this->connection(new Credentials(), false),
                    ApiRequest::get('/api/v1/posts'),
                    $authRequired,
                );
                self::fail('An exception was expected');
            } catch (ApiException $error) {
                self::assertSame(ErrorKind::Auth, $error->kind);
                self::assertSame(3, $error->getCode());
                self::assertStringContainsString('No configuration file "' . $this->configFile() . '"', $error->getMessage());
                self::assertStringContainsString('website:auth:login', $error->getMessage());
                self::assertArrayHasKey('hint', $error->extra);
            }
        }

        self::assertSame([], $this->sent);
    }

    public function testConfigurationWithoutValidJwtNorApiKeyFailsWithAnAuthError(): void
    {
        $client = $this->client([]);

        try {
            $client->call(
                $this->connection(new Credentials('key:me@site.test', null, 'expired', 1_799_000_000)),
                ApiRequest::get('/api/v1/admin/tags'),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Auth, $error->kind);
            self::assertStringContainsString('no valid JWT and no API key', $error->getMessage());
            self::assertStringContainsString('website:auth:login', $error->getMessage());
            self::assertArrayHasKey('hint', $error->extra);
        }

        self::assertSame([], $this->sent);
    }

    public function testOptionalAuthSendsTheRequestWithoutBearer(): void
    {
        $client = $this->client([$this->json(200, '{"meta":{},"data":[]}')]);

        $response = $client->call($this->connection(new Credentials()), ApiRequest::get('/api/v1/posts'), false);

        self::assertSame(200, $response->status);
        self::assertNull($this->sent[0]['auth']);
    }

    public function testTheJwtOfTheConfigurationIsSentAsBearer(): void
    {
        $client = $this->client([$this->json(200, '{"meta":{},"data":[]}')]);

        $client->call($this->connection(new Credentials(token: 'jwt-token')), ApiRequest::get('/api/v1/admin/tags'));

        self::assertSame('Bearer jwt-token', $this->sent[0]['auth']);
        self::assertFileDoesNotExist($this->configFile(), 'A valid JWT does not rewrite the configuration file');
    }

    public function testLoginIsDoneOnceWithTheApiKeyOfTheConfigurationAndTheJwtIsStored(): void
    {
        $jwt = ApiHarness::jwt(1_800_003_600);
        $client = $this->client([
            $this->json(200, '{"meta":{"error":false},"data":{"token":"' . $jwt . '"}}'),
            $this->json(200, '{"meta":{},"data":[]}'),
            $this->json(200, '{"meta":{},"data":[]}'),
        ]);
        $connection = $this->connection(new Credentials('key:me@site.test', 'secret'));

        $client->call($connection, ApiRequest::get('/api/v1/admin/tags'));
        $client->call($connection, ApiRequest::get('/api/v1/admin/tags'));

        self::assertCount(3, $this->sent);
        self::assertSame('POST', $this->sent[0]['method']);
        self::assertSame(self::URL . '/api/v1/login', $this->sent[0]['url']);
        self::assertNull($this->sent[0]['auth']);
        self::assertSame('Bearer ' . $jwt, $this->sent[1]['auth']);
        self::assertSame('Bearer ' . $jwt, $this->sent[2]['auth'], 'The new JWT is reused by the next requests');

        $stored = json_decode((string) file_get_contents($this->configFile()), true);
        self::assertSame($jwt, $stored['token']);
        self::assertSame(1_800_003_600, $stored['expiresAt']);
        self::assertSame('secret', $stored['apiKey']);
    }

    public function test401IsReplayedOnceAfterANewLoginWithTheApiKeyOfTheConfiguration(): void
    {
        $fresh = ApiHarness::jwt(1_800_007_200);
        $client = $this->client([
            $this->json(401, '{"meta":{"error":true},"data":{"code":401,"message":"Invalid JWT Token"}}'),
            $this->json(200, '{"meta":{},"data":{"token":"' . $fresh . '"}}'),
            $this->json(200, '{"meta":{},"data":[]}'),
        ]);

        $response = $client->call(
            $this->connection(new Credentials('key:me@site.test', 'secret', 'revoked', 1_800_003_600)),
            ApiRequest::get('/api/v1/admin/tags'),
        );

        self::assertSame(200, $response->status);
        self::assertCount(3, $this->sent);
        self::assertSame('Bearer revoked', $this->sent[0]['auth']);
        self::assertSame('/api/v1/login', parse_url($this->sent[1]['url'], PHP_URL_PATH));
        self::assertSame('Bearer ' . $fresh, $this->sent[2]['auth']);
        self::assertStringContainsString($fresh, (string) file_get_contents($this->configFile()));
    }

    public function test401RightAfterALoginOfThisProcessIsNotReplayed(): void
    {
        $client = $this->client([
            $this->json(200, '{"meta":{},"data":{"token":"' . ApiHarness::jwt(1_800_003_600) . '"}}'),
            $this->json(401, '{"meta":{"error":true},"data":{"code":401,"message":"Invalid JWT Token"}}'),
        ]);

        try {
            $client->call(
                $this->connection(new Credentials('key:me@site.test', 'secret')),
                ApiRequest::get('/api/v1/admin/tags'),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Auth, $error->kind);
        }

        self::assertCount(2, $this->sent);
    }

    public function test401IsNotReplayedWithoutApiKeyInTheConfiguration(): void
    {
        $client = $this->client([
            $this->json(401, '{"meta":{"error":true},"data":{"code":401,"message":"Expired JWT Token"}}'),
        ]);

        try {
            $client->call(
                $this->connection(new Credentials('key:me@site.test', null, 'revoked')),
                ApiRequest::get('/api/v1/admin/tags'),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Auth, $error->kind);
            self::assertSame('Expired JWT Token', $error->getMessage());
        }

        self::assertCount(1, $this->sent);
    }

    public function testLoginFailureAddsTheHintAboutTheUsernameFormat(): void
    {
        $client = $this->client([
            $this->json(401, '{"meta":{"error":true},"data":{"code":401,"message":"Invalid credentials."}}'),
        ]);

        try {
            $client->call(
                $this->connection(new Credentials('me@site.test', 'secret')),
                ApiRequest::get('/api/v1/admin/tags'),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame('Invalid credentials.', $error->getMessage());
            self::assertStringContainsString('<keyName>:<email>', (string) $error->extra['hint']);
        }
    }

    public function testCreateFollowsTheLocationWithExplicitGet(): void
    {
        $client = $this->client([
            $this->json(302, '<html>redirect</html>', ['Location: /api/v1/admin/tag/tag-1']),
            $this->json(200, '{"meta":{"id":"tag-1"},"data":{"id":"tag-1","name":"x"}}'),
        ]);

        $response = $client->create(
            $this->connection(new Credentials(token: 't')),
            ApiRequest::json('POST', '/api/v1/admin/tag/new', ['name' => 'x']),
        );

        self::assertSame('tag-1', $response->id());
        self::assertSame('GET', $this->sent[1]['method']);
        self::assertSame(self::URL . '/api/v1/admin/tag/tag-1', $this->sent[1]['url']);
        self::assertSame('Bearer t', $this->sent[1]['auth']);
    }

    public function testCreateCarriesTheQueryOfTheCreationToTheFetch(): void
    {
        $client = $this->client([
            $this->json(302, '', ['Location: /api/v1/admin/content/c-1']),
            $this->json(200, '{"meta":{"id":"c-1"},"data":{"id":"c-1"}}'),
        ]);

        $client->create(
            $this->connection(new Credentials(token: 't')),
            ApiRequest::json('POST', '/api/v1/admin/content/new', [], ['locale' => 'fr']),
        );

        self::assertSame(self::URL . '/api/v1/admin/content/c-1?locale=fr', $this->sent[1]['url']);
    }

    public function testCreateReturnsASyntheticDocumentWhenTheObjectCanNotBeFetched(): void
    {
        $client = $this->client([
            $this->json(302, '', ['Location: /api/v1/admin/tag/tag-9']),
            $this->json(500, '{"meta":{"error":true},"data":{"code":500,"message":"Internal Server Error"}}'),
        ]);

        $response = $client->create(
            $this->connection(new Credentials(token: 't')),
            ApiRequest::json('POST', '/api/v1/admin/tag/new', []),
        );

        self::assertSame(200, $response->status);
        self::assertSame('tag-9', $response->id());
        self::assertStringContainsString('Created, but the object can not be fetched', (string) $response->meta()['warning']);
        self::assertSame('/api/v1/admin/tag/tag-9', $response->meta()['location']);
    }

    public function testCreateWithoutFetchReadsTheIdFromTheQueryOfTheLocation(): void
    {
        $client = $this->client([
            $this->json(302, '', ['Location: /api/v1/post/my-post?id=comment-7']),
        ]);

        $response = $client->create(
            $this->connection(new Credentials(token: 't')),
            ApiRequest::json('POST', '/api/v1/post/my-post/comment', ['author' => 'a']),
            false,
        );

        self::assertSame('comment-7', $response->id());
        self::assertCount(1, $this->sent);
    }

    public function testCreateRefusesACrossOriginLocation(): void
    {
        $client = $this->client([$this->json(302, '', ['Location: https://evil.test/api/v1/admin/tag/1'])]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unexpected redirection');

        $client->create(
            $this->connection(new Credentials(token: 't')),
            ApiRequest::json('POST', '/api/v1/admin/tag/new', []),
        );
    }

    public function testCreateRefusesALocationOutOfTheApiAndDetectsALoginRedirection(): void
    {
        $client = $this->client([$this->json(302, '', ['Location: /login'])]);

        try {
            $client->create(
                $this->connection(new Credentials(token: 't')),
                ApiRequest::json('POST', '/api/v1/admin/tag/new', []),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Auth, $error->kind);
        }
    }

    public function testCreateRefusesARedirectionWithoutLocation(): void
    {
        $client = $this->client([$this->json(302, '')]);

        try {
            $client->create(
                $this->connection(new Credentials(token: 't')),
                ApiRequest::json('POST', '/api/v1/admin/tag/new', []),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Server, $error->kind);
        }
    }

    public function testCreateReturnsTheBodyWhenTheApiAnswersDirectlyWithTheObject(): void
    {
        $client = $this->client([$this->json(200, '{"meta":{"id":"x"},"data":{"id":"x"}}')]);

        $response = $client->create(
            $this->connection(new Credentials(token: 't')),
            ApiRequest::json('POST', '/api/v1/admin/tag/new', []),
        );

        self::assertSame('x', $response->id());
    }

    public function testCreateConvertsErrorsToExceptions(): void
    {
        $client = $this->client([
            $this->json(400, '{"meta":{"errors":true},"data":{".name":"Invalid"}}'),
        ]);

        try {
            $client->create(
                $this->connection(new Credentials(token: 't')),
                ApiRequest::json('POST', '/api/v1/admin/tag/new', []),
            );
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Validation, $error->kind);
            self::assertSame(['.name' => 'Invalid'], $error->fields);
        }
    }

    public function testCallConvertsErrorsToExceptions(): void
    {
        $client = $this->client([$this->json(404, '{"meta":{"error":true},"data":{"code":404,"message":"Not found"}}')]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Not found');

        $client->call($this->connection(new Credentials(token: 't')), ApiRequest::get('/api/v1/admin/tag/x'));
    }

    public function testAnonymousConnectionSendsNoBearerEvenForRequiredAuth(): void
    {
        $client = $this->client([$this->json(200, '{"meta":{},"data":[]}')]);
        $connection = $this->connection(new Credentials(token: 't'))->asAnonymous();

        $client->call($connection, ApiRequest::get('/api/v1/admin/tags'));

        self::assertNull($this->sent[0]['auth']);
    }
}
