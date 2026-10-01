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
use Teknoo\East\Website\Tools\Command\Resource\UpdateCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;


/**
 * Tests of the update of the objects: partial payloads, tri-state booleans, blocks and change of type in two steps
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(UpdateCommand::class)]
class UpdateCommandTest extends TestCase
{
    private const array UPDATED = ['meta' => ['id' => 'id-1'], 'data' => ['id' => 'id-1', 'name' => 'updated']];

    private function harness(): ApiHarness
    {
        return new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']);
    }

    /**
     * @return iterable<string, array{list<string>, string, string}>
     */
    public static function resources(): iterable
    {
        yield 'tag' => [['website:tag:update', 'id-1', '--name=New'], '/api/v1/admin/tag/id-1', '{"name":"New"}'];
        yield 'type' => [
            ['website:type:update', 'id-1', '--name=N', '--template=t.twig', '--block=a:text'],
            '/api/v1/admin/type/id-1',
            '{"name":"N","template":"t.twig","blocks":[{"name":"a","type":"2"}]}',
        ];
        yield 'content' => [
            ['website:content:update', 'id-1', '--title=T', '--subtitle=S', '--slug=s', '--description=D', '--author=u-1', '--tag=a'],
            '/api/v1/admin/content/id-1',
            '{"author":"u-1","tags":["a"],"title":"T","subtitle":"S","slug":"s","description":"D"}',
        ];
        yield 'post' => [['website:post:update', 'id-1', '--title=P'], '/api/v1/admin/post/id-1', '{"title":"P"}'];
        yield 'item' => [
            ['website:item:update', 'id-1', '--hidden', '--position=2'],
            '/api/v1/admin/item/id-1',
            '{"hidden":true,"position":2}',
        ];
        yield 'user' => [
            ['website:user:update', 'id-1', '--email=a@site.test', '--no-active'],
            '/api/v1/admin/user/id-1',
            '{"email":"a@site.test","active":false}',
        ];
        yield 'comment' => [
            ['website:comment:update', 'post-1', 'id-1', '--moderated-author=A', '--moderated-title=T', '--moderated-content=C'],
            '/api/v1/admin/post/post-1/comment/id-1',
            '{"moderatedAuthor":"A","moderatedTitle":"T","moderatedContent":"C"}',
        ];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('resources')]
    public function testUpdateOfEachResourceIsASingleJsonPut(array $tokens, string $path, string $body): void
    {
        $harness = $this->harness()->respond('PUT ' . $path, 200, self::UPDATED);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, $tokens);

        self::assertSame(0, $code, $stderr);
        self::assertSame(self::UPDATED, AbstractCommandTest::decode($stdout));
        self::assertCount(1, $harness->requests);
        self::assertSame('PUT', $harness->requests[0]['method']);
        self::assertSame($path, $harness->requests[0]['path']);
        self::assertSame('application/json', $harness->requests[0]['headers']['content-type']);
        self::assertSame('Bearer jwt', $harness->requests[0]['headers']['authorization']);
        self::assertSame($body, $harness->requests[0]['body']);
    }

    public function testNothingIsSentForTheOptionsNotProvided(): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/item/i-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute($harness, ['website:item:update', 'i-1']);

        self::assertSame(0, $code);
        self::assertSame('{}', $harness->requests[0]['body']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function booleans(): iterable
    {
        yield 'set' => [['--hidden'], '{"hidden":true}'];
        yield 'reset' => [['--no-hidden'], '{"hidden":false}'];
        yield 'absent' => [[], '{}'];
    }

    /**
     * @param list<string> $options
     */
    #[DataProvider('booleans')]
    public function testBooleansAreTriState(array $options, string $body): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/item/i-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute($harness, ['website:item:update', 'i-1', ...$options]);

        self::assertSame(0, $code);
        self::assertSame($body, $harness->requests[0]['body']);
    }

    public function testAnEmptyIdentifierOfAReferenceBecomesNull(): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/item/i-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute($harness, ['website:item:update', 'i-1', '--parent=', '--content=']);

        self::assertSame(0, $code);
        self::assertSame('{"parent":null,"content":null}', $harness->requests[0]['body']);
    }

    public function testTheLocaleIsSentAsQuery(): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/content/c-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute($harness, ['website:content:update', 'c-1', '--title=T', '--locale=fr']);

        self::assertSame(0, $code);
        self::assertSame('locale=fr', $harness->requests[0]['query']);
    }

    public function testBlocksWithoutChangeOfTypeAreSentInTheSameRequest(): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/content/c-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:content:update', 'c-1', '--title=T', '--part=intro=Hello', '--publish'],
        );

        self::assertSame(0, $code);
        self::assertCount(1, $harness->requests);
        self::assertSame('{"title":"T","block_intro":"Hello","publish":true}', $harness->requests[0]['body']);
    }

    public function testPublishAloneIsASinglePut(): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/post/p-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute($harness, ['website:post:update', 'p-1', '--publish']);

        self::assertSame(0, $code);
        self::assertSame('{"publish":true}', $harness->requests[0]['body']);
    }

    public function testBlocksWithAChangeOfTypeNeedTwoRequests(): void
    {
        $harness = $this->harness()
            ->respond('PUT /api/v1/admin/content/c-1', 200, ['meta' => ['id' => 'c-1'], 'data' => ['id' => 'c-1', 'step' => 1]])
            ->respond('PUT /api/v1/admin/content/c-1', 200, ['meta' => ['id' => 'c-1'], 'data' => ['id' => 'c-1', 'step' => 2]]);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, [
            'website:content:update',
            'c-1',
            '--type=type-2',
            '--title=T',
            '--part=intro=Hello',
            '--publish',
            '--compact',
        ]);

        self::assertSame(0, $code, $stderr);
        self::assertCount(2, $harness->requests);
        self::assertSame('PUT', $harness->requests[0]['method']);
        self::assertSame('{"type":"type-2","title":"T"}', $harness->requests[0]['body']);
        self::assertSame('PUT', $harness->requests[1]['method']);
        self::assertSame('/api/v1/admin/content/c-1', $harness->requests[1]['path']);
        self::assertSame('application/json', $harness->requests[1]['headers']['content-type']);
        self::assertSame('{"block_intro":"Hello","publish":true}', $harness->requests[1]['body']);
        self::assertSame(2, AbstractCommandTest::decode($stdout)['data']['step']);
    }

    public function testATypeFromTheRawPayloadAlsoNeedsTwoRequests(): void
    {
        $harness = $this->harness()->respond('PUT /api/v1/admin/post/p-1', 200, self::UPDATED);

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:post:update', 'p-1', '--data={"type":"t-2"}', '--part=a=b'],
        );

        self::assertSame(0, $code);
        self::assertCount(2, $harness->requests);
        self::assertSame('{"type":"t-2"}', $harness->requests[0]['body']);
        self::assertSame('{"block_a":"b"}', $harness->requests[1]['body']);
    }

    public function testAFailureOfTheSecondRequestReportsThePartialState(): void
    {
        $harness = $this->harness()
            ->respond('PUT /api/v1/admin/content/c-1', 200, self::UPDATED)
            ->respond(
                'PUT /api/v1/admin/content/c-1',
                400,
                ['meta' => ['errors' => true], 'data' => ['.block_a' => 'Invalid']],
            );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:content:update', 'c-1', '--type=t-2', '--part=a=b'],
        );

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame(
            ['id' => 'c-1', 'failedStep' => 2, 'appliedStep' => 1],
            AbstractCommandTest::decode($stderr)['data']['partial'],
        );
    }

    public function testAFailureOfTheFirstRequestStopsEverything(): void
    {
        $harness = $this->harness()->respond(
            'PUT /api/v1/admin/content/c-1',
            404,
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'message' => 'Not found']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:content:update', 'c-1', '--type=t-2', '--part=a=b'],
        );

        self::assertSame(4, $code);
        self::assertCount(1, $harness->requests);
        self::assertArrayNotHasKey('partial', AbstractCommandTest::decode($stderr)['data']);
    }

    public function testDryRunOfASingleUpdateSendsNothing(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:tag:update', 'tag-1', '--name=x', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $requests = AbstractCommandTest::decode($stdout)['requests'];
        self::assertCount(1, $requests);
        self::assertSame('PUT', $requests[0]['method']);
        self::assertSame('https://site.test/api/v1/admin/tag/tag-1', $requests[0]['url']);
        self::assertSame(['name' => 'x'], $requests[0]['body']);
        self::assertSame('Bearer ***', $requests[0]['headers']['Authorization']);
    }

    public function testDryRunOfATwoStepsUpdateShowsBothRequestsAndSendsNothing(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:content:update', 'c-1', '--type=t-2', '--part=a=b', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $requests = AbstractCommandTest::decode($stdout)['requests'];
        self::assertCount(2, $requests);
        self::assertSame(['type' => 't-2'], $requests[0]['body']);
        self::assertSame('PUT', $requests[1]['method']);
        self::assertSame(['block_a' => 'b'], $requests[1]['body']);
    }

    public function testDryRunOfATwoStepsUpdateShowsTheRealIdentifierInTheSecondRequest(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:content:update', 'c-1', '--type=t-2', '--part=a=b', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        $url = AbstractCommandTest::decode($stdout)['requests'][1]['url'];
        self::assertSame('https://site.test/api/v1/admin/content/c-1', $url);
    }

    public function testValidationErrorsOfTheUpdate(): void
    {
        $harness = $this->harness()->respond(
            'PUT /api/v1/admin/user/u-1',
            400,
            ['meta' => ['errors' => true], 'data' => ['.roles' => 'This value is not valid.']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:user:update', 'u-1', '--role=ROLE_ADMIN']);

        self::assertSame(2, $code);
        self::assertSame(['.roles' => 'This value is not valid.'], AbstractCommandTest::decode($stderr)['data']['fields']);
    }

    public function testTheIdentifierIsRequired(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:update', '--name=x']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testMediaCanNotBeUpdated(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:media:update', 'm-1']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
    }
}
