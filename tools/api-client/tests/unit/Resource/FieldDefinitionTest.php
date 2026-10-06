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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FieldKind;

/**
 * Tests of the fields of the resources and of the console options built from them
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FieldDefinition::class)]
#[CoversClass(FieldKind::class)]
class FieldDefinitionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function optionNames(): iterable
    {
        yield 'single word' => ['name', 'name'];
        yield 'two words' => ['isHighlighted', 'is-highlighted'];
        yield 'first name' => ['firstName', 'first-name'];
        yield 'moderated' => ['moderatedAuthor', 'moderated-author'];
        yield 'locale' => ['localeField', 'locale-field'];
        yield 'three words' => ['aLongFieldName', 'a-long-field-name'];
    }

    #[DataProvider('optionNames')]
    public function testOptionNameIsTheKebabCaseOfTheName(string $name, string $expected): void
    {
        self::assertSame($expected, (new FieldDefinition($name, FieldKind::String, 'desc'))->optionName());
    }

    public function testExplicitOptionNameWins(): void
    {
        $field = new FieldDefinition('tags', FieldKind::IdList, 'Id of a tag', 'tag');

        self::assertSame('tag', $field->optionName());
        self::assertSame('tags', $field->name);
    }

    /**
     * @return iterable<string, array{FieldKind}>
     */
    public static function valueKinds(): iterable
    {
        yield 'string' => [FieldKind::String];
        yield 'int' => [FieldKind::Int];
        yield 'id' => [FieldKind::Id];
    }

    #[DataProvider('valueKinds')]
    public function testScalarKindsAcceptAValueWithoutDefault(FieldKind $kind): void
    {
        $option = (new FieldDefinition('field', $kind, 'A field'))->inputOption();

        self::assertSame('field', $option->getName());
        self::assertSame('A field', $option->getDescription());
        self::assertTrue($option->isValueRequired());
        self::assertFalse($option->isArray());
        self::assertFalse($option->isNegatable());
        self::assertNull($option->getDefault());
    }

    public function testBooleanKindIsNegatableWithoutDefault(): void
    {
        $option = (new FieldDefinition('isHighlighted', FieldKind::Bool, 'Highlight'))->inputOption();

        self::assertSame('is-highlighted', $option->getName());
        self::assertTrue($option->isNegatable());
        self::assertFalse($option->acceptValue());
        self::assertNull($option->getDefault());
    }

    /**
     * @return iterable<string, array{FieldKind}>
     */
    public static function listKinds(): iterable
    {
        yield 'id list' => [FieldKind::IdList];
        yield 'string list' => [FieldKind::StringList];
        yield 'blocks' => [FieldKind::Blocks];
    }

    #[DataProvider('listKinds')]
    public function testListKindsAreRepeatableOptions(FieldKind $kind): void
    {
        $option = (new FieldDefinition('tags', $kind, 'Id of a tag', 'tag'))->inputOption();

        self::assertSame('tag', $option->getName());
        self::assertTrue($option->isArray());
        self::assertTrue($option->isValueRequired());
        self::assertSame([], $option->getDefault());
        self::assertStringContainsString('repeatable', $option->getDescription());
        self::assertStringContainsString('empty value', $option->getDescription());
    }

    public function testKindsWhichAreLists(): void
    {
        self::assertTrue(FieldKind::IdList->isList());
        self::assertTrue(FieldKind::StringList->isList());
        self::assertTrue(FieldKind::Blocks->isList());
        self::assertFalse(FieldKind::String->isList());
        self::assertFalse(FieldKind::Int->isList());
        self::assertFalse(FieldKind::Bool->isList());
        self::assertFalse(FieldKind::Id->isList());
    }

    public function testChoices(): void
    {
        $field = new FieldDefinition('roles', FieldKind::StringList, 'Role', 'role', ['ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $field->choices);
        self::assertSame([], (new FieldDefinition('name', FieldKind::String, 'Name'))->choices);
    }

    public function testAFieldHasNoTargetAndIsOnASingleLineByDefault(): void
    {
        $field = new FieldDefinition('name', FieldKind::String, 'Name');

        self::assertNull($field->target);
        self::assertFalse($field->multiline);

        $id = new FieldDefinition('parent', FieldKind::Id, 'Id of the parent', 'parent-id', ['a', 'b']);

        self::assertNull($id->target);
        self::assertFalse($id->multiline);
    }

    public function testTargetOfAnId(): void
    {
        $field = new FieldDefinition('author', FieldKind::Id, 'Id of the author (a user)', target: 'user');

        self::assertSame('user', $field->target);
        self::assertFalse($field->multiline);
        self::assertSame('author', $field->optionName());
        self::assertSame([], $field->choices);

        $list = new FieldDefinition('tags', FieldKind::IdList, 'Id of a tag', 'tag', [], 'tag');

        self::assertSame('tag', $list->target);
        self::assertSame('tag', $list->optionName());
        self::assertSame('tags', $list->name);
    }

    public function testMultilineText(): void
    {
        $field = new FieldDefinition('description', FieldKind::String, 'Description', multiline: true);

        self::assertTrue($field->multiline);
        self::assertNull($field->target);
        self::assertSame('description', $field->optionName());

        $positional = new FieldDefinition('description', FieldKind::String, 'Description', null, [], null, true);

        self::assertTrue($positional->multiline);
        self::assertNull($positional->target);
    }

    public function testTheTargetAndTheLinesDoNotChangeTheConsoleOption(): void
    {
        $plain = (new FieldDefinition('author', FieldKind::Id, 'Id of the author'))->inputOption();
        $targeted = (new FieldDefinition('author', FieldKind::Id, 'Id of the author', target: 'user', multiline: true))
            ->inputOption();

        self::assertSame($plain->getName(), $targeted->getName());
        self::assertSame($plain->getDescription(), $targeted->getDescription());
        self::assertSame($plain->isValueRequired(), $targeted->isValueRequired());
        self::assertSame($plain->isArray(), $targeted->isArray());
        self::assertSame($plain->getDefault(), $targeted->getDefault());
    }
}
