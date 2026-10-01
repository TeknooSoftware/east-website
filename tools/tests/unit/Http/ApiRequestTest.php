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
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function fclose;
use function is_array;
use function is_resource;

/**
 * Tests of the description of a request to the API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ApiRequest::class)]
class ApiRequestTest extends TestCase
{
    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    private function connection(string $baseUrl = 'https://site.test'): Connection
    {
        return new Connection($baseUrl, new Endpoints(), new Credentials(), useSession: false);
    }

    public function testGet(): void
    {
        $request = ApiRequest::get('/api/v1/admin/tags', ['page' => 2]);

        self::assertSame('GET', $request->method());
        self::assertSame('/api/v1/admin/tags', $request->path());
        self::assertSame(['page' => 2], $request->query());
        self::assertNull($request->payload());
        self::assertFalse($request->fromOrigin());
        self::assertSame(['headers' => []], $request->httpOptions());
    }

    public function testDelete(): void
    {
        $request = ApiRequest::delete('/api/v1/admin/tag/t-1/delete');

        self::assertSame('DELETE', $request->method());
        self::assertSame([], $request->query());
        self::assertSame(['headers' => []], $request->httpOptions());
    }

    public function testFollow(): void
    {
        $request = ApiRequest::follow('/api/v1/admin/tag/t-1', ['locale' => 'fr']);

        self::assertSame('GET', $request->method());
        self::assertTrue($request->fromOrigin());
        self::assertSame(['locale' => 'fr'], $request->query());
    }

    public function testJsonSendsTheBareMediaType(): void
    {
        $request = ApiRequest::json('POST', '/api/v1/admin/tag/new', ['name' => 'Déloge/x'], ['locale' => 'fr']);

        self::assertSame('POST', $request->method());
        self::assertSame(['name' => 'Déloge/x'], $request->payload());
        self::assertSame(['locale' => 'fr'], $request->query());
        self::assertSame(
            ['headers' => ['Content-Type' => 'application/json'], 'body' => '{"name":"Déloge/x"}'],
            $request->httpOptions(),
        );
    }

    public function testJsonWithAnEmptyPayloadSendsAnEmptyObject(): void
    {
        $options = ApiRequest::json('PUT', '/api/v1/admin/tag/t-1', [])->httpOptions();

        self::assertSame('{}', $options['body']);
        self::assertSame('application/json', $options['headers']['Content-Type']);
    }

    public function testMultipartOpensANewStreamForEachAttempt(): void
    {
        $this->temp = new TempDir();
        $file = $this->temp->write('logo.png', 'binary');
        $request = ApiRequest::multipart('/api/v1/admin/media/new', ['media[name]' => 'Logo'], 'media[image]', $file);

        self::assertSame('POST', $request->method());
        self::assertNull($request->payload());

        $first = $request->httpOptions();
        $second = $request->httpOptions();

        self::assertSame([], $first['headers']);
        self::assertIsArray($first['body']);
        self::assertSame('Logo', $first['body']['media[name]']);
        self::assertIsResource($first['body']['media[image]']);
        self::assertIsResource($second['body']['media[image]']);
        self::assertNotSame($first['body']['media[image]'], $second['body']['media[image]']);

        foreach ([$first, $second] as $options) {
            if (is_array($options['body']) && is_resource($options['body']['media[image]'])) {
                fclose($options['body']['media[image]']);
            }
        }
    }

    public function testMultipartWithAMissingFileIsAUsageError(): void
    {
        $request = ApiRequest::multipart('/api/v1/admin/media/new', [], 'media[image]', '/does/not/exist.png');

        try {
            $request->httpOptions();
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('/does/not/exist.png', $error->getMessage());
        }
    }

    public function testMultipartWithAnUnreadableFileIsAUsageError(): void
    {
        $this->temp = new TempDir();
        $file = $this->temp->write('secret.png', 'binary');
        chmod($file, 0000);

        if (is_readable($file)) {
            self::markTestSkipped('The file stays readable (running as root or without POSIX permissions)');
        }

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        ApiRequest::multipart('/api/v1/admin/media/new', [], 'media[image]', $file)->httpOptions();
    }

    public function testDescribeMasksTheBearer(): void
    {
        $description = ApiRequest::json('POST', '/api/v1/admin/tag/new', ['name' => 'x'], ['locale' => 'fr'])
            ->describe($this->connection(), true);

        self::assertSame(
            [
                'method' => 'POST',
                'url' => 'https://site.test/api/v1/admin/tag/new?locale=fr',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ***',
                ],
                'body' => ['name' => 'x'],
            ],
            $description,
        );
    }

    public function testDescribeWithoutAuthentication(): void
    {
        $description = ApiRequest::get('/api/v1/posts')->describe($this->connection(), false);

        self::assertSame(['Accept' => 'application/json'], $description['headers']);
        self::assertArrayNotHasKey('body', $description);
    }

    public function testDescribeAnEmptyPayloadAsAnObject(): void
    {
        $description = ApiRequest::json('PUT', '/api/v1/admin/tag/t-1', [])->describe($this->connection(), true);

        self::assertEquals((object) [], $description['body']);
    }

    public function testDescribeWithoutBaseUrlUsesAPlaceholder(): void
    {
        $description = ApiRequest::get('/api/v1/posts', ['page' => 1])->describe($this->connection(''), false);

        self::assertSame('<base-url>/api/v1/posts?page=1', $description['url']);
    }

    public function testDescribeARequestFromTheOrigin(): void
    {
        $description = ApiRequest::follow('/api/v1/admin/tag/t-1')->describe($this->connection('https://site.test/sub'), true);

        self::assertSame('https://site.test/api/v1/admin/tag/t-1', $description['url']);
    }

    public function testDescribeMultipartDoesNotOpenTheFile(): void
    {
        $description = ApiRequest::multipart(
            '/api/v1/admin/media/new',
            ['media[name]' => 'Logo'],
            'media[image]',
            '/path/to/logo.png',
        )->describe($this->connection(), true);

        self::assertSame('multipart/form-data', $description['headers']['Content-Type']);
        self::assertSame(
            ['media[name]' => 'Logo', 'media[image]' => '@/path/to/logo.png'],
            $description['multipart'],
        );
    }
}
