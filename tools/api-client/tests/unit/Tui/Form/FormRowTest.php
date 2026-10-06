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

namespace Teknoo\Tests\East\Website\Tools\Tui\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Tui\Form\FormRow;
use Teknoo\East\Website\Tools\Tui\Form\RowKind;

/**
 * Tests of a row of a form of the interactive mode: what is displayed, what was changed and what must be sent
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FormRow::class)]
class FormRowTest extends TestCase
{
    private function field(string $resource, string $name): FieldDefinition
    {
        foreach ((new Registry())->resource($resource)->fields ?? [] as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        self::fail('Unknown field ' . $resource . '.' . $name);
    }

    public function testARowKeepsItsInitialValue(): void
    {
        $row = new FormRow('title', 'Title', RowKind::Text, 'Hello');

        self::assertSame('title', $row->name);
        self::assertSame('Title', $row->label);
        self::assertSame(RowKind::Text, $row->kind);
        self::assertSame('Hello', $row->value);
        self::assertSame('Hello', $row->initial);
        self::assertNull($row->field);
        self::assertSame([], $row->choices);
        self::assertSame('', $row->hint);
        self::assertNull($row->error);
        self::assertFalse($row->forced);
    }

    public function testARowOfAFieldWithChoicesAndAHint(): void
    {
        $roles = $this->field('user', 'roles');
        $row = new FormRow('roles', 'roles', RowKind::Choices, ['ROLE_USER'], $roles, $roles->choices, 'Space: check');

        self::assertSame($roles, $row->field);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $row->choices);
        self::assertSame('Space: check', $row->hint);
        self::assertSame(['ROLE_USER'], $row->initial);
    }

    public function testOnlyTheReadOnlyRowsAreNotEditable(): void
    {
        foreach (RowKind::cases() as $kind) {
            self::assertSame(
                RowKind::ReadOnly !== $kind,
                (new FormRow('row', 'row', $kind, null))->isEditable(),
                $kind->name,
            );
        }
    }

    /**
     * @return iterable<string, array{string, bool, string}>
     */
    public static function partNames(): iterable
    {
        yield 'a block' => ['block_intro', true, 'intro'];
        yield 'a block with an underscore' => ['block_main_text', true, 'main_text'];
        yield 'a block with a numeric name' => ['block_1', true, '1'];
        yield 'the blocks of a type' => ['blocks', false, ''];
        yield 'the publication' => ['publish', false, ''];
        yield 'the block of a form read only' => ['block intro', false, ''];
        yield 'a field' => ['title', false, ''];
    }

    #[DataProvider('partNames')]
    public function testTheRowsOfTheBlocksArePrefixed(string $name, bool $isPart, string $partName): void
    {
        $row = new FormRow($name, $name, RowKind::Text, '');

        self::assertSame($isPart, $row->isPart());
        if ($isPart) {
            self::assertSame($partName, $row->partName());
            self::assertSame(FormRow::PART_PREFIX . $partName, $row->name);
        }
    }

    public function testNamesOfTheSpecialRows(): void
    {
        self::assertSame('block_', FormRow::PART_PREFIX);
        self::assertSame('publish', FormRow::PUBLISH);
    }

    public function testARowIsChangedWhenItsValueIsNotTheInitialOne(): void
    {
        $row = new FormRow('title', 'title', RowKind::Text, 'Hello');
        self::assertFalse($row->isChanged());

        $row->value = 'World';
        self::assertTrue($row->isChanged());

        $row->value = 'Hello';
        self::assertFalse($row->isChanged());
    }

    public function testTheChangesAreDetectedStrictly(): void
    {
        $position = new FormRow('position', 'position', RowKind::Text, '1');
        $position->value = 1;
        self::assertTrue($position->isChanged());

        $author = new FormRow('author', 'author', RowKind::Relation, ['id' => 'u1', 'label' => 'a@b.c']);
        $author->value = ['id' => 'u1', 'label' => 'a@b.c'];
        self::assertFalse($author->isChanged());

        $author->value = ['id' => 'u2', 'label' => 'd@e.f'];
        self::assertTrue($author->isChanged());

        $author->value = null;
        self::assertTrue($author->isChanged());

        $tags = new FormRow('tags', 'tags', RowKind::RelationList, []);
        self::assertFalse($tags->isChanged());
        $tags->value = [['id' => 't1', 'label' => 'php']];
        self::assertTrue($tags->isChanged());
    }

    public function testOnlyAChangedRowMustBeSent(): void
    {
        $row = new FormRow('title', 'title', RowKind::Text, 'Hello');
        self::assertFalse($row->mustBeSent());

        $row->value = 'World';
        self::assertTrue($row->mustBeSent());
    }

    public function testAForcedRowIsSentEvenUnchanged(): void
    {
        $row = new FormRow('title', 'title', RowKind::Text, 'Hello');
        $row->forced = true;

        self::assertFalse($row->isChanged());
        self::assertTrue($row->mustBeSent());
    }

    public function testAReadOnlyRowIsNeverSent(): void
    {
        $row = new FormRow('id', 'id', RowKind::ReadOnly, 'c1');
        self::assertFalse($row->mustBeSent());

        $row->value = 'c2';
        self::assertTrue($row->isChanged());
        self::assertFalse($row->mustBeSent());

        $row->forced = true;
        self::assertFalse($row->mustBeSent());
    }

    public function testCommitMakesTheCurrentValueTheInitialOne(): void
    {
        $row = new FormRow('title', 'title', RowKind::Text, 'Hello');
        $row->value = 'World';
        $row->forced = true;

        $row->commit();

        self::assertSame('World', $row->value);
        self::assertSame('World', $row->initial);
        self::assertFalse($row->forced);
        self::assertFalse($row->isChanged());
        self::assertFalse($row->mustBeSent());

        $row->value = 'Hello';
        self::assertTrue($row->isChanged());
    }

    /**
     * @return iterable<string, array{RowKind, mixed, string}>
     */
    public static function texts(): iterable
    {
        yield 'bool checked' => [RowKind::Bool, true, '[x]'];
        yield 'bool unchecked' => [RowKind::Bool, false, '[ ]'];
        yield 'bool without value' => [RowKind::Bool, null, '[ ]'];
        yield 'bool with a truthy value' => [RowKind::Bool, 'yes', '[ ]'];

        yield 'choices' => [RowKind::Choices, ['ROLE_USER', 'ROLE_ADMIN'], 'ROLE_USER, ROLE_ADMIN'];
        yield 'one choice' => [RowKind::Choices, ['ROLE_ADMIN'], 'ROLE_ADMIN'];
        yield 'no choice' => [RowKind::Choices, [], ''];
        yield 'choices which are not a list' => [RowKind::Choices, 'ROLE_USER', ''];
        yield 'choices with other values' => [RowKind::Choices, ['ROLE_USER', 3, null, 'ROLE_ADMIN'], 'ROLE_USER, ROLE_ADMIN'];

        yield 'relation' => [RowKind::Relation, ['id' => 'u1', 'label' => 'admin@teknoo.software'], 'admin@teknoo.software (u1)'];
        yield 'relation labelled by its id' => [RowKind::Relation, ['id' => 'u1', 'label' => 'u1'], 'u1'];
        yield 'relation with an empty label' => [RowKind::Relation, ['id' => 'u1', 'label' => ''], 'u1'];
        yield 'relation without label' => [RowKind::Relation, ['id' => 'u1'], 'u1'];
        yield 'relation with a label which is not a text' => [RowKind::Relation, ['id' => 'u1', 'label' => ['x']], 'u1'];
        yield 'no relation' => [RowKind::Relation, null, '(none)'];
        yield 'relation which is a text' => [RowKind::Relation, 'u1', '(none)'];

        yield 'relation list' => [
            RowKind::RelationList,
            [['id' => 't1', 'label' => 'php'], ['id' => 't2', 'label' => 't2'], ['id' => 't3', 'label' => '']],
            'php (t1), t2, t3',
        ];
        yield 'relation list of one object' => [RowKind::RelationList, [['id' => 't1', 'label' => 'php']], 'php (t1)'];
        yield 'empty relation list' => [RowKind::RelationList, [], '(none)'];
        yield 'no relation list' => [RowKind::RelationList, null, '(none)'];

        yield 'multi-line text' => [RowKind::Multiline, "First\nSecond\nThird", 'First … (3 lines)'];
        yield 'multi-line text of two lines' => [RowKind::Multiline, "First\nSecond", 'First … (2 lines)'];
        yield 'multi-line text of one line' => [RowKind::Multiline, 'First', 'First'];
        yield 'empty multi-line text' => [RowKind::Multiline, '', ''];
        yield 'multi-line text which is not a text' => [RowKind::Multiline, ['First'], ''];

        yield 'read only' => [RowKind::ReadOnly, 'c1', 'c1'];
        yield 'read only on several lines' => [RowKind::ReadOnly, "intro:textarea\ncount:numeric", 'intro:textarea … (2 lines)'];
        yield 'empty read only' => [RowKind::ReadOnly, '', ''];
        yield 'read only without value' => [RowKind::ReadOnly, null, ''];

        yield 'text' => [RowKind::Text, 'Hello <b>world</b>', 'Hello <b>world</b>'];
        yield 'empty text' => [RowKind::Text, '', ''];
        yield 'text which is a number' => [RowKind::Text, 12, '12'];
        yield 'text which is a float' => [RowKind::Text, 1.5, '1.5'];
        yield 'text which is a boolean' => [RowKind::Text, true, ''];
        yield 'text without value' => [RowKind::Text, null, ''];
        yield 'text which is a list' => [RowKind::Text, ['a'], ''];
    }

    #[DataProvider('texts')]
    public function testTextOfARowOnASingleLine(RowKind $kind, mixed $value, string $expected): void
    {
        self::assertSame($expected, (new FormRow('row', 'row', $kind, $value))->text());
    }

    public function testTheTextFollowsTheCurrentValue(): void
    {
        $row = new FormRow('isHighlighted', 'isHighlighted', RowKind::Bool, false, $this->field('tag', 'isHighlighted'));
        self::assertSame('[ ]', $row->text());

        $row->value = true;
        self::assertSame('[x]', $row->text());
    }

    /**
     * @return iterable<string, array{mixed, list<string>}>
     */
    public static function stringValues(): iterable
    {
        yield 'a list of texts' => [['ROLE_USER', 'ROLE_ADMIN'], ['ROLE_USER', 'ROLE_ADMIN']];
        yield 'an empty list' => [[], []];
        yield 'no value' => [null, []];
        yield 'a text' => ['ROLE_USER', []];
        yield 'a boolean' => [true, []];
        yield 'other values are ignored' => [['ROLE_USER', 1, null, true, ['ROLE_ADMIN'], 'ROLE_ADMIN'], ['ROLE_USER', 'ROLE_ADMIN']];
        yield 'the keys are ignored' => [['a' => 'ROLE_USER', 'b' => 'ROLE_ADMIN'], ['ROLE_USER', 'ROLE_ADMIN']];
        yield 'empty texts are kept' => [['', 'ROLE_USER'], ['', 'ROLE_USER']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('stringValues')]
    public function testStringsOfARowWithChoices(mixed $value, array $expected): void
    {
        self::assertSame($expected, (new FormRow('roles', 'roles', RowKind::Choices, $value))->strings());
    }

    /**
     * @return iterable<string, array{RowKind, mixed, list<string>}>
     */
    public static function idValues(): iterable
    {
        yield 'a relation' => [RowKind::Relation, ['id' => 'u1', 'label' => 'admin@teknoo.software'], ['u1']];
        yield 'a relation without label' => [RowKind::Relation, ['id' => 'u1'], ['u1']];
        yield 'no relation' => [RowKind::Relation, null, []];
        yield 'a relation which is a text' => [RowKind::Relation, 'u1', []];
        yield 'a relation without id' => [RowKind::Relation, ['label' => 'Admin'], []];
        yield 'a relation with a null id' => [RowKind::Relation, ['id' => null, 'label' => 'Admin'], []];
        yield 'a relation with an id which is not a text' => [RowKind::Relation, ['id' => 12, 'label' => 'Admin'], []];
        yield 'an empty relation' => [RowKind::Relation, [], []];

        yield 'a relation list' => [
            RowKind::RelationList,
            [['id' => 't1', 'label' => 'php'], ['id' => 't2', 'label' => 'east']],
            ['t1', 't2'],
        ];
        yield 'an empty relation list' => [RowKind::RelationList, [], []];
        yield 'no relation list' => [RowKind::RelationList, null, []];
        yield 'a relation list which is a text' => [RowKind::RelationList, 't1', []];
        yield 'a relation list with malformed objects' => [
            RowKind::RelationList,
            [['id' => 't1'], 't2', null, ['label' => 'east'], ['id' => 3], ['id' => ['t4']], ['id' => 't5', 'label' => 'x']],
            ['t1', 't5'],
        ];
        yield 'a single object given as a list' => [RowKind::RelationList, ['id' => 't1', 'label' => 'php'], []];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('idValues')]
    public function testIdsOfARelation(RowKind $kind, mixed $value, array $expected): void
    {
        self::assertSame($expected, (new FormRow('relation', 'relation', $kind, $value))->ids());
    }

    public function testAValueGivenOnTheCommandLineIsKeptAsItWasGivenWhileTheRowIsNotChanged(): void
    {
        $row = new FormRow('description', 'description', RowKind::Multiline, 'Old');
        self::assertNull($row->given());

        $row->force("A\nB", "A\r\nB");
        self::assertSame("A\nB", $row->value);
        self::assertTrue($row->forced);
        self::assertTrue($row->mustBeSent());
        self::assertSame(["A\r\nB"], $row->given());

        // A null given on the command line is still a given value
        $none = new FormRow('author', 'author', RowKind::Relation, ['id' => 'u1', 'label' => 'Ada']);
        $none->force(null, null);
        self::assertSame([null], $none->given());

        $row->value = "A\nB\nC";
        self::assertNull($row->given());

        $row->value = "A\nB";
        self::assertSame(["A\r\nB"], $row->given());

        $row->commit();
        self::assertFalse($row->forced);
        self::assertNull($row->given());
    }
}
