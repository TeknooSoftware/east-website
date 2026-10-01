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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Http\ApiResponse;

/**
 * Tests of the response of the API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ApiResponse::class)]
class ApiResponseTest extends TestCase
{
    /**
     * @param array<mixed>|null $body
     * @param array<string, list<string>> $headers
     */
    private function response(int $status = 200, ?array $body = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($status, $headers, '', $body);
    }

    public function testHeaderIsReadCaseInsensitively(): void
    {
        $response = $this->response(200, null, ['location' => ['/a', '/b']]);

        self::assertSame('/a', $response->header('Location'));
        self::assertSame('/a', $response->location());
        self::assertNull($response->header('X-Missing'));
    }

    /**
     * @return iterable<string, array{int, bool, bool}>
     */
    public static function statuses(): iterable
    {
        yield 'ok' => [200, true, false];
        yield 'no content' => [204, true, false];
        yield 'multiple' => [300, false, true];
        yield 'found' => [302, false, true];
        yield 'last redirect' => [399, false, true];
        yield 'bad request' => [400, false, false];
        yield 'before success' => [199, false, false];
        yield 'error' => [500, false, false];
    }

    #[DataProvider('statuses')]
    public function testStatusClassification(int $status, bool $success, bool $redirect): void
    {
        $response = $this->response($status);

        self::assertSame($success, $response->isSuccess());
        self::assertSame($redirect, $response->isRedirect());
    }

    public function testMetaAndData(): void
    {
        $response = $this->response(200, ['meta' => ['page' => 1], 'data' => [['id' => 'a']]]);

        self::assertSame(['page' => 1], $response->meta());
        self::assertSame([['id' => 'a']], $response->data());
    }

    public function testMetaAndDataWithoutBody(): void
    {
        $response = $this->response();

        self::assertSame([], $response->meta());
        self::assertNull($response->data());
    }

    public function testMetaIsEmptyWhenNotAnArray(): void
    {
        self::assertSame([], $this->response(200, ['meta' => 'text'])->meta());
    }

    public function testIdFromMeta(): void
    {
        self::assertSame('m-1', $this->response(200, ['meta' => ['id' => 'm-1'], 'data' => ['id' => 'd-1']])->id());
    }

    public function testIdFromDataWhenMetaHasNone(): void
    {
        self::assertSame('d-1', $this->response(200, ['meta' => [], 'data' => ['id' => 'd-1']])->id());
        self::assertSame('d-1', $this->response(200, ['meta' => ['id' => ''], 'data' => ['id' => 'd-1']])->id());
    }

    public function testNoId(): void
    {
        self::assertNull($this->response()->id());
        self::assertNull($this->response(200, ['data' => []])->id());
        self::assertNull($this->response(200, ['data' => 'text'])->id());
        self::assertNull($this->response(200, ['data' => ['id' => '']])->id());
        self::assertNull($this->response(200, ['data' => ['id' => 12]])->id());
    }
}
