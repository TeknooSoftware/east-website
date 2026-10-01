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

namespace Teknoo\Tests\East\Website\Tools\Support;

use RuntimeException;

use function count;
use function decoct;
use function fileperms;
use function json_decode;
use function str_starts_with;

use const DIRECTORY_SEPARATOR;

/**
 * Scenarios run against a real process of the CLI, with the real HTTP client and the fake API server: shared by the tests of the sources and by the tests of the phar.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait CliScenariosTrait
{
    private TempDir $temp;

    private FakeApiServer $server;

    private Cli $cli;

    abstract protected function binary(): string;

    protected function setUp(): void
    {
        $this->temp = new TempDir();

        try {
            $this->server = FakeApiServer::start($this->temp);
        } catch (RuntimeException $error) {
            $this->temp->remove();
            self::markTestSkipped($error->getMessage());
        }

        $this->cli = new Cli($this->binary(), $this->server->url, $this->temp->path('session.json'));
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        $this->temp->remove();
    }

    /**
     * @return array{int, string, string}
     */
    private function login(): array
    {
        return $this->cli->run(
            ['website:auth:login', '--username=key:me@example.com', '--api-key-file=-'],
            [],
            'secret',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requestsTo(string $method, string $path): array
    {
        return \array_values(\array_filter(
            $this->server->requests(),
            static fn (array $request): bool => $request['method'] === $method && $request['path'] === $path,
        ));
    }

    public function testLoginStoresAPrivateSessionAndIsReusedByTheNextCommands(): void
    {
        [$code, $stdout, $stderr] = $this->login();

        self::assertSame(0, $code, $stderr);
        self::assertStringNotContainsString('sig', $stdout, 'The token must not be printed without --print-token');
        self::assertFileExists($this->temp->path('session.json'));
        if (DIRECTORY_SEPARATOR === '/') {
            self::assertSame('600', decoct(fileperms($this->temp->path('session.json')) & 0777));
        }

        [$code, $stdout] = $this->cli->run(['website:tag:list', '--compact']);
        $document = json_decode($stdout, true);

        self::assertSame(0, $code);
        self::assertSame('tag-1', $document['data'][0]['id']);
        self::assertCount(1, $this->requestsTo('POST', '/api/v1/login'));
        self::assertStringStartsWith('Bearer ', $this->requestsTo('GET', '/api/v1/admin/tags')[0]['headers']['authorization']);
    }

    public function testEnvironmentCredentialsLoginAutomaticallyOnce(): void
    {
        $env = ['EAST_WEBSITE_USERNAME' => 'key:me@example.com', 'EAST_WEBSITE_API_KEY' => 'secret'];

        [$code] = $this->cli->run(['website:tag:list', '--compact'], $env);
        [$codeAgain] = $this->cli->run(['website:tag:list', '--compact'], $env);

        self::assertSame(0, $code);
        self::assertSame(0, $codeAgain);
        self::assertCount(1, $this->requestsTo('POST', '/api/v1/login'));
        self::assertCount(2, $this->requestsTo('GET', '/api/v1/admin/tags'));
    }

    public function testCreationSendsTheExactJsonContentTypeAndFollowsTheRedirection(): void
    {
        $this->login();

        [$code, $stdout, $stderr] = $this->cli->run(['website:tag:create', '--name=Hello', '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('tag-1', json_decode($stdout, true)['data']['id']);

        $creation = $this->requestsTo('POST', '/api/v1/admin/tag/new')[0];
        self::assertSame('application/json', $creation['contentType']);
        self::assertSame('{"name":"Hello"}', $creation['body']);
        self::assertCount(1, $this->requestsTo('GET', '/api/v1/admin/tag/tag-1'));
    }

    public function testContentWithPartsIsSentInTwoRequests(): void
    {
        $this->login();

        [$code, $stdout, $stderr] = $this->cli->run(
            ['website:content:create', '--title=T', '--type=type-1', '--part=intro=Hello', '--publish', '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame(['block_intro' => 'Hello', 'publish' => true], json_decode($stdout, true)['data']['received']);
        self::assertSame('{"type":"type-1","title":"T"}', $this->requestsTo('POST', '/api/v1/admin/content/new')[0]['body']);
        self::assertCount(1, $this->requestsTo('PUT', '/api/v1/admin/content/content-1'));
    }

    public function testMediaIsUploadedAsAMultipartRequest(): void
    {
        $this->login();
        $file = $this->temp->write('picture.png', 'not really a png');

        [$code, , $stderr] = $this->cli->run(['website:media:create', '--file=' . $file, '--name=Picture', '--alternative=Alt']);

        self::assertSame(0, $code, $stderr);
        $upload = $this->requestsTo('POST', '/api/v1/admin/media/new')[0];
        self::assertTrue(str_starts_with($upload['contentType'], 'multipart/form-data'), $upload['contentType']);
        self::assertSame(['name' => 'Picture', 'alternative' => 'Alt'], $upload['post']['media']);
        self::assertSame(['image' => 'picture.png'], $upload['files']['name']);
        self::assertSame(['image' => 16], $upload['files']['size']);
    }

    public function testFrontCommentIsNotFollowed(): void
    {
        $this->login();

        [$code, $stdout, $stderr] = $this->cli->run(
            ['website:front:comment:create', 'my-post', '--author=a', '--title=t', '--content=c', '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('comment-1', json_decode($stdout, true)['data']['id']);
        self::assertCount(0, $this->requestsTo('GET', '/api/v1/post/my-post'));
    }

    public function testDryRunSendsNothing(): void
    {
        [$code, $stdout] = $this->cli->run(['website:tag:create', '--name=x', '--dry-run', '--compact']);

        self::assertSame(0, $code);
        self::assertTrue(json_decode($stdout, true)['dryRun']);
        self::assertSame([], $this->server->requests());
    }

    public function testExitCodes(): void
    {
        [$code] = $this->cli->run(['website:tag:list']);
        self::assertSame(3, $code, 'No credentials');

        [$code, , $stderr] = $this->cli->run(
            ['website:auth:login', '--username=key:me@example.com', '--api-key-file=-'],
            [],
            'wrong',
        );
        self::assertSame(3, $code);
        self::assertSame('auth', json_decode($stderr, true)['data']['kind']);

        $this->login();
        [$code, , $stderr] = $this->cli->run(['website:tag:get', 'missing']);
        self::assertSame(4, $code);
        self::assertSame('Tag not found', json_decode($stderr, true)['data']['message']);

        [$code] = $this->cli->run(['website:tag:list', '--page=abc']);
        self::assertSame(2, $code);

        [$code] = $this->cli->run(['website:unknown']);
        self::assertSame(2, $code);

        [$code, , $stderr] = $this->cli->run(
            ['website:tag:list', '--url=http://127.0.0.1:1', '--timeout=2'],
            ['EAST_WEBSITE_TOKEN' => 'explicit-token'],
        );
        self::assertSame(1, $code);
        self::assertSame('transport', json_decode($stderr, true)['data']['kind']);
    }

    public function testListOfTheCommandsIsAvailableAsJson(): void
    {
        [$code, $stdout] = $this->cli->run(['list', '--format=json', '--short']);
        $document = json_decode($stdout, true);

        self::assertSame(0, $code);
        $names = \array_column($document['commands'], 'name');
        self::assertContains('website:type:create', $names);
        self::assertContains('website:front:post:list-by-tag', $names);
        self::assertGreaterThan(40, count($names));
    }
}
