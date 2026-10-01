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
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Version;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function array_shift;
use function count;
use function get_resources;
use function strtolower;

/**
 * Tests of the low level transport: headers, no redirection followed, errors not thrown, exceptions of the network converted
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Transport::class)]
class TransportTest extends TestCase
{
    /**
     * @var list<array{method: string, url: string, options: array<string, mixed>}>
     */
    private array $sent = [];

    /**
     * @param list<MockResponse|TransportException> $responses
     */
    private function transport(array $responses): Transport
    {
        return new Transport(new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->sent[] = ['method' => $method, 'url' => $url, 'options' => $options];
            $response = array_shift($responses);
            if ($response instanceof TransportException) {
                throw $response;
            }

            self::assertInstanceOf(MockResponse::class, $response);

            return $response;
        }));
    }

    private function connection(bool $insecure = false, int $timeout = 30, string $baseUrl = 'https://site.test'): Connection
    {
        return new Connection(
            $baseUrl,
            new Endpoints(),
            new Credentials(),
            useSession: false,
            insecure: $insecure,
            timeout: $timeout,
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(int $index = 0): array
    {
        $headers = [];
        foreach ($this->sent[$index]['options']['headers'] ?? [] as $header) {
            [$name, $value] = explode(':', (string) $header, 2);
            $headers[strtolower($name)] = trim($value);
        }

        return $headers;
    }

    public function testJsonRequestSendsTheExpectedHeadersAndBody(): void
    {
        $transport = $this->transport([new MockResponse('{"meta":{},"data":[]}', ['http_code' => 200])]);

        $response = $transport->send(
            $this->connection(),
            ApiRequest::json('POST', '/api/v1/admin/tag/new', ['name' => 'x'], ['locale' => 'fr']),
            'jwt-token',
        );

        self::assertSame('POST', $this->sent[0]['method']);
        self::assertSame('https://site.test/api/v1/admin/tag/new?locale=fr', $this->sent[0]['url']);
        self::assertSame('{"name":"x"}', $this->sent[0]['options']['body']);

        $headers = $this->headers();
        self::assertSame('application/json', $headers['content-type']);
        self::assertSame('application/json', $headers['accept']);
        self::assertSame('Bearer jwt-token', $headers['authorization']);
        self::assertSame('east-website-cli/' . Version::VERSION, $headers['user-agent']);

        self::assertSame(200, $response->status);
        self::assertSame(['meta' => [], 'data' => []], $response->body);
    }

    public function testNoAuthorizationHeaderWithoutBearer(): void
    {
        $transport = $this->transport([new MockResponse('{}', ['http_code' => 200])]);

        $transport->send($this->connection(), ApiRequest::get('/api/v1/posts'), null);

        self::assertArrayNotHasKey('authorization', $this->headers());
    }

    public function testRedirectionsAreNeverFollowed(): void
    {
        $transport = $this->transport([
            new MockResponse('<html></html>', [
                'http_code' => 302,
                'response_headers' => ['Location: /api/v1/admin/tag/t-1'],
            ]),
        ]);

        $response = $transport->send($this->connection(), ApiRequest::get('/api/v1/admin/tag/new'), 'jwt');

        self::assertSame(0, $this->sent[0]['options']['max_redirects']);
        self::assertCount(1, $this->sent);
        self::assertSame(302, $response->status);
        self::assertSame('/api/v1/admin/tag/t-1', $response->location());
        self::assertNull($response->body);
        self::assertSame('<html></html>', $response->raw);
    }

    public function testHttpErrorsAreNotThrown(): void
    {
        $transport = $this->transport([new MockResponse('{"data":{"message":"Nope"}}', ['http_code' => 500])]);

        $response = $transport->send($this->connection(), ApiRequest::get('/api/v1/posts'), null);

        self::assertSame(500, $response->status);
        self::assertFalse($response->isSuccess());
    }

    public function testNonJsonBodyKeepsTheRawContent(): void
    {
        $transport = $this->transport([new MockResponse('<html>Bad gateway</html>', ['http_code' => 502])]);

        $response = $transport->send($this->connection(), ApiRequest::get('/api/v1/posts'), null);

        self::assertNull($response->body);
        self::assertSame('<html>Bad gateway</html>', $response->raw);
    }

    public function testTimeoutsAndTlsOptions(): void
    {
        $transport = $this->transport([new MockResponse('{}'), new MockResponse('{}')]);

        $transport->send($this->connection(false, 12), ApiRequest::get('/api/v1/posts'), null);
        $transport->send($this->connection(true), ApiRequest::get('/api/v1/posts'), null);

        self::assertEquals(12.0, $this->sent[0]['options']['timeout']);
        self::assertEquals(120.0, $this->sent[0]['options']['max_duration']);
        self::assertTrue($this->sent[0]['options']['verify_peer']);
        self::assertTrue($this->sent[0]['options']['verify_host']);
        self::assertFalse($this->sent[1]['options']['verify_peer']);
        self::assertFalse($this->sent[1]['options']['verify_host']);
    }

    public function testRequestFromTheOriginIgnoresThePathOfTheBaseUrl(): void
    {
        $transport = $this->transport([new MockResponse('{}')]);

        $transport->send(
            $this->connection(baseUrl: 'https://site.test/sub'),
            ApiRequest::follow('/sub/api/v1/admin/tag/t-1'),
            null,
        );

        self::assertSame('https://site.test/sub/api/v1/admin/tag/t-1', $this->sent[0]['url']);
    }

    public function testRequestFromTheOriginKeepsTheQueryOfTheLocation(): void
    {
        $transport = $this->transport([new MockResponse('{}')]);

        $transport->send($this->connection(), ApiRequest::follow('/api/v1/post/p?id=c-1', ['locale' => 'fr']), null);

        self::assertSame('https://site.test/api/v1/post/p?id=c-1&locale=fr', $this->sent[0]['url']);
    }

    public function testNetworkFailuresAreConvertedToTransportExceptions(): void
    {
        $failure = new TransportException('Connection refused');
        $transport = $this->transport([$failure]);

        try {
            $transport->send($this->connection(), ApiRequest::get('/api/v1/posts'), null);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Transport, $error->kind);
            self::assertSame(1, $error->getCode());
            self::assertStringContainsString('Unable to reach the server: Connection refused', $error->getMessage());
            self::assertSame($failure, $error->getPrevious());
        }
    }

    public function testMissingBaseUrlIsAUsageError(): void
    {
        $transport = $this->transport([]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('No base URL configured');

        $transport->send($this->connection(baseUrl: ''), ApiRequest::get('/api/v1/posts'), null);
    }

    public function testMultipartIsSentAndTheUploadedStreamIsClosed(): void
    {
        $temp = new TempDir();
        $file = $temp->write('logo.png', 'binary-content');
        $before = count(get_resources('stream'));

        $transport = $this->transport([new MockResponse('{"meta":{},"data":{}}', ['http_code' => 302])]);
        $transport->send(
            $this->connection(),
            ApiRequest::multipart('/api/v1/admin/media/new', ['media[name]' => 'Logo'], 'media[image]', $file),
            'jwt',
        );

        self::assertStringStartsWith('multipart/form-data; boundary=', $this->headers()['content-type']);
        self::assertSame($before, count(get_resources('stream')));
        $temp->remove();
    }

    public function testStreamsAreClosedEvenWhenTheNetworkFails(): void
    {
        $temp = new TempDir();
        $file = $temp->write('logo.png', 'binary-content');
        $before = count(get_resources('stream'));

        $transport = $this->transport([new TransportException('Timeout')]);

        try {
            $transport->send(
                $this->connection(),
                ApiRequest::multipart('/api/v1/admin/media/new', [], 'media[image]', $file),
                'jwt',
            );
            self::fail('An exception was expected');
        } catch (ApiException) {
            self::assertSame($before, count(get_resources('stream')));
        }

        $temp->remove();
    }
}
