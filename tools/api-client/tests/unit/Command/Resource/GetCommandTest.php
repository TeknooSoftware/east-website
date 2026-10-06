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

namespace Teknoo\Tests\East\Website\Tools\Command\Resource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Command\Resource\GetCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\ScriptedDriver;

/**
 * Tests of the get of an object of the resources of the admin API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(GetCommand::class)]
class GetCommandTest extends TestCase
{
    private function harness(): ApiHarness
    {
        return new ApiHarness(['token' => 'jwt']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function resources(): iterable
    {
        yield 'tag' => [['website:tag:get', 'id-1'], '/api/v1/admin/tag/id-1'];
        yield 'type' => [['website:type:get', 'id-1'], '/api/v1/admin/type/id-1'];
        yield 'content' => [['website:content:get', 'id-1'], '/api/v1/admin/content/id-1'];
        yield 'post' => [['website:post:get', 'id-1'], '/api/v1/admin/post/id-1'];
        yield 'item' => [['website:item:get', 'id-1'], '/api/v1/admin/item/id-1'];
        yield 'user' => [['website:user:get', 'id-1'], '/api/v1/admin/user/id-1'];
        yield 'media' => [['website:media:get', '64b7f0c2a1d3e4f5a6b7c8d9'], '/api/v1/admin/media/64b7f0c2a1d3e4f5a6b7c8d9'];
        yield 'comment' => [['website:comment:get', 'post-9', 'cmt-2'], '/api/v1/admin/post/post-9/comment/cmt-2'];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('resources')]
    public function testGetOfEachResource(array $tokens, string $path): void
    {
        $document = ['meta' => ['id' => 'id-1', '@class' => 'X'], 'data' => ['@class' => 'X', 'id' => 'id-1']];
        $harness = $this->harness()->respond('GET ' . $path, 200, $document);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, $tokens);

        self::assertSame(0, $code, $stderr);
        self::assertSame($document, AbstractCommandTest::decode($stdout));
        self::assertCount(1, $harness->requests);
        self::assertSame('GET', $harness->requests[0]['method']);
        self::assertSame($path, $harness->requests[0]['path']);
        self::assertSame('Bearer jwt', $harness->requests[0]['headers']['authorization']);
    }

    public function testTheIdentifierIsUrlEncoded(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tag/a%20b%2Fc', 200, ['meta' => [], 'data' => []]);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:get', 'a b/c']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('/api/v1/admin/tag/a%20b%2Fc', $harness->requests[0]['path']);
    }

    public function testLocaleOnATranslatableResource(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/content/c-1', 200, ['meta' => [], 'data' => []]);

        [$code] = AbstractCommandTest::execute($harness, ['website:content:get', 'c-1', '--locale=fr']);

        self::assertSame(0, $code);
        self::assertSame('locale=fr', $harness->requests[0]['query']);
    }

    public function testLocaleOptionDoesNotExistOnANonTranslatableResource(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:get', 't-1', '--locale=fr']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testMissingOrEmptyIdentifierIsAUsageError(): void
    {
        $harness = $this->harness();

        [$missing] = AbstractCommandTest::execute($harness, ['website:tag:get']);
        [$empty, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:get', '']);

        self::assertSame(2, $missing);
        self::assertSame(2, $empty);
        self::assertSame('The argument "id" can not be empty', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);
    }

    public function testNotFound(): void
    {
        $harness = $this->harness()->respond(
            'GET /api/v1/admin/tag/missing',
            404,
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'message' => 'Not found']],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:get', 'missing']);

        self::assertSame(4, $code);
        self::assertSame('', $stdout);
        self::assertSame('not_found', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testDryRunSendsNothing(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:comment:get', 'post-9', 'cmt-2', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $request = AbstractCommandTest::decode($stdout)['requests'][0];
        self::assertSame('GET', $request['method']);
        self::assertSame('https://site.test/api/v1/admin/post/post-9/comment/cmt-2', $request['url']);
        self::assertSame('Bearer ***', $request['headers']['Authorization']);
    }

    public function testTheTuiFormatOpensTheObjectReadOnly(): void
    {
        $driver = new ScriptedDriver([Keys::DOWN, 'q']);
        $harness = new ApiHarness(['token' => 'jwt'], driver: $driver);
        $harness->respond('GET /api/v1/admin/content/c1', 200, [
            'meta' => ['id' => 'c1'],
            'data' => ['id' => 'c1', 'title' => 'Home', 'slug' => 'home'],
        ]);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:content:get', 'c1', '--format=tui', '--locale=fr'],
            [],
            true,
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stdout);
        self::assertSame('', $stderr);
        self::assertStringStartsWith('content · Home', $driver->screens[0]);
        self::assertStringContainsString('> title  Home', $driver->screens[1]);
        self::assertCount(1, $harness->requests);
        self::assertSame('locale=fr', $harness->requests[0]['query']);
    }

    public function testTheTuiFormatNeedsATerminalAndSendsNothingWithoutIt(): void
    {
        $driver = new ScriptedDriver([], false);
        $harness = new ApiHarness(['token' => 'jwt'], driver: $driver);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:get', 't1', '--format=tui'], [], true);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
        self::assertSame([], $harness->requests);
        self::assertSame(0, $driver->runs);
    }

    public function testAnObjectNotFoundNeverOpensTheTuiFormat(): void
    {
        $driver = new ScriptedDriver([]);
        $harness = new ApiHarness(['token' => 'jwt'], driver: $driver);
        $harness->respond('GET /api/v1/admin/tag/t1', 404, ['meta' => ['error' => true], 'data' => ['message' => 'Gone']]);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:get', 't1', '--format=tui'], [], true);

        self::assertSame(4, $code);
        self::assertSame('', $stdout);
        self::assertSame('Gone', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame(0, $driver->runs);
    }

    public function testADryRunNeverOpensTheTuiFormat(): void
    {
        $driver = new ScriptedDriver([], false);
        $harness = new ApiHarness(['token' => 'jwt'], driver: $driver);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:tag:get', 't1', '--format=tui', '--dry-run']);

        self::assertSame(0, $code);
        self::assertTrue(AbstractCommandTest::decode($stdout)['dryRun']);
        self::assertSame([], $harness->requests);
        self::assertSame(0, $driver->runs);
    }
}
