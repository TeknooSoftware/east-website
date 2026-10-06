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
use Teknoo\East\Website\Tools\Command\Front\CommentCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

/**
 * Tests of the comment posted on a published blog post: the redirection is not followed, the id is read from it
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CommentCommand::class)]
class CommentCommandTest extends TestCase
{
    public function testACommentIsPostedAndTheRedirectionIsNotFollowed(): void
    {
        $harness = (new ApiHarness())->respond(
            'POST /api/v1/post/my-post/comment',
            302,
            [],
            ['Location' => '/api/v1/post/my-post?id=comment-7'],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'my-post', '--author=Jane', '--title=Nice', '--content=Great post', '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);
        self::assertSame(
            [
                'meta' => ['error' => false, 'id' => 'comment-7', 'location' => '/api/v1/post/my-post?id=comment-7'],
                'data' => ['id' => 'comment-7'],
            ],
            AbstractCommandTest::decode($stdout),
        );

        self::assertCount(1, $harness->requests);
        self::assertSame('POST', $harness->requests[0]['method']);
        self::assertSame('/api/v1/post/my-post/comment', $harness->requests[0]['path']);
        self::assertSame('application/json', $harness->requests[0]['headers']['content-type']);
        self::assertSame('{"author":"Jane","title":"Nice","content":"Great post"}', $harness->requests[0]['body']);
    }

    public function testNoCredentialIsNeededButTheTokenIsSentWhenAvailable(): void
    {
        $anonymous = (new ApiHarness())->respond('POST /api/v1/post/p/comment', 302, [], ['Location' => '/api/v1/post/p?id=c1']);
        $authenticated = (new ApiHarness(['token' => 'jwt']))
            ->respond('POST /api/v1/post/p/comment', 302, [], ['Location' => '/api/v1/post/p?id=c1']);
        $tokens = ['website:front:comment:create', 'p', '--author=a', '--title=t', '--content=c'];

        [$anonymousCode] = AbstractCommandTest::execute($anonymous, $tokens);
        [$authenticatedCode] = AbstractCommandTest::execute($authenticated, $tokens);

        self::assertSame(0, $anonymousCode);
        self::assertSame(0, $authenticatedCode);
        self::assertArrayNotHasKey('authorization', $anonymous->requests[0]['headers']);
        self::assertSame('Bearer jwt', $authenticated->requests[0]['headers']['authorization']);
    }

    public function testTheConfigurationFileIsRequired(): void
    {
        $harness = new ApiHarness(null);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'p', '--author=a', '--title=t', '--content=c'],
        );

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertStringContainsString('website:auth:login', AbstractCommandTest::decode($stderr)['data']['message']);
        self::assertSame([], $harness->requests);
    }

    public function testTheSlugIsUrlEncoded(): void
    {
        $harness = (new ApiHarness())->respond('POST /api/v1/post/a%20b/comment', 302, [], ['Location' => '/api/v1/post/a%20b?id=c1']);

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'a b', '--author=a', '--title=t', '--content=c'],
        );

        self::assertSame(0, $code);
        self::assertSame('/api/v1/post/a%20b/comment', $harness->requests[0]['path']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function incompleteComments(): iterable
    {
        yield 'no author' => [['--title=t', '--content=c'], 'author'];
        yield 'no title' => [['--author=a', '--content=c'], 'title'];
        yield 'no content' => [['--author=a', '--title=t'], 'content'];
        yield 'empty author' => [['--author=', '--title=t', '--content=c'], 'author'];
        yield 'empty content' => [['--author=a', '--title=t', '--content='], 'content'];
    }

    /**
     * @param list<string> $options
     */
    #[DataProvider('incompleteComments')]
    public function testRequiredOptionsAreCheckedBeforeAnyRequest(array $options, string $missing): void
    {
        $harness = new ApiHarness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:front:comment:create', 'my-post', ...$options]);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The option --' . $missing . ' is required',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testThePostSlugIsRequired(): void
    {
        $harness = new ApiHarness();

        [$code] = AbstractCommandTest::execute($harness, ['website:front:comment:create', '--author=a', '--title=t', '--content=c']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    public function testInvalidCommentIsAValidationError(): void
    {
        $harness = (new ApiHarness())->respond(
            'POST /api/v1/post/my-post/comment',
            400,
            ['meta' => ['id' => 'x'], 'data' => ['title' => 'echoed']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'my-post', '--author=a', '--title=t', '--content=c'],
        );

        self::assertSame(2, $code);
        self::assertSame('validation', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testAnUnknownPostIsNotFound(): void
    {
        $harness = (new ApiHarness())->respond(
            'POST /api/v1/post/unknown/comment',
            404,
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'message' => 'Post not found']],
        );

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'unknown', '--author=a', '--title=t', '--content=c'],
        );

        self::assertSame(4, $code);
    }

    public function testADirectSuccessIsPrintedAsIs(): void
    {
        $harness = (new ApiHarness())->respond(
            'POST /api/v1/post/my-post/comment',
            200,
            ['meta' => ['id' => 'comment-1'], 'data' => ['id' => 'comment-1']],
        );

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'my-post', '--author=a', '--title=t', '--content=c'],
        );

        self::assertSame(0, $code);
        self::assertSame('comment-1', AbstractCommandTest::decode($stdout)['data']['id']);
    }

    public function testARedirectionOutOfTheApiIsAnError(): void
    {
        $harness = (new ApiHarness())->respond('POST /api/v1/post/my-post/comment', 302, [], ['Location' => '/login']);

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'my-post', '--author=a', '--title=t', '--content=c'],
        );

        self::assertSame(3, $code);
        self::assertStringContainsString('Unexpected redirection to "/login"', AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testDryRunSendsNothingAndMasksTheBearer(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'my-post', '--author=a', '--title=t', '--content=c', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $request = AbstractCommandTest::decode($stdout)['requests'][0];
        self::assertSame('POST', $request['method']);
        self::assertSame('https://site.test/api/v1/post/my-post/comment', $request['url']);
        self::assertSame(['author' => 'a', 'title' => 't', 'content' => 'c'], $request['body']);
        self::assertSame('Bearer ***', $request['headers']['Authorization']);
    }

    public function testDryRunOfAnAnonymousCommentHasNoBearer(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:front:comment:create', 'p', '--author=a', '--title=t', '--content=c', '--anonymous', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertArrayNotHasKey('Authorization', AbstractCommandTest::decode($stdout)['requests'][0]['headers']);
    }
}
