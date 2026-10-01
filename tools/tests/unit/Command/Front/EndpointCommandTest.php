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

namespace Teknoo\Tests\East\Website\Tools\Command\Front;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Command\Front\EndpointCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

/**
 * Tests of the public endpoints: published contents and posts, with an optional authentication
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(EndpointCommand::class)]
class EndpointCommandTest extends TestCase
{
    private const array DOCUMENT = ['meta' => ['id' => 'x'], 'data' => ['id' => 'x', 'title' => 'Published']];

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function endpoints(): iterable
    {
        yield 'home page by default' => [['website:front:content:get'], '/api/v1/content/default'];
        yield 'content' => [['website:front:content:get', 'about-us'], '/api/v1/content/about-us'];
        yield 'post' => [['website:front:post:get', 'my-post'], '/api/v1/post/my-post'];
        yield 'posts' => [['website:front:post:list'], '/api/v1/posts'];
        yield 'posts of a tag' => [['website:front:post:list-by-tag', 'php'], '/api/v1/posts/by/php'];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('endpoints')]
    public function testEachPublicEndpoint(array $tokens, string $path): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']))->respond('GET ' . $path, 200, self::DOCUMENT);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, $tokens);

        self::assertSame(0, $code, $stderr);
        self::assertSame(self::DOCUMENT, AbstractCommandTest::decode($stdout));
        self::assertCount(1, $harness->requests);
        self::assertSame('GET', $harness->requests[0]['method']);
        self::assertSame($path, $harness->requests[0]['path']);
        self::assertSame('', $harness->requests[0]['query']);
        self::assertNull($harness->requests[0]['body']);
    }

    public function testTheSlugIsUrlEncoded(): void
    {
        $harness = (new ApiHarness())->respond('GET /api/v1/post/a%20b%2Fc', 200, self::DOCUMENT);

        [$code] = AbstractCommandTest::execute($harness, ['website:front:post:get', 'a b/c']);

        self::assertSame(0, $code);
        self::assertSame('/api/v1/post/a%20b%2Fc', $harness->requests[0]['path']);
    }

    public function testLocaleAndPageAreSentAsQuery(): void
    {
        $harness = (new ApiHarness())->respond('GET /api/v1/posts', 200, self::DOCUMENT);

        [$code] = AbstractCommandTest::execute($harness, ['website:front:post:list', '--page=2', '--locale=fr']);

        self::assertSame(0, $code);
        self::assertSame('locale=fr&page=2', $harness->requests[0]['query']);
    }

    public function testTheLocaleOfAContent(): void
    {
        $harness = (new ApiHarness())->respond('GET /api/v1/content/default', 200, self::DOCUMENT);

        [$code] = AbstractCommandTest::execute($harness, ['website:front:content:get', '--locale=fr']);

        self::assertSame(0, $code);
        self::assertSame('locale=fr', $harness->requests[0]['query']);
    }

    public function testThePageOptionExistsOnlyOnTheLists(): void
    {
        $harness = new ApiHarness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:front:post:get', 'my-post', '--page=2']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testTheTagOfAListIsRequired(): void
    {
        $harness = new ApiHarness();

        [$code] = AbstractCommandTest::execute($harness, ['website:front:post:list-by-tag']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    public function testThePostSlugIsRequired(): void
    {
        $harness = new ApiHarness();

        [$code] = AbstractCommandTest::execute($harness, ['website:front:post:get']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPages(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-3'];
    }

    #[DataProvider('invalidPages')]
    public function testAnInvalidPageIsAUsageErrorWithoutAnyRequest(string $page): void
    {
        $harness = new ApiHarness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:front:post:list', '--page=' . $page]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The page must be greater or equal to 1, ' . $page . ' given',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testAnonymousRequestWhenThereIsNoCredential(): void
    {
        $harness = (new ApiHarness())->respond('GET /api/v1/content/default', 200, self::DOCUMENT);

        [$code] = AbstractCommandTest::execute($harness, ['website:front:content:get']);

        self::assertSame(0, $code);
        self::assertArrayNotHasKey('authorization', $harness->requests[0]['headers']);
    }

    public function testTheTokenIsSentWhenAvailable(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']))->respond('GET /api/v1/content/default', 200, self::DOCUMENT);

        [$code] = AbstractCommandTest::execute($harness, ['website:front:content:get']);

        self::assertSame(0, $code);
        self::assertSame('Bearer jwt', $harness->requests[0]['headers']['authorization']);
    }

    public function testAnonymousOptionOmitsTheToken(): void
    {
        $harness = (new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']))->respond('GET /api/v1/content/default', 200, self::DOCUMENT);

        [$code] = AbstractCommandTest::execute($harness, ['website:front:content:get', '--anonymous']);

        self::assertSame(0, $code);
        self::assertArrayNotHasKey('authorization', $harness->requests[0]['headers']);
    }

    public function testTheLoginIsDoneWhenAnApiKeyIsAvailable(): void
    {
        $jwt = ApiHarness::jwt(1_800_003_600);
        $harness = (new ApiHarness(['EAST_WEBSITE_USERNAME' => 'key:me@site.test', 'EAST_WEBSITE_API_KEY' => 'secret']))
            ->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => ['token' => $jwt]])
            ->respond('GET /api/v1/content/default', 200, self::DOCUMENT);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:front:content:get']);

        self::assertSame(0, $code, $stderr);
        self::assertCount(2, $harness->requests);
        self::assertSame('/api/v1/login', $harness->requests[0]['path']);
        self::assertSame('Bearer ' . $jwt, $harness->requests[1]['headers']['authorization']);
    }

    public function testNotFound(): void
    {
        $harness = (new ApiHarness())->respond(
            'GET /api/v1/content/missing',
            404,
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'message' => 'Content not found']],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:front:content:get', 'missing']);

        self::assertSame(4, $code);
        self::assertSame('', $stdout);
        self::assertSame('Content not found', AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testUnauthorizedWhenTheApplicationProtectsThePublicApi(): void
    {
        $harness = (new ApiHarness())->respond(
            'GET /api/v1/posts',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'JWT Token not found']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:front:post:list']);

        self::assertSame(3, $code);
        self::assertSame('JWT Token not found', AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testDryRunSendsNothingAndMasksTheBearer(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:front:post:list-by-tag', 'php', '--page=2', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $request = AbstractCommandTest::decode($stdout)['requests'][0];
        self::assertSame('https://site.test/api/v1/posts/by/php?page=2', $request['url']);
        self::assertSame('Bearer ***', $request['headers']['Authorization']);
    }

    public function testDryRunOfAnAnonymousRequestHasNoBearer(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:front:content:get', '--anonymous', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertArrayNotHasKey('Authorization', AbstractCommandTest::decode($stdout)['requests'][0]['headers']);
    }

    public function testTheTableFormatOfAList(): void
    {
        $harness = (new ApiHarness())->respond('GET /api/v1/posts', 200, [
            'meta' => ['totalPages' => 2, 'page' => 1, 'count' => 12],
            'data' => [['id' => 'p-1', 'title' => 'First'], ['id' => 'p-2', 'title' => 'Second']],
        ]);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:front:post:list', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('| p-1', $stdout);
        self::assertStringContainsString('| Second', $stdout);
        self::assertStringContainsString('page 1/2, 12 item(s)', $stdout);
    }
}
