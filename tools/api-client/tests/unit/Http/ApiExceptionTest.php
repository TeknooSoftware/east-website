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
use RuntimeException;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Json;

use function str_repeat;

/**
 * Tests of the failures of the CLI and of their rendering
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ApiException::class)]
class ApiExceptionTest extends TestCase
{
    private function response(int $status, string $raw): ApiResponse
    {
        return new ApiResponse($status, [], $raw, Json::decode($raw));
    }

    public function testUsage(): void
    {
        $error = ApiException::usage('Bad usage');

        self::assertSame(ErrorKind::Usage, $error->kind);
        self::assertSame('Bad usage', $error->getMessage());
        self::assertSame(2, $error->getCode());
        self::assertSame(0, $error->status);
    }

    public function testExitCodeOfTheKindIsTheCodeOfTheException(): void
    {
        self::assertSame(1, (new ApiException('x', ErrorKind::Server))->getCode());
        self::assertSame(3, (new ApiException('x', ErrorKind::Auth))->getCode());
        self::assertSame(4, (new ApiException('x', ErrorKind::NotFound))->getCode());
    }

    public function testFromResponseUsesTheMessageOfTheApi(): void
    {
        $error = ApiException::fromResponse(
            $this->response(404, '{"meta":{"error":true},"data":{"code":404,"message":"Tag not found"}}')
        );

        self::assertSame(ErrorKind::NotFound, $error->kind);
        self::assertSame('Tag not found', $error->getMessage());
        self::assertSame(404, $error->status);
        self::assertSame([], $error->fields);
        self::assertSame([], $error->extra);
    }

    public function testFromResponseAuth(): void
    {
        $error = ApiException::fromResponse(
            $this->response(401, '{"meta":{"error":true},"data":{"code":401,"message":"Expired JWT Token"}}')
        );

        self::assertSame(ErrorKind::Auth, $error->kind);
        self::assertSame('Expired JWT Token', $error->getMessage());
    }

    public function testFromResponseValidationWithFieldErrors(): void
    {
        $error = ApiException::fromResponse(
            $this->response(400, '{"meta":{"errors":true},"data":{".name":"Too short",".blocks.0.type":"Invalid"}}')
        );

        self::assertSame(ErrorKind::Validation, $error->kind);
        self::assertSame('Validation failed', $error->getMessage());
        self::assertSame(['.name' => 'Too short', '.blocks.0.type' => 'Invalid'], $error->fields);
        self::assertSame([], $error->extra);
    }

    public function testFromResponseValidationWithTheObjectReturnedUnchanged(): void
    {
        $error = ApiException::fromResponse(
            $this->response(400, '{"meta":{"id":"t-1"},"data":{"id":"t-1","name":"x"}}')
        );

        self::assertSame(ErrorKind::Validation, $error->kind);
        self::assertSame('The server rejected the submitted data without detailing the errors', $error->getMessage());
        self::assertSame([], $error->fields);
        self::assertSame(['id' => 't-1', 'name' => 'x'], $error->extra['rejected']);
    }

    public function testFromResponseValidationWithAMessageAndNoFieldFlagKeepsTheMessage(): void
    {
        $error = ApiException::fromResponse(
            $this->response(400, '{"meta":{"error":true},"data":{"code":400,"message":"Malformed JSON body"}}')
        );

        self::assertSame('Malformed JSON body', $error->getMessage());
        self::assertSame([], $error->fields);
        self::assertArrayHasKey('rejected', $error->extra);
    }

    public function testFromResponseWithANonJsonBodyUsesAnExcerpt(): void
    {
        $error = ApiException::fromResponse($this->response(502, "<html>\nBad gateway\n</html>"));

        self::assertSame(ErrorKind::Server, $error->kind);
        self::assertSame("Unexpected HTTP status 502: <html>\nBad gateway\n</html>", $error->getMessage());
    }

    public function testFromResponseTruncatesTheExcerpt(): void
    {
        $error = ApiException::fromResponse($this->response(500, str_repeat('x', 500)));

        self::assertSame('Unexpected HTTP status 500: ' . str_repeat('x', 197) . '...', $error->getMessage());
    }

    public function testFromResponseWithAnEmptyBody(): void
    {
        $error = ApiException::fromResponse($this->response(503, ''));

        self::assertSame('Unexpected HTTP status 503', $error->getMessage());
    }

    public function testWithExtraMergesAndKeepsTheOriginalState(): void
    {
        $previous = new RuntimeException('previous');
        $error = new ApiException(
            'Failed',
            ErrorKind::Server,
            500,
            ['.a' => 'b'],
            ['hint' => 'first', 'keep' => 1],
            $previous,
        );

        $copy = $error->withExtra(['hint' => 'second', 'other' => 2]);

        self::assertNotSame($error, $copy);
        self::assertSame('Failed', $copy->getMessage());
        self::assertSame(ErrorKind::Server, $copy->kind);
        self::assertSame(500, $copy->status);
        self::assertSame(['.a' => 'b'], $copy->fields);
        self::assertSame(['hint' => 'second', 'other' => 2, 'keep' => 1], $copy->extra);
        self::assertSame($previous, $copy->getPrevious());
        self::assertSame(['hint' => 'first', 'keep' => 1], $error->extra);
    }

    public function testToArrayWithoutFields(): void
    {
        $error = new ApiException('Nope', ErrorKind::NotFound, 404, [], ['hint' => 'h']);

        self::assertSame(
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'kind' => 'not_found', 'message' => 'Nope', 'hint' => 'h']],
            $error->toArray(),
        );
    }

    public function testToArrayWithFields(): void
    {
        $error = new ApiException('Validation failed', ErrorKind::Validation, 400, ['.name' => 'Invalid']);

        self::assertSame(
            [
                'meta' => ['error' => true],
                'data' => [
                    'code' => 400,
                    'kind' => 'validation',
                    'message' => 'Validation failed',
                    'fields' => ['.name' => 'Invalid'],
                ],
            ],
            $error->toArray(),
        );
    }
}
