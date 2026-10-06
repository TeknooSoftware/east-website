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
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\ErrorKind;

/**
 * Tests of the description of the API to reach: URLs, plain http protection, and validation of the redirections
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Connection::class)]
class ConnectionTest extends TestCase
{
    private function connection(
        string $baseUrl = 'https://site.test',
        bool $allowHttp = false,
        ?Endpoints $endpoints = null,
    ): Connection {
        return new Connection($baseUrl, $endpoints ?? new Endpoints(), new Credentials(), allowHttp: $allowHttp);
    }

    public function testDefaults(): void
    {
        $connection = new Connection('https://site.test', new Endpoints(), new Credentials());

        self::assertSame('', $connection->configFile);
        self::assertFalse($connection->configured);
        self::assertFalse($connection->insecure);
        self::assertFalse($connection->allowHttp);
        self::assertFalse($connection->anonymous);
        self::assertSame(30, Connection::DEFAULT_TIMEOUT);
        self::assertSame(Connection::DEFAULT_TIMEOUT, $connection->timeout);
        self::assertSame('username', $connection->usernameField);
        self::assertSame('token', $connection->tokenField);
    }

    private function complete(bool $anonymous): Connection
    {
        return new Connection(
            'https://site.test',
            new Endpoints('/api/v2'),
            new Credentials('old', 'secret', 'jwt', 1_800_003_600),
            '/tmp/east-website.json',
            true,
            true,
            true,
            $anonymous,
            12,
            'login',
            'secret',
        );
    }

    public function testWithCredentialsKeepsTheOtherSettings(): void
    {
        $connection = $this->complete(true);

        $copy = $connection->withCredentials(new Credentials('new'));

        self::assertNotSame($connection, $copy);
        self::assertSame('new', $copy->credentials->username);
        self::assertSame('old', $connection->credentials->username);
        self::assertSame('https://site.test', $copy->baseUrl);
        self::assertSame($connection->endpoints, $copy->endpoints);
        self::assertSame('/tmp/east-website.json', $copy->configFile);
        self::assertTrue($copy->configured);
        self::assertTrue($copy->insecure);
        self::assertTrue($copy->allowHttp);
        self::assertTrue($copy->anonymous);
        self::assertSame(12, $copy->timeout);
        self::assertSame('login', $copy->usernameField);
        self::assertSame('secret', $copy->tokenField);
    }

    public function testAsAnonymousKeepsTheConfiguration(): void
    {
        $connection = $this->complete(false);

        $copy = $connection->asAnonymous();

        self::assertNotSame($connection, $copy);
        self::assertTrue($copy->anonymous);
        self::assertFalse($connection->anonymous);
        self::assertSame('https://site.test', $copy->baseUrl);
        self::assertSame($connection->endpoints, $copy->endpoints);
        self::assertSame($connection->credentials, $copy->credentials);
        self::assertSame('/tmp/east-website.json', $copy->configFile);
        self::assertTrue($copy->configured);
        self::assertTrue($copy->insecure);
        self::assertTrue($copy->allowHttp);
        self::assertSame(12, $copy->timeout);
        self::assertSame('login', $copy->usernameField);
        self::assertSame('secret', $copy->tokenField);
    }

    public function testUrl(): void
    {
        self::assertSame(
            'https://site.test/api/v1/admin/tags?page=2&order=name',
            $this->connection()->url('/api/v1/admin/tags', ['page' => 2, 'order' => 'name']),
        );
    }

    public function testUrlWithoutQueryAndWithTrailingSlashInTheBaseUrl(): void
    {
        self::assertSame(
            'https://site.test/sub/api/v1/posts',
            $this->connection('https://site.test/sub/')->url('/api/v1/posts'),
        );
    }

    public function testUrlEncodesTheQuery(): void
    {
        self::assertSame(
            'https://site.test/api?q=a%20b%26c&flag=1',
            $this->connection()->url('/api', ['q' => 'a b&c', 'flag' => true]),
        );
    }

    public function testOriginDropsThePath(): void
    {
        self::assertSame('https://site.test', $this->connection('https://site.test/sub/path')->origin());
    }

    public function testOriginIsLowerCasedAndKeepsThePort(): void
    {
        self::assertSame('https://site.test:8443', $this->connection('HTTPS://Site.TEST:8443/x')->origin());
    }

    public function testOriginOfAnIpv6Loopback(): void
    {
        self::assertSame('http://[::1]:8080', $this->connection('http://[::1]:8080')->origin());
    }

    public function testOriginUrl(): void
    {
        $connection = $this->connection('https://site.test/sub');

        self::assertSame('https://site.test/api/v1/x', $connection->originUrl('/api/v1/x'));
        self::assertSame('https://site.test/api/v1/x?locale=fr', $connection->originUrl('/api/v1/x', ['locale' => 'fr']));
    }

    public function testOriginUrlAppendsToAnExistingQuery(): void
    {
        self::assertSame(
            'https://site.test/api/v1/post/p?id=c-1&locale=fr',
            $this->connection()->originUrl('/api/v1/post/p?id=c-1', ['locale' => 'fr']),
        );
    }

    public function testDisplayUrlWithoutBaseUrlIsAPlaceholder(): void
    {
        $connection = $this->connection('');

        self::assertSame('<base-url>/api/v1/posts', $connection->displayUrl('/api/v1/posts', [], false));
        self::assertSame('<base-url>/api/v1/posts?page=2', $connection->displayUrl('/api/v1/posts', ['page' => 2], true));
    }

    public function testDisplayUrl(): void
    {
        $connection = $this->connection('https://site.test/sub');

        self::assertSame('https://site.test/sub/api/v1/posts', $connection->displayUrl('/api/v1/posts', [], false));
        self::assertSame('https://site.test/api/v1/posts', $connection->displayUrl('/api/v1/posts', [], true));
    }

    public function testMissingBaseUrlIsAUsageError(): void
    {
        try {
            $this->connection('')->url('/api');
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('login first with website:auth:login --url=', $error->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBaseUrls(): iterable
    {
        yield 'no scheme' => ['site.test'];
        yield 'unsupported scheme' => ['ftp://site.test'];
        yield 'no host' => ['https://'];
        yield 'relative' => ['/api/v1'];
        yield 'scheme relative' => ['//site.test'];
    }

    #[DataProvider('invalidBaseUrls')]
    public function testInvalidBaseUrlIsAUsageError(string $baseUrl): void
    {
        try {
            $this->connection($baseUrl)->url('/api');
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function loopbackUrls(): iterable
    {
        yield 'localhost' => ['http://localhost'];
        yield 'localhost with port' => ['http://localhost:8080'];
        yield 'ipv4' => ['http://127.0.0.1:8000'];
        yield 'ipv6' => ['http://[::1]:8000'];
        yield 'localhost sub domain' => ['http://api.localhost'];
        yield 'upper case' => ['http://LOCALHOST'];
    }

    #[DataProvider('loopbackUrls')]
    public function testPlainHttpIsAllowedToALoopbackHost(string $baseUrl): void
    {
        self::assertStringStartsWith('http://', $this->connection($baseUrl)->url('/api'));
    }

    public function testPlainHttpToARemoteHostIsRefused(): void
    {
        try {
            $this->connection('http://site.test')->url('/api');
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('--allow-http', $error->getMessage());
        }
    }

    public function testPlainHttpToARemoteHostCanBeAllowed(): void
    {
        self::assertSame('http://site.test/api', $this->connection('http://site.test', true)->url('/api'));
    }

    public function testPlainHttpIsRefusedForTheOriginToo(): void
    {
        $this->expectException(ApiException::class);

        $this->connection('http://site.test')->originUrl('/api');
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function locations(): iterable
    {
        yield 'admin path' => ['/api/v1/admin/tag/t-1', '/api/v1/admin/tag/t-1'];
        yield 'api path with query' => ['/api/v1/post/p?id=c-1', '/api/v1/post/p?id=c-1'];
        yield 'exactly the api prefix' => ['/api/v1', '/api/v1'];
        yield 'absolute url of the same origin' => ['https://site.test/api/v1/admin/tag/t-1', '/api/v1/admin/tag/t-1'];
        yield 'same origin in another case' => ['HTTPS://SITE.TEST/api/v1/admin/tag/t-1', '/api/v1/admin/tag/t-1'];
        yield 'another origin' => ['https://evil.test/api/v1/admin/tag/t-1', null];
        yield 'another scheme' => ['http://site.test/api/v1/admin/tag/t-1', null];
        yield 'another port' => ['https://site.test:8443/api/v1/admin/tag/t-1', null];
        yield 'outside of the api' => ['/login', null];
        yield 'prefix lookalike' => ['/api/v10/admin/tag/t-1', null];
        yield 'relative path' => ['api/v1/admin/tag/t-1', null];
        yield 'no path' => ['?id=1', null];
        yield 'malformed' => ['http:///api/v1', null];
    }

    #[DataProvider('locations')]
    public function testPathFromLocation(string $location, ?string $expected): void
    {
        self::assertSame($expected, $this->connection()->pathFromLocation($location));
    }

    public function testPathFromLocationWithABasePath(): void
    {
        $connection = $this->connection('https://site.test/sub');

        self::assertSame('/sub/api/v1/admin/tag/t-1', $connection->pathFromLocation('/sub/api/v1/admin/tag/t-1'));
        self::assertNull($connection->pathFromLocation('/api/v1/admin/tag/t-1'));
    }

    public function testPathFromLocationFollowsTheConfiguredPrefixes(): void
    {
        $connection = $this->connection(endpoints: new Endpoints('/rest', '/rest/back'));

        self::assertSame('/rest/back/tag/t-1', $connection->pathFromLocation('/rest/back/tag/t-1'));
        self::assertSame('/rest/post/p', $connection->pathFromLocation('/rest/post/p'));
        self::assertNull($connection->pathFromLocation('/api/v1/admin/tag/t-1'));
    }
}
