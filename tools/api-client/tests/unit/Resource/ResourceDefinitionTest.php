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
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;

/**
 * Tests of the description of a resource of the admin API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ResourceDefinition::class)]
#[CoversClass(Operation::class)]
class ResourceDefinitionTest extends TestCase
{
    private function definition(): ResourceDefinition
    {
        return new ResourceDefinition(
            name: 'comment',
            label: 'comment of a blog post',
            basePath: 'post/{post-id}/comment',
            listPath: 'post/{post-id}/comments',
            fields: [new FieldDefinition('moderatedTitle', FieldKind::String, 'Title')],
            operations: [Operation::List, Operation::Get],
            parents: ['post-id'],
        );
    }

    public function testPaths(): void
    {
        $definition = $this->definition();

        self::assertSame('post/{post-id}/comment/new', $definition->createPath());
        self::assertSame('post/{post-id}/comment/{id}', $definition->itemPath());
        self::assertSame('post/{post-id}/comment/{id}/delete', $definition->deletePath());
        self::assertSame('post/{post-id}/comments', $definition->listPath);
    }

    public function testSupportedOperations(): void
    {
        $definition = $this->definition();

        self::assertTrue($definition->supports(Operation::List));
        self::assertTrue($definition->supports(Operation::Get));
        self::assertFalse($definition->supports(Operation::Create));
        self::assertFalse($definition->supports(Operation::Update));
        self::assertFalse($definition->supports(Operation::Delete));
    }

    public function testDefaults(): void
    {
        $definition = new ResourceDefinition('tag', 'tag', 'tag', 'tags', [], []);

        self::assertSame([], $definition->parents);
        self::assertFalse($definition->translatable);
        self::assertFalse($definition->hasParts);
        self::assertSame([], $definition->listColumns);
        self::assertSame('name', $definition->labelField);
    }

    public function testDefaultsOfTheInteractiveModeWithTheOtherArguments(): void
    {
        $definition = $this->definition();

        self::assertSame([], $definition->listColumns);
        self::assertSame('name', $definition->labelField);
    }

    public function testListColumnsAndLabelField(): void
    {
        $definition = new ResourceDefinition(
            name: 'content',
            label: 'content',
            basePath: 'content',
            listPath: 'contents',
            fields: [new FieldDefinition('title', FieldKind::String, 'Title')],
            operations: [Operation::List],
            listColumns: ['id', 'title', 'publishedAt'],
            labelField: 'title',
        );

        self::assertSame(['id', 'title', 'publishedAt'], $definition->listColumns);
        self::assertSame('title', $definition->labelField);
        self::assertSame([], $definition->parents);
        self::assertFalse($definition->translatable);
        self::assertFalse($definition->hasParts);
    }

    public function testListColumnsAndLabelFieldAreTheLastArguments(): void
    {
        $definition = new ResourceDefinition('media', 'media', 'media', 'media', [], [], [], false, false, ['id', 'name'], 'id');

        self::assertSame(['id', 'name'], $definition->listColumns);
        self::assertSame('id', $definition->labelField);
    }

    public function testOperationsAreTheLastSegmentOfTheCommandNames(): void
    {
        self::assertSame(
            ['list', 'get', 'create', 'update', 'delete'],
            array_map(static fn (Operation $operation): string => $operation->value, Operation::cases()),
        );
    }
}
