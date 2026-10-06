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

namespace Teknoo\Tests\East\Website\Tools\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Command\SchemaCommand;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function array_column;
use function array_keys;
use function str_starts_with;

/**
 * Tests of the description of the API as JSON, made for agents: offline, without credentials
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SchemaCommand::class)]
class SchemaCommandTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    private function schema(ApiHarness $harness, string ...$tokens): array
    {
        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:schema', ...$tokens, '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('', $stderr);
        self::assertSame([], $harness->requests, 'The schema must not use the network');

        return AbstractCommandTest::decode($stdout);
    }

    public function testAllTheResourcesAndThePublicEndpointsAreDescribed(): void
    {
        $harness = new ApiHarness();

        $document = $this->schema($harness);

        self::assertFalse($document['meta']['error']);
        $data = $document['data'];

        self::assertSame(
            ['tag', 'type', 'content', 'post', 'item', 'user', 'media', 'comment'],
            array_keys($data['resources']),
        );
        self::assertSame(
            [
                'website:front:content:get',
                'website:front:post:get',
                'website:front:post:list',
                'website:front:post:list-by-tag',
            ],
            array_column($data['front'], 'command'),
        );
        foreach ($data['front'] as $endpoint) {
            self::assertTrue(str_starts_with($endpoint['path'], '/api/v1/'));
            self::assertIsBool($endpoint['paginated']);
            self::assertIsArray($endpoint['arguments']);
            self::assertNotSame('', $endpoint['description']);
        }

        self::assertSame(
            ['textarea' => '0', 'raw' => '1', 'text' => '2', 'numeric' => '3', 'image' => '4'],
            $data['blockKinds'],
        );
    }

    public function testFrontEndpointsDescribeTheirArguments(): void
    {
        $harness = new ApiHarness();

        $front = $this->schema($harness)['data']['front'];

        self::assertSame([['name' => 'slug', 'required' => false, 'default' => 'default']], $front[0]['arguments']);
        self::assertSame([['name' => 'slug', 'required' => true, 'default' => null]], $front[1]['arguments']);
        self::assertSame([], $front[2]['arguments']);
        self::assertTrue($front[2]['paginated']);
        self::assertSame([['name' => 'tag', 'required' => true, 'default' => null]], $front[3]['arguments']);
    }

    public function testASingleResourceIsDescribedWithItsCommandsPathsAndFields(): void
    {
        $document = $this->schema(new ApiHarness(), 'content');

        self::assertSame(['resources'], array_keys($document['data']));
        self::assertSame(['content'], array_keys($document['data']['resources']));
        $content = $document['data']['resources']['content'];

        self::assertSame('content', $content['label']);
        self::assertSame(
            [
                'list' => 'website:content:list',
                'get' => 'website:content:get',
                'create' => 'website:content:create',
                'update' => 'website:content:update',
                'delete' => 'website:content:delete',
            ],
            $content['commands'],
        );
        self::assertSame([], $content['parents']);
        self::assertTrue($content['translatable']);
        self::assertTrue($content['parts']);
        self::assertSame(
            [
                'list' => '/api/v1/admin/contents',
                'item' => '/api/v1/admin/content/{id}',
                'create' => '/api/v1/admin/content/new',
                'delete' => '/api/v1/admin/content/{id}/delete',
            ],
            $content['paths'],
        );

        $fields = array_column($content['fields'], null, 'name');
        self::assertSame(
            ['author', 'type', 'tags', 'title', 'subtitle', 'slug', 'description', 'localeField'],
            array_keys($fields),
        );
        self::assertSame('--tag', $fields['tags']['option']);
        self::assertSame('id-list', $fields['tags']['kind']);
        self::assertSame('--locale-field', $fields['localeField']['option']);
        self::assertSame('--author', $fields['author']['option']);
        self::assertSame('id', $fields['author']['kind']);
        self::assertSame('string', $fields['title']['kind']);
        self::assertSame([], $fields['title']['choices']);
        self::assertNotSame('', $fields['title']['description']);
    }

    public function testTheKindsAndTheChoicesOfTheFields(): void
    {
        $user = $this->schema(new ApiHarness(), 'user')['data']['resources']['user'];
        $item = $this->schema(new ApiHarness(), 'item')['data']['resources']['item'];
        $tag = $this->schema(new ApiHarness(), 'tag')['data']['resources']['tag'];
        $type = $this->schema(new ApiHarness(), 'type')['data']['resources']['type'];

        $roles = array_column($user['fields'], null, 'name')['roles'];
        self::assertSame('--role', $roles['option']);
        self::assertSame('string-list', $roles['kind']);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $roles['choices']);

        $itemFields = array_column($item['fields'], 'kind', 'name');
        self::assertSame('int', $itemFields['position']);
        self::assertSame('bool', $itemFields['hidden']);
        self::assertSame('id', $itemFields['parent']);

        self::assertSame('--is-highlighted', array_column($tag['fields'], 'option', 'name')['isHighlighted']);

        $blocks = array_column($type['fields'], null, 'name')['blocks'];
        self::assertSame('blocks', $blocks['kind']);
        self::assertSame('--block', $blocks['option']);
        self::assertStringContainsString('textarea|raw|text|numeric|image', $blocks['description']);
    }

    public function testTheCommentIsDescribedWithItsParent(): void
    {
        $comment = $this->schema(new ApiHarness(), 'comment')['data']['resources']['comment'];

        self::assertSame(['post-id'], $comment['parents']);
        self::assertSame(
            ['list' => 'website:comment:list', 'get' => 'website:comment:get', 'update' => 'website:comment:update', 'delete' => 'website:comment:delete'],
            $comment['commands'],
        );
        self::assertFalse($comment['translatable']);
        self::assertFalse($comment['parts']);
        self::assertSame('/api/v1/admin/post/{post-id}/comments', $comment['paths']['list']);
        self::assertSame(
            ['moderatedAuthor', 'moderatedTitle', 'moderatedContent'],
            array_column($comment['fields'], 'name'),
        );
    }

    public function testTheMediaCreationIsTheUploadCommand(): void
    {
        $media = $this->schema(new ApiHarness(), 'media')['data']['resources']['media'];

        self::assertSame(
            [
                'list' => 'website:media:list',
                'get' => 'website:media:get',
                'delete' => 'website:media:delete',
                'create' => 'website:media:create',
            ],
            $media['commands'],
        );
        self::assertSame([], $media['fields']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function resources(): iterable
    {
        foreach (['tag', 'type', 'content', 'post', 'item', 'user', 'media', 'comment'] as $resource) {
            yield $resource => [$resource];
        }
    }

    #[DataProvider('resources')]
    public function testEveryDescribedCommandExists(string $resource): void
    {
        $harness = new ApiHarness();
        $described = $this->schema($harness, $resource)['data']['resources'][$resource];

        foreach ($described['commands'] as $command) {
            self::assertTrue($harness->application()->has($command), $command . ' is described but does not exist');
        }
    }

    public function testThePathsFollowTheConfiguredPrefix(): void
    {
        $harness = new ApiHarness(['adminPrefix' => '/cms/api']);

        $paths = $this->schema($harness, 'tag')['data']['resources']['tag']['paths'];

        self::assertSame('/cms/api/tags', $paths['list']);
        self::assertSame('/cms/api/tag/{id}', $paths['item']);
    }

    public function testNoConfigurationFileIsNeeded(): void
    {
        $harness = new ApiHarness(null);

        $document = $this->schema($harness, 'tag');

        self::assertFalse($document['meta']['error']);
        self::assertSame('/api/v1/admin/tags', $document['data']['resources']['tag']['paths']['list']);
        self::assertSame([], $harness->requests);
    }

    public function testAnUnknownResourceIsAUsageError(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:schema', 'unknown']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame(
            ['meta' => ['error' => true], 'data' => ['code' => 0, 'kind' => 'usage', 'message' => 'The resource "unknown" does not exist']],
            AbstractCommandTest::decode($stderr),
        );
    }

    public function testThePrettyFormatIsTheDefault(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:schema', 'tag']);

        self::assertSame(0, $code);
        self::assertStringContainsString("\n    \"data\": {", $stdout);
    }
}
