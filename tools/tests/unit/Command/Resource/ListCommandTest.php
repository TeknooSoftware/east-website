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
use Teknoo\East\Website\Tools\Command\Resource\ListCommand;
use Teknoo\East\Website\Tools\Command\Resource\ResourceCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

/**
 * Tests of the list of the objects of the resources of the admin API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListCommand::class)]
#[CoversClass(ResourceCommand::class)]
class ListCommandTest extends TestCase
{
    private const array PAGE = [
        'meta' => ['totalPages' => 3, 'page' => 2, 'count' => 42],
        'data' => [['id' => 'x-1'], ['id' => 'x-2']],
    ];

    private function harness(): ApiHarness
    {
        return new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function resources(): iterable
    {
        yield 'tag' => [['website:tag:list'], '/api/v1/admin/tags'];
        yield 'type' => [['website:type:list'], '/api/v1/admin/types'];
        yield 'content' => [['website:content:list'], '/api/v1/admin/contents'];
        yield 'post' => [['website:post:list'], '/api/v1/admin/posts'];
        yield 'item' => [['website:item:list'], '/api/v1/admin/items'];
        yield 'user' => [['website:user:list'], '/api/v1/admin/users'];
        yield 'media' => [['website:media:list'], '/api/v1/admin/media'];
        yield 'comment' => [['website:comment:list', 'post-9'], '/api/v1/admin/post/post-9/comments'];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('resources')]
    public function testListOfEachResource(array $tokens, string $path): void
    {
        $harness = $this->harness()->respond('GET ' . $path, 200, self::PAGE);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, $tokens);

        self::assertSame(0, $code, $stderr);
        self::assertSame(self::PAGE, AbstractCommandTest::decode($stdout));
        self::assertCount(1, $harness->requests);
        self::assertSame('GET', $harness->requests[0]['method']);
        self::assertSame($path, $harness->requests[0]['path']);
        self::assertSame('', $harness->requests[0]['query']);
        self::assertSame('Bearer jwt', $harness->requests[0]['headers']['authorization']);
        self::assertNull($harness->requests[0]['body']);
        self::assertArrayNotHasKey('content-type', $harness->requests[0]['headers']);
    }

    public function testPaginationAndSortingOptionsAreSentAsQuery(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, self::PAGE);

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:tag:list', '--page=2', '--order=name', '--direction=desc'],
        );

        self::assertSame(0, $code);
        self::assertSame('page=2&order=name&direction=DESC', $harness->requests[0]['query']);
    }

    public function testLocaleIsSentFirstOnTranslatableResources(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/contents', 200, self::PAGE);

        [$code] = AbstractCommandTest::execute($harness, ['website:content:list', '--locale=fr', '--page=3']);

        self::assertSame(0, $code);
        self::assertSame('locale=fr&page=3', $harness->requests[0]['query']);
    }

    public function testLocaleOptionDoesNotExistOnNonTranslatableResources(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:list', '--locale=fr']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'direction' => ['--direction=sideways', 'The direction must be ASC or DESC'];
        yield 'page zero' => ['--page=0', 'The page must be greater or equal to 1'];
        yield 'page negative' => ['--page=-2', 'The page must be greater or equal to 1'];
        yield 'page not a number' => ['--page=abc', 'The option --page expects an integer, "abc" given'];
    }

    #[DataProvider('invalidOptions')]
    public function testInvalidOptionsAreUsageErrorsWithoutAnyRequest(string $option, string $message): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:list', $option]);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testAnEmptyOrderIsIgnored(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, self::PAGE);

        [$code] = AbstractCommandTest::execute($harness, ['website:tag:list', '--order=']);

        self::assertSame(0, $code);
        self::assertSame('', $harness->requests[0]['query']);
    }

    public function testTheIdentifierOfTheParentIsRequiredAndNotEmpty(): void
    {
        $harness = $this->harness();

        [$missing, , $missingError] = AbstractCommandTest::execute($harness, ['website:comment:list']);
        [$empty, , $emptyError] = AbstractCommandTest::execute($harness, ['website:comment:list', '']);

        self::assertSame(2, $missing);
        self::assertStringContainsString('Not enough arguments', AbstractCommandTest::decode($missingError)['data']['message']);
        self::assertSame(2, $empty);
        self::assertSame('The argument "post-id" can not be empty', AbstractCommandTest::decode($emptyError)['data']['message']);
        self::assertSame([], $harness->requests);
    }

    public function testTheParentIdentifierIsUrlEncoded(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/post/a%20b%2Fc/comments', 200, self::PAGE);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:comment:list', 'a b/c']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('/api/v1/admin/post/a%20b%2Fc/comments', $harness->requests[0]['path']);
    }

    public function testDryRunPrintsTheRequestAndSendsNothing(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:tag:list', '--page=2', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            [
                'dryRun' => true,
                'requests' => [[
                    'method' => 'GET',
                    'url' => 'https://site.test/api/v1/admin/tags?page=2',
                    'headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer ***'],
                ]],
            ],
            AbstractCommandTest::decode($stdout),
        );
    }

    public function testDryRunDoesNotNeedCredentialsNorAServer(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_URL' => '']);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:tag:list', '--dry-run']);

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            '<base-url>/api/v1/admin/tags',
            AbstractCommandTest::decode($stdout)['requests'][0]['url'],
        );
    }

    public function testCustomAdminPrefixFromTheEnvironment(): void
    {
        $harness = new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt', 'EAST_WEBSITE_ADMIN_PREFIX' => 'cms/admin/']);
        $harness->respond('GET /cms/admin/tags', 200, self::PAGE);

        [$code] = AbstractCommandTest::execute($harness, ['website:tag:list']);

        self::assertSame(0, $code);
        self::assertSame('/cms/admin/tags', $harness->requests[0]['path']);
    }
}
