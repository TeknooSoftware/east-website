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
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Tui\Form\FormRow;
use Teknoo\East\Website\Tools\Tui\Form\FormState;
use Teknoo\East\Website\Tools\Tui\Form\RowKind;

/**
 * Tests of the rows of a form of the interactive mode and of the body sent from them: only what was changed, typed like
 * the options of the commands
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FormState::class)]
class FormStateTest extends TestCase
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

    /**
     * A row of a field of the registry, of the kind the forms give to it.
     */
    private function row(string $resource, string $name, RowKind $kind, mixed $value): FormRow
    {
        $field = $this->field($resource, $name);

        return new FormRow($name, $name, $kind, $value, $field, $field->choices);
    }

    private function part(string $name, mixed $value, RowKind $kind = RowKind::Multiline): FormRow
    {
        return new FormRow(FormRow::PART_PREFIX . $name, $name, $kind, $value);
    }

    private function publish(): FormRow
    {
        return new FormRow(FormRow::PUBLISH, 'publish', RowKind::Bool, false);
    }

    /**
     * The form of a content, as it is built for an existing one.
     */
    private function content(): FormState
    {
        return new FormState([
            new FormRow('id', 'id', RowKind::ReadOnly, 'c1'),
            $this->row('content', 'author', RowKind::Relation, ['id' => 'u1', 'label' => 'admin@teknoo.software']),
            $this->row('content', 'type', RowKind::Relation, ['id' => 'ty1', 'label' => 'Page']),
            $this->row('content', 'tags', RowKind::RelationList, [['id' => 't1', 'label' => 'php']]),
            $this->row('content', 'title', RowKind::Text, 'Hello'),
            $this->row('content', 'subtitle', RowKind::Text, ''),
            $this->row('content', 'slug', RowKind::Text, 'hello'),
            $this->row('content', 'description', RowKind::Multiline, "First\nSecond"),
            $this->row('content', 'localeField', RowKind::Text, ''),
            $this->part('intro', 'Introduction'),
            $this->part('count', '3', RowKind::Text),
            $this->publish(),
        ]);
    }

    private function payload(FormState $state): Payload
    {
        $payload = $state->payload(new PayloadBuilder());
        self::assertInstanceOf(Payload::class, $payload);

        return $payload;
    }

    private function set(FormState $state, string $name, mixed $value): FormRow
    {
        $row = $state->row($name);
        self::assertInstanceOf(FormRow::class, $row, $name);
        $row->value = $value;

        return $row;
    }

    public function testRows(): void
    {
        $title = $this->row('content', 'title', RowKind::Text, 'Hello');
        $slug = $this->row('content', 'slug', RowKind::Text, 'hello');
        $state = new FormState([$title, $slug]);

        self::assertSame([$title, $slug], $state->rows());
        self::assertSame($title, $state->row('title'));
        self::assertSame($slug, $state->row('slug'));
        self::assertNull($state->row('subtitle'));
        self::assertNull($state->row(''));
        self::assertSame([], (new FormState([]))->rows());
        self::assertNull((new FormState([]))->row('title'));
    }

    public function testAStateIsDirtyWhenAnEditableRowIsChanged(): void
    {
        $state = $this->content();
        self::assertFalse($state->isDirty());

        $title = $this->set($state, 'title', 'World');
        self::assertTrue($state->isDirty());

        $title->value = 'Hello';
        self::assertFalse($state->isDirty());

        $this->set($state, 'publish', true);
        self::assertTrue($state->isDirty());
    }

    public function testAReadOnlyOrForcedRowDoesNotMakeAStateDirty(): void
    {
        $state = $this->content();

        $this->set($state, 'id', 'c2');
        self::assertFalse($state->isDirty());

        $title = $state->row('title');
        self::assertNotNull($title);
        $title->forced = true;
        self::assertFalse($state->isDirty());

        self::assertFalse((new FormState([]))->isDirty());
    }

    public function testReplaceKeepsTheRowsAlreadyKnownAndTakesTheNewOnes(): void
    {
        $title = $this->row('content', 'title', RowKind::Text, 'Hello');
        $intro = $this->part('intro', 'Introduction');
        $removed = $this->part('footer', 'Footer');
        $publish = $this->publish();
        $state = new FormState([$title, $intro, $removed, $publish]);

        $title->value = 'World';
        $intro->value = 'Typed';
        $intro->forced = true;

        $newTitle = $this->row('content', 'title', RowKind::Text, 'From the server');
        $newIntro = $this->part('intro', 'From the server');
        $body = $this->part('body', '');
        $newPublish = $this->publish();

        $state->replace([$newTitle, $body, $newIntro, $newPublish]);

        self::assertSame([$title, $body, $intro, $publish], $state->rows());
        self::assertSame($title, $state->row('title'));
        self::assertSame('World', $title->value);
        self::assertSame('Hello', $title->initial);
        self::assertSame($intro, $state->row('block_intro'));
        self::assertSame('Typed', $intro->value);
        self::assertTrue($intro->forced);
        self::assertSame($body, $state->row('block_body'));
        self::assertNull($state->row('block_footer'));
        self::assertTrue($state->isDirty());
    }

    public function testReplaceWithNoRow(): void
    {
        $state = $this->content();
        $state->replace([]);

        self::assertSame([], $state->rows());
    }

    public function testNothingIsSentWhenNothingWasChanged(): void
    {
        $payload = $this->payload($this->content());

        self::assertSame([], $payload->fields);
        self::assertSame([], $payload->parts);
        self::assertFalse($payload->publish);
        self::assertSame([], $payload->body());
    }

    public function testOnlyTheChangedRowsAreSent(): void
    {
        $state = $this->content();
        $this->set($state, 'title', 'World');
        $this->set($state, 'description', "First\nSecond\nThird");

        $payload = $this->payload($state);

        self::assertSame(['title' => 'World', 'description' => "First\nSecond\nThird"], $payload->fields);
        self::assertSame([], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testAForcedRowIsSentEvenUnchanged(): void
    {
        $state = $this->content();
        foreach (['slug', 'author', 'block_intro'] as $name) {
            $row = $state->row($name);
            self::assertNotNull($row);
            $row->forced = true;
        }

        $payload = $this->payload($state);

        self::assertSame(['author' => 'u1', 'slug' => 'hello'], $payload->fields);
        self::assertSame(['intro' => 'Introduction'], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testAReadOnlyRowIsNeverSent(): void
    {
        $state = new FormState([
            new FormRow('title', 'title', RowKind::ReadOnly, 'Hello', $this->field('content', 'title')),
            new FormRow('block_intro', 'intro', RowKind::ReadOnly, 'Introduction'),
        ]);
        foreach ($state->rows() as $row) {
            $row->value = 'Changed';
            $row->forced = true;
        }

        $payload = $this->payload($state);

        self::assertSame([], $payload->fields);
        self::assertSame([], $payload->parts);
    }

    public function testAnEditableRowWithoutFieldIsNotSent(): void
    {
        $state = new FormState([new FormRow('note', 'note', RowKind::Text, '')]);
        $this->set($state, 'note', 'Changed');

        self::assertSame([], $this->payload($state)->body());
    }

    public function testARelationIsSentAsAnId(): void
    {
        $state = $this->content();
        $this->set($state, 'author', ['id' => 'u2', 'label' => 'writer@teknoo.software']);
        $this->set($state, 'type', ['id' => 'ty2', 'label' => 'ty2']);

        self::assertSame(['author' => 'u2', 'type' => 'ty2'], $this->payload($state)->fields);
    }

    public function testAClearedRelationIsSentAsNull(): void
    {
        $state = $this->content();
        $this->set($state, 'author', null);

        $payload = $this->payload($state);

        self::assertSame(['author' => null], $payload->fields);
        self::assertArrayHasKey('author', $payload->body());
    }

    public function testARelationListIsSentAsAListOfIds(): void
    {
        $state = $this->content();
        $this->set($state, 'tags', [['id' => 't2', 'label' => 'east'], ['id' => 't1', 'label' => 'php']]);

        self::assertSame(['tags' => ['t2', 't1']], $this->payload($state)->fields);
    }

    public function testAnEmptiedRelationListIsSentAsAnEmptyList(): void
    {
        $state = $this->content();
        $this->set($state, 'tags', []);

        self::assertSame(['tags' => []], $this->payload($state)->fields);
    }

    public function testAListOfIdsTypedAsATextIsSentOneValueByLine(): void
    {
        $state = new FormState([$this->row('content', 'tags', RowKind::Multiline, 't1')]);
        $this->set($state, 'tags', "t1\n\n  t2  \n \nt3\n");

        self::assertSame(['tags' => ['t1', 't2', 't3']], $this->payload($state)->fields);

        $this->set($state, 'tags', '');
        self::assertSame(['tags' => []], $this->payload($state)->fields);
    }

    public function testBooleansAreSentAsBooleans(): void
    {
        $state = new FormState([
            $this->row('tag', 'name', RowKind::Text, 'php'),
            $this->row('tag', 'isHighlighted', RowKind::Bool, false),
            $this->row('user', 'active', RowKind::Bool, true),
        ]);
        $this->set($state, 'isHighlighted', true);
        $this->set($state, 'active', false);

        self::assertSame(['isHighlighted' => true, 'active' => false], $this->payload($state)->fields);
    }

    public function testChoicesAreSentAsAListOfTexts(): void
    {
        $state = new FormState([$this->row('user', 'roles', RowKind::Choices, ['ROLE_USER'])]);
        $this->set($state, 'roles', ['ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['roles' => ['ROLE_USER', 'ROLE_ADMIN']], $this->payload($state)->fields);

        $this->set($state, 'roles', []);
        self::assertSame(['roles' => []], $this->payload($state)->fields);
    }

    public function testAChoiceWhichIsNotAcceptedIsReportedOnItsRow(): void
    {
        $state = new FormState([$this->row('user', 'roles', RowKind::Choices, ['ROLE_USER'])]);
        $roles = $this->set($state, 'roles', ['ROLE_ROOT']);

        self::assertNull($state->payload(new PayloadBuilder()));
        self::assertIsString($roles->error);
        self::assertStringContainsString('ROLE_ROOT', $roles->error);
        self::assertTrue($state->hasErrors());
    }

    public function testIntegersAreSentAsIntegers(): void
    {
        $state = new FormState([
            $this->row('item', 'name', RowKind::Text, 'Home'),
            $this->row('item', 'position', RowKind::Text, '1'),
        ]);
        $this->set($state, 'position', ' 12 ');
        $this->set($state, 'name', ' Blog ');

        self::assertSame(['name' => ' Blog ', 'position' => 12], $this->payload($state)->fields);

        $this->set($state, 'position', '-3');
        self::assertSame(-3, $this->payload($state)->fields['position'] ?? null);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notIntegers(): iterable
    {
        yield 'a text' => ['first'];
        yield 'a float' => ['1.5'];
        yield 'a number followed by a text' => ['12 items'];
    }

    #[DataProvider('notIntegers')]
    public function testAValueRefusedByItsFieldIsReportedOnItsRow(string $value): void
    {
        $state = new FormState([
            $this->row('item', 'name', RowKind::Text, 'Home'),
            $this->row('item', 'position', RowKind::Text, '1'),
        ]);
        $name = $this->set($state, 'name', 'Blog');
        $position = $this->set($state, 'position', $value);

        self::assertNull($state->payload(new PayloadBuilder()));
        self::assertIsString($position->error);
        self::assertStringContainsString('integer', $position->error);
        self::assertStringContainsString($value, $position->error);
        self::assertNull($name->error);
        self::assertTrue($state->hasErrors());

        $position->value = '2';
        $state->clearErrors();
        self::assertSame(['name' => 'Blog', 'position' => 2], $this->payload($state)->fields);
        self::assertFalse($state->hasErrors());
    }

    public function testTheBlocksOfATypeAreTypedOneByLine(): void
    {
        $state = new FormState([
            $this->row('type', 'name', RowKind::Text, 'Page'),
            $this->row('type', 'template', RowKind::Text, 'page.html.twig'),
            $this->row('type', 'blocks', RowKind::Multiline, ''),
        ]);
        $this->set($state, 'blocks', "intro:textarea\ncount:numeric");

        self::assertSame(
            ['blocks' => [['name' => 'intro', 'type' => '0'], ['name' => 'count', 'type' => '3']]],
            $this->payload($state)->fields,
        );

        $this->set($state, 'blocks', "  body:raw  \n\ntitle:text\nphoto:image\n");
        self::assertSame(
            [
                'blocks' => [
                    ['name' => 'body', 'type' => '1'],
                    ['name' => 'title', 'type' => '2'],
                    ['name' => 'photo', 'type' => '4'],
                ],
            ],
            $this->payload($state)->fields,
        );
    }

    public function testEmptiedBlocksOfATypeAreSentAsAnEmptyList(): void
    {
        $state = new FormState([$this->row('type', 'blocks', RowKind::Multiline, 'intro:textarea')]);
        $this->set($state, 'blocks', '');

        self::assertSame(['blocks' => []], $this->payload($state)->fields);
    }

    public function testABlockOfATypeWhichIsNotValidIsReportedOnItsRow(): void
    {
        $state = new FormState([$this->row('type', 'blocks', RowKind::Multiline, '')]);
        $blocks = $this->set($state, 'blocks', "intro:textarea\ncount:video");

        self::assertNull($state->payload(new PayloadBuilder()));
        self::assertIsString($blocks->error);
        self::assertStringContainsString('count:video', $blocks->error);
        self::assertStringContainsString('textarea|raw|text|numeric|image', $blocks->error);
    }

    public function testTheRowsOfTheBlocksAreSentAsParts(): void
    {
        $state = $this->content();
        $this->set($state, 'block_intro', "New\nintroduction");
        $this->set($state, 'block_count', '4');

        $payload = $this->payload($state);

        self::assertSame([], $payload->fields);
        self::assertSame(['intro' => "New\nintroduction", 'count' => '4'], $payload->parts);
        self::assertFalse($payload->publish);
        self::assertSame(['block_intro' => "New\nintroduction", 'block_count' => '4'], $payload->body());
    }

    public function testABlockWhichIsNotATextIsSentEmpty(): void
    {
        $state = $this->content();
        $this->set($state, 'block_intro', null);

        self::assertSame(['intro' => ''], $this->payload($state)->parts);
    }

    public function testThePublicationIsSentWhenItIsChecked(): void
    {
        $state = $this->content();
        $publish = $this->set($state, 'publish', true);

        $payload = $this->payload($state);

        self::assertTrue($payload->publish);
        self::assertSame([], $payload->fields);
        self::assertSame([], $payload->parts);
        self::assertSame(['publish' => true], $payload->body());

        $publish->value = false;
        self::assertFalse($this->payload($state)->publish);

        $publish->forced = true;
        self::assertFalse($this->payload($state)->publish);

        $publish->value = true;
        $publish->commit();
        $publish->forced = true;
        self::assertTrue($this->payload($state)->publish);
    }

    public function testFieldsPartsAndPublicationTogether(): void
    {
        $state = $this->content();
        $this->set($state, 'title', 'World');
        $this->set($state, 'type', ['id' => 'ty2', 'label' => 'Article']);
        $this->set($state, 'block_intro', 'New');
        $this->set($state, 'publish', true);

        $payload = $this->payload($state);

        self::assertSame(['type' => 'ty2', 'title' => 'World'], $payload->fields);
        self::assertSame(['intro' => 'New'], $payload->parts);
        self::assertTrue($payload->publish);
        self::assertTrue($payload->needsTwoSteps(false));
    }

    public function testTheExtraFieldsAndPartsAreSentAsTheyAre(): void
    {
        $state = $this->content();
        $state->setExtra(
            ['unknown' => ['nested' => [1, 2]], 'position' => '7', 'nothing' => null],
            ['footer' => 'Footer', 'raw' => ['a' => 'b']],
        );

        $payload = $this->payload($state);

        self::assertSame(['unknown' => ['nested' => [1, 2]], 'position' => '7', 'nothing' => null], $payload->fields);
        self::assertSame(['footer' => 'Footer', 'raw' => ['a' => 'b']], $payload->parts);
        self::assertFalse($payload->publish);
        self::assertFalse($state->isDirty());
    }

    public function testTheExtraFieldsAndPartsAreMergedUnderTheValuesOfTheRows(): void
    {
        $state = $this->content();
        $state->setExtra(
            ['title' => 'From --data', 'unknown' => 1, 'slug' => 'from-data'],
            ['intro' => 'From --data', 'footer' => 'Footer', 'count' => '9'],
        );
        $this->set($state, 'title', 'Typed');
        $this->set($state, 'block_intro', 'Typed');

        $payload = $this->payload($state);

        self::assertSame(['title' => 'Typed', 'unknown' => 1, 'slug' => 'from-data'], $payload->fields);
        self::assertSame(['intro' => 'Typed', 'footer' => 'Footer', 'count' => '9'], $payload->parts);
    }

    public function testSetExtraReplacesThePreviousOnes(): void
    {
        $state = $this->content();
        $state->setExtra(['unknown' => 1], ['footer' => 'Footer']);
        $state->setExtra(['other' => 2], []);

        $payload = $this->payload($state);

        self::assertSame(['other' => 2], $payload->fields);
        self::assertSame([], $payload->parts);
    }

    public function testCommitMakesEverythingSaved(): void
    {
        $state = $this->content();
        $state->setExtra(['unknown' => 1], ['footer' => 'Footer']);
        $title = $this->set($state, 'title', 'World');
        $intro = $this->set($state, 'block_intro', 'New');
        $publish = $this->set($state, 'publish', true);
        $slug = $state->row('slug');
        self::assertNotNull($slug);
        $slug->forced = true;

        $state->commit();

        self::assertFalse($state->isDirty());
        self::assertSame('World', $title->value);
        self::assertSame('World', $title->initial);
        self::assertSame('New', $intro->initial);
        self::assertTrue($publish->initial);
        self::assertFalse($slug->forced);

        $payload = $this->payload($state);
        self::assertSame([], $payload->fields);
        self::assertSame([], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testCommitWithoutThePartsKeepsTheBlocksAndThePublicationToSend(): void
    {
        $state = $this->content();
        $state->setExtra(['unknown' => 1], ['footer' => 'Footer']);
        $title = $this->set($state, 'title', 'World');
        $intro = $this->set($state, 'block_intro', 'New');
        $publish = $this->set($state, 'publish', true);
        $count = $state->row('block_count');
        $slug = $state->row('slug');
        self::assertNotNull($count);
        self::assertNotNull($slug);
        $count->forced = true;
        $slug->forced = true;

        $state->commit(false);

        self::assertSame('World', $title->initial);
        self::assertFalse($title->isChanged());
        self::assertFalse($slug->forced);
        self::assertSame('Introduction', $intro->initial);
        self::assertTrue($intro->isChanged());
        self::assertTrue($count->forced);
        self::assertFalse($publish->initial);
        self::assertTrue($publish->isChanged());
        self::assertTrue($state->isDirty());

        $payload = $this->payload($state);
        self::assertSame([], $payload->fields);
        self::assertSame(['footer' => 'Footer', 'intro' => 'New', 'count' => '3'], $payload->parts);
        self::assertTrue($payload->publish);

        $state->commit();

        self::assertFalse($state->isDirty());
        self::assertSame([], $this->payload($state)->body());
    }

    public function testTheErrorsOfTheServerAreReportedOnTheirRows(): void
    {
        $state = $this->content();

        $unmapped = $state->applyErrors([
            '.title' => 'This value should not be blank.',
            '.block_intro' => 'This value is too long.',
            '.publish' => 'Not allowed.',
        ]);

        self::assertSame([], $unmapped);
        self::assertSame('This value should not be blank.', $state->row('title')?->error);
        self::assertSame('This value is too long.', $state->row('block_intro')?->error);
        self::assertSame('Not allowed.', $state->row('publish')?->error);
        self::assertNull($state->row('slug')?->error);
        self::assertTrue($state->hasErrors());
    }

    public function testTheErrorOfANestedPathIsReportedOnTheRowOfItsField(): void
    {
        $state = new FormState([
            $this->row('type', 'name', RowKind::Text, 'Page'),
            $this->row('type', 'blocks', RowKind::Multiline, "intro:textarea\ncount:numeric"),
        ]);

        self::assertSame([], $state->applyErrors(['.blocks.0.type' => 'The selected choice is invalid.']));
        self::assertSame('The selected choice is invalid.', $state->row('blocks')?->error);
        self::assertNull($state->row('name')?->error);
    }

    public function testSeveralErrorsOfTheSameRowAreJoined(): void
    {
        $state = new FormState([
            $this->row('type', 'blocks', RowKind::Multiline, "intro:textarea\ncount:numeric"),
        ]);

        $unmapped = $state->applyErrors([
            '.blocks.0.type' => 'The selected choice is invalid.',
            '.blocks.1.name' => 'This value should not be blank.',
            '.blocks' => 'Too many blocks.',
        ]);

        self::assertSame([], $unmapped);
        self::assertSame(
            'The selected choice is invalid. This value should not be blank. Too many blocks.',
            $state->row('blocks')?->error,
        );
    }

    public function testAnErrorGivenAsAListOfTexts(): void
    {
        $state = $this->content();

        $unmapped = $state->applyErrors([
            '.title' => ['This value should not be blank.', 'This value is too short.'],
            '.slug' => ['Already used.', 12, ['nested'], null, 'Really.'],
            '.unknown' => ['First.', 'Second.'],
        ]);

        self::assertSame(['unknown: First. Second.'], $unmapped);
        self::assertSame('This value should not be blank. This value is too short.', $state->row('title')?->error);
        self::assertSame('Already used. Really.', $state->row('slug')?->error);
    }

    public function testTheErrorsWithoutAnyRowAreReturned(): void
    {
        $state = $this->content();

        $unmapped = $state->applyErrors([
            '.' => 'The object is not valid.',
            '.unknown' => 'Not expected.',
            '.title' => 'This value should not be blank.',
            '.id' => 'Can not be changed.',
            '.block_footer' => 'Unknown block.',
            '.other.0.name' => 'Nested.',
            '' => 'Without path.',
        ]);

        self::assertSame(
            [
                'The object is not valid.',
                'unknown: Not expected.',
                'id: Can not be changed.',
                'block_footer: Unknown block.',
                'other: Nested.',
                'Without path.',
            ],
            $unmapped,
        );
        self::assertSame('This value should not be blank.', $state->row('title')?->error);
        self::assertNull($state->row('id')?->error);
    }

    public function testThePathsOfTheErrorsCanBeGivenWithoutTheirLeadingDot(): void
    {
        $state = $this->content();

        $unmapped = $state->applyErrors(['title' => 'Not blank.', 0 => 'In a list.']);

        self::assertCount(1, $unmapped);
        self::assertStringContainsString('In a list.', $unmapped[0]);
        self::assertSame('Not blank.', $state->row('title')?->error);
    }

    public function testAnErrorWhichIsNotATextIsStillReported(): void
    {
        $state = $this->content();

        $unmapped = $state->applyErrors(['.title' => 42, '.slug' => null, '.' => 1.5]);

        self::assertSame(['1.5'], $unmapped);
        self::assertSame('42', $state->row('title')?->error);
        self::assertSame('', $state->row('slug')?->error);
        self::assertTrue($state->hasErrors());
    }

    public function testNewErrorsAreAddedToTheOnesAlreadyReported(): void
    {
        $state = $this->content();
        $state->applyErrors(['.title' => 'First.']);
        $state->applyErrors(['.title' => 'Second.']);

        self::assertSame('First. Second.', $state->row('title')?->error);
    }

    public function testNoError(): void
    {
        $state = $this->content();

        self::assertSame([], $state->applyErrors([]));
        self::assertFalse($state->hasErrors());
        self::assertFalse((new FormState([]))->hasErrors());
    }

    public function testClearErrors(): void
    {
        $state = $this->content();
        $state->applyErrors(['.title' => 'Not blank.', '.block_intro' => 'Too long.']);
        self::assertTrue($state->hasErrors());

        $state->clearErrors();

        self::assertFalse($state->hasErrors());
        foreach ($state->rows() as $row) {
            self::assertNull($row->error, $row->name);
        }

        self::assertSame('Hello', $state->row('title')?->value);
    }

    public function testAValueOfTheCommandLineIsSentAsItWasGivenWhileItsRowIsNotChanged(): void
    {
        $state = $this->content();
        $this->set($state, 'description', 'ignored')->force("A\nB", "A\r\nB");
        $this->set($state, 'block_intro', 'ignored')->force('Text', "Text\t");
        $this->set($state, 'tags', [])->force([['id' => 't1', 'label' => 't1']], 't1');

        $payload = $this->payload($state);
        self::assertSame(['tags' => 't1', 'description' => "A\r\nB"], $payload->fields);
        self::assertSame(['intro' => "Text\t"], $payload->parts);

        // The value of the form is sent as soon as the row is changed
        $this->set($state, 'description', "A\nB\nC");
        $this->set($state, 'block_intro', 'Other');
        $this->set($state, 'tags', [['id' => 't1', 'label' => 't1'], ['id' => 't2', 'label' => 't2']]);

        $payload = $this->payload($state);
        self::assertSame(['tags' => ['t1', 't2'], 'description' => "A\nB\nC"], $payload->fields);
        self::assertSame(['intro' => 'Other'], $payload->parts);
    }

    public function testTheMessagesOfTheServerAreCleanedBeforeBeingDisplayed(): void
    {
        $state = $this->content();

        $unmapped = $state->applyErrors([
            '.title' => "too short\x1b]0;owned\x07\x1b[2J\nsecond line",
            ".ghost\x1b[2J" => "odd\x1b[31m",
        ]);

        self::assertSame('too short]0;owned[2J second line', $this->set($state, 'title', 'x')->error);
        self::assertSame(['ghost[2J: odd[31m'], $unmapped);
    }
}
