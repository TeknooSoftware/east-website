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

namespace Teknoo\Tests\East\Website\Tools\Resource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Resource\FrontArgument;
use Teknoo\East\Website\Tools\Resource\FrontEndpoint;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;

use function array_map;

/**
 * Tests of the catalogue of the resources and of the public endpoints of the API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Registry::class)]
#[CoversClass(FrontEndpoint::class)]
#[CoversClass(FrontArgument::class)]
class RegistryTest extends TestCase
{
    private function resource(string $name): ResourceDefinition
    {
        $resource = (new Registry())->resource($name);
        self::assertNotNull($resource, $name);

        return $resource;
    }

    /**
     * @return list<string>
     */
    private function operations(ResourceDefinition $resource): array
    {
        return array_map(static fn (Operation $operation): string => $operation->value, $resource->operations);
    }

    public function testResources(): void
    {
        self::assertSame(
            ['tag', 'type', 'content', 'post', 'item', 'user', 'media', 'comment'],
            array_map(static fn (ResourceDefinition $resource): string => $resource->name, (new Registry())->resources()),
        );
    }

    public function testUnknownResource(): void
    {
        self::assertNull((new Registry())->resource('unknown'));
    }

    public function testStandardResourcesSupportAllTheOperations(): void
    {
        foreach (['tag', 'type', 'content', 'post', 'item', 'user'] as $name) {
            self::assertSame(['list', 'get', 'create', 'update', 'delete'], $this->operations($this->resource($name)), $name);
        }
    }

    public function testPathsOfTheStandardResources(): void
    {
        $expected = [
            'tag' => ['tag', 'tags'],
            'type' => ['type', 'types'],
            'content' => ['content', 'contents'],
            'post' => ['post', 'posts'],
            'item' => ['item', 'items'],
            'user' => ['user', 'users'],
        ];

        foreach ($expected as $name => [$base, $list]) {
            self::assertSame($base, $this->resource($name)->basePath, $name);
            self::assertSame($list, $this->resource($name)->listPath, $name);
        }
    }

    public function testMediaCanNotBeUpdatedAndItsCreationIsASpecificCommand(): void
    {
        $media = $this->resource('media');

        self::assertSame(['list', 'get', 'delete'], $this->operations($media));
        self::assertSame('media', $media->basePath);
        self::assertSame('media', $media->listPath);
        self::assertSame([], $media->fields);
    }

    public function testCommentsDependOnTheirPost(): void
    {
        $comment = $this->resource('comment');

        self::assertSame(['post-id'], $comment->parents);
        self::assertSame('post/{post-id}/comment', $comment->basePath);
        self::assertSame('post/{post-id}/comments', $comment->listPath);
        self::assertSame(['list', 'get', 'update', 'delete'], $this->operations($comment));
        self::assertSame(
            ['moderatedAuthor', 'moderatedTitle', 'moderatedContent'],
            array_map(static fn ($field): string => $field->name, $comment->fields),
        );
    }

    public function testContentsAndPostsHaveTranslatableParts(): void
    {
        foreach (['content', 'post'] as $name) {
            self::assertTrue($this->resource($name)->hasParts, $name);
            self::assertTrue($this->resource($name)->translatable, $name);
        }

        self::assertTrue($this->resource('item')->translatable);
        self::assertFalse($this->resource('item')->hasParts);

        foreach (['tag', 'type', 'user', 'media', 'comment'] as $name) {
            self::assertFalse($this->resource($name)->hasParts, $name);
            self::assertFalse($this->resource($name)->translatable, $name);
        }
    }

    public function testOptionsOfTheFields(): void
    {
        $options = static fn (string $name): array => array_map(
            static fn ($field): string => $field->optionName(),
            (new Registry())->resource($name)?->fields ?? [],
        );

        self::assertSame(['name', 'slug', 'is-highlighted'], $options('tag'));
        self::assertSame(['name', 'template', 'block'], $options('type'));
        self::assertSame(
            ['author', 'type', 'tag', 'title', 'subtitle', 'slug', 'description', 'environment', 'locale-field'],
            $options('content'),
        );
        self::assertSame($options('content'), $options('post'));
        self::assertSame(
            ['name', 'location', 'parent', 'content', 'slug', 'hidden', 'position', 'environment', 'locale-field'],
            $options('item'),
        );
        self::assertSame(['first-name', 'last-name', 'email', 'role', 'active'], $options('user'));
    }

    public function testRolesOfTheUsersAreRestricted(): void
    {
        $roles = null;
        foreach ($this->resource('user')->fields as $field) {
            if ('roles' === $field->name) {
                $roles = $field;
            }
        }

        self::assertNotNull($roles);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $roles->choices);
    }

    public function testEveryTargetIsAResourceWhichCanBeListedWithoutParent(): void
    {
        $registry = new Registry();
        $targets = 0;

        foreach ($registry->resources() as $resource) {
            foreach ($resource->fields as $field) {
                if (null === $field->target) {
                    continue;
                }

                ++$targets;
                $where = $resource->name . '.' . $field->name . ' -> ' . $field->target;
                $target = $registry->resource($field->target);

                self::assertInstanceOf(ResourceDefinition::class, $target, $where);
                self::assertSame([], $target->parents, $where);
                self::assertTrue($target->supports(Operation::List), $where);
                self::assertContains($field->kind, [FieldKind::Id, FieldKind::IdList], $where);
            }
        }

        self::assertSame(8, $targets);
    }

    public function testTargetsOfTheRelations(): void
    {
        $targets = [];
        foreach ((new Registry())->resources() as $resource) {
            foreach ($resource->fields as $field) {
                if (null !== $field->target) {
                    $targets[$resource->name][$field->name] = $field->target;
                }
            }
        }

        self::assertSame(
            [
                'content' => ['author' => 'user', 'type' => 'type', 'tags' => 'tag'],
                'post' => ['author' => 'user', 'type' => 'type', 'tags' => 'tag'],
                'item' => ['parent' => 'item', 'content' => 'content'],
            ],
            $targets,
        );
    }

    public function testKindsOfTheRelations(): void
    {
        $kinds = [];
        foreach (['content', 'post', 'item'] as $name) {
            foreach ($this->resource($name)->fields as $field) {
                if (null !== $field->target) {
                    $kinds[$name][$field->name] = $field->kind;
                }
            }
        }

        self::assertSame(
            [
                'content' => ['author' => FieldKind::Id, 'type' => FieldKind::Id, 'tags' => FieldKind::IdList],
                'post' => ['author' => FieldKind::Id, 'type' => FieldKind::Id, 'tags' => FieldKind::IdList],
                'item' => ['parent' => FieldKind::Id, 'content' => FieldKind::Id],
            ],
            $kinds,
        );
    }

    public function testFieldsEditedOnSeveralLines(): void
    {
        $multiline = [];
        $fields = 0;
        foreach ((new Registry())->resources() as $resource) {
            foreach ($resource->fields as $field) {
                ++$fields;
                if ($field->multiline) {
                    $multiline[] = $resource->name . '.' . $field->name;
                    self::assertSame(FieldKind::String, $field->kind, $resource->name . '.' . $field->name);
                }
            }
        }

        self::assertSame(['content.description', 'post.description', 'comment.moderatedContent'], $multiline);
        self::assertGreaterThan(3, $fields);
    }

    public function testLabelFields(): void
    {
        $labels = [];
        foreach ((new Registry())->resources() as $resource) {
            $labels[$resource->name] = $resource->labelField;
        }

        self::assertSame(
            [
                'tag' => 'name',
                'type' => 'name',
                'content' => 'title',
                'post' => 'title',
                'item' => 'name',
                'user' => 'email',
                'media' => 'name',
                'comment' => 'title',
            ],
            $labels,
        );
    }

    public function testListColumns(): void
    {
        $columns = ['id', 'title', 'slug', 'type', 'author', 'tags', 'publishedAt', 'environment'];

        self::assertSame($columns, $this->resource('content')->listColumns);
        self::assertSame($columns, $this->resource('post')->listColumns);
        self::assertSame(['id', 'name', 'length'], $this->resource('media')->listColumns);

        foreach (['tag', 'type', 'item', 'user', 'comment'] as $name) {
            self::assertSame([], $this->resource($name)->listColumns, $name);
        }
    }

    public function testFrontEndpoints(): void
    {
        $front = (new Registry())->front();

        self::assertSame(
            ['content:get', 'post:get', 'post:list', 'post:list-by-tag'],
            array_map(static fn (FrontEndpoint $endpoint): string => $endpoint->name, $front),
        );
        self::assertSame(
            ['content/{slug}', 'post/{slug}', 'posts', 'posts/by/{tag}'],
            array_map(static fn (FrontEndpoint $endpoint): string => $endpoint->template, $front),
        );
        self::assertSame(
            [false, false, true, true],
            array_map(static fn (FrontEndpoint $endpoint): bool => $endpoint->paginated, $front),
        );
    }

    public function testTheHomePageIsTheDefaultContent(): void
    {
        $content = (new Registry())->front()[0];

        self::assertCount(1, $content->arguments);
        self::assertSame('slug', $content->arguments[0]->name);
        self::assertSame('default', $content->arguments[0]->default);
    }

    public function testTheSlugOfAPostIsRequired(): void
    {
        $post = (new Registry())->front()[1];

        self::assertSame('slug', $post->arguments[0]->name);
        self::assertNull($post->arguments[0]->default);
        self::assertSame([], (new Registry())->front()[2]->arguments);
        self::assertSame('tag', (new Registry())->front()[3]->arguments[0]->name);
    }
}
