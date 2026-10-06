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

namespace Teknoo\Tests\East\Website\Tools\Tui\Widget;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Ansi\AnsiUtils;
use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Widget\EditorWidget;
use Symfony\Component\Tui\Widget\InputWidget;
use Teknoo\East\Website\Tools\Tui\Form\FormRow;
use Teknoo\East\Website\Tools\Tui\Form\RowKind;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;
use Teknoo\East\Website\Tools\Tui\Widget\FormWidget;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the form of the interactive mode: the drawing of its rows, the keys moving between them and editing
 * each kind of row, its events, its window on a small terminal and its read only mode
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FormWidget::class)]
class FormWidgetTest extends TestCase
{
    private const string DELETE = "\x1b[3~";

    /**
     * @var list<string>
     */
    private array $events = [];

    /**
     * @return list<FormRow> a row of each kind, the first one can not be edited
     */
    private function rows(): array
    {
        return [
            new FormRow('id', 'Id', RowKind::ReadOnly, 'abc', hint: 'Never displayed'),
            new FormRow('title', 'Title', RowKind::Text, 'Hello', hint: 'The title of the post'),
            new FormRow('body', 'Body', RowKind::Multiline, "l1\nl2", hint: 'Markdown'),
            new FormRow('active', 'Active', RowKind::Bool, false, hint: 'Space: check'),
            new FormRow('roles', 'Roles', RowKind::Choices, ['b'], choices: ['a', 'b', 'c'], hint: 'Left, Right: move'),
            new FormRow('author', 'Author', RowKind::Relation, ['id' => 'u1', 'label' => 'Ann'], hint: 'Enter: choose'),
            new FormRow(
                'tags',
                'Tags',
                RowKind::RelationList,
                [['id' => 't1', 'label' => 'One'], ['id' => 't2', 'label' => 't2']],
            ),
        ];
    }

    /**
     * @return list<FormRow> rows "Field 1", "Field 2"... with a hint
     */
    private function fields(int $count, RowKind $kind = RowKind::Text): array
    {
        $rows = [];
        for ($number = 1; $number <= $count; ++$number) {
            $rows[] = new FormRow('f' . $number, 'Field ' . $number, $kind, 'v' . $number, hint: 'hint ' . $number);
        }

        return $rows;
    }

    /**
     * Records every event of a form in $this->events.
     */
    private function listen(FormWidget $form): FormWidget
    {
        return $form
            ->onSubmit(function (): void {
                $this->events[] = 'submit';
            })
            ->onCancel(function (): void {
                $this->events[] = 'cancel';
            })
            ->onPick(function (FormRow $row): void {
                $this->events[] = 'pick:' . $row->name;
            })
            ->onChange(function (FormRow $row): void {
                $this->events[] = 'change:' . $row->name . '=' . json_encode(
                    $row->value,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                );
            })
            ->onAction(function (string $action): void {
                $this->events[] = 'action:' . $action;
            });
    }

    /**
     * Displays a form with the focus, then moves to one of its rows.
     */
    private function mount(TuiHarness $harness, FormWidget $form, ?string $row = null): void
    {
        $harness->mount($form);
        if (null !== $row) {
            $form->focusRow($row);
            $harness->render();
            self::assertSame($row, self::active($form));
        }
    }

    /**
     * @return string|null name of the active row
     *
     * @phpstan-impure the active row changes with the keys sent to the terminal
     */
    private static function active(FormWidget $form): ?string
    {
        return $form->activeRow()?->name;
    }

    private static function row(FormWidget $form, string $name): FormRow
    {
        foreach ($form->rows() as $row) {
            if ($row->name === $name) {
                return $row;
            }
        }

        self::fail('No row ' . $name);
    }

    /**
     * @param array<string> $lines
     * @return list<string> the lines without their styles nor their trailing spaces, like the lines of a screen
     */
    private static function plain(array $lines): array
    {
        return array_values(array_map(
            static fn (string $line): string => rtrim(AnsiUtils::stripAnsiCodes($line)),
            $lines,
        ));
    }

    /**
     * @param list<string> $lines
     * @return list<string> the lines of the active row and of the other marked lines
     */
    private static function marked(array $lines): array
    {
        return array_values(array_filter($lines, static fn (string $line): bool => str_starts_with($line, '> ')));
    }

    public function testItDisplaysEachKindOfRow(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount(new FormWidget($this->rows()));

        self::assertSame(
            [
                '  Id      abc',
                '> Title   Hello',
                '    The title of the post',
                '  Body    l1 … (2 lines)',
                '  Active  [ ]',
                '  Roles   [ ] a  [x] b  [ ] c',
                '  Author  Ann (u1)',
                '  Tags    One (t1), t2',
            ],
            $harness->lines(),
        );
    }

    public function testItDisplaysTheEmptyAndTheCheckedValues(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount(new FormWidget([
            new FormRow('title', 'Title', RowKind::Text, ''),
            new FormRow('body', 'Body', RowKind::Multiline, ''),
            new FormRow('line', 'Line', RowKind::Multiline, 'only one line'),
            new FormRow('active', 'Active', RowKind::Bool, true),
            new FormRow('roles', 'Roles', RowKind::Choices, ['c', 'a'], choices: ['a', 'b', 'c']),
            new FormRow('none', 'None', RowKind::Choices, [], choices: []),
            new FormRow('author', 'Author', RowKind::Relation, null),
            new FormRow('tags', 'Tags', RowKind::RelationList, []),
            new FormRow('id', 'Id', RowKind::ReadOnly, ''),
            new FormRow('count', 'Count', RowKind::Text, 3),
        ]));

        self::assertSame(
            [
                '> Title',
                '  Body',
                '  Line    only one line',
                '  Active  [x]',
                '  Roles   [x] a  [ ] b  [x] c',
                '  None',
                '  Author  (none)',
                '  Tags    (none)',
                '  Id',
                '  Count   3',
            ],
            $harness->lines(),
        );
    }

    public function testTheLabelsAreAlignedAndALongLabelIsCut(): void
    {
        $harness = new TuiHarness(60, 6);
        $harness->mount(new FormWidget([
            new FormRow('long', str_repeat('L', 30), RowKind::Bool, true),
            new FormRow('wide', '日本語のラベル', RowKind::Bool, false),
            new FormRow('short', 'Short', RowKind::Text, 'value'),
        ]));

        self::assertSame(
            [
                '> ' . str_repeat('L', 23) . '…  [x]',
                '  日本語のラベル            [ ]',
                '  Short                     value',
            ],
            $harness->lines(),
        );
    }

    public function testAValueWiderThanTheTerminalIsCut(): void
    {
        $harness = new TuiHarness(24, 6);
        $harness->mount(new FormWidget([
            new FormRow('active', 'Active', RowKind::Bool, true),
            new FormRow('author', 'Author', RowKind::Relation, ['id' => 'u1', 'label' => 'Ann of the blog']),
            new FormRow('roles', 'Roles', RowKind::Choices, [], choices: ['admin', 'editor', 'user']),
            new FormRow('id', 'Id', RowKind::ReadOnly, 'an identifier too long'),
        ]));

        self::assertSame(
            [
                '> Active  [x]',
                '  Author  Ann of the bl…',
                '  Roles   [ ] admin  [ ]',
                '  Id      an identifier…',
            ],
            $harness->lines(),
        );
    }

    public function testTheFirstEditableRowIsActive(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows()));

        self::assertSame('title', self::active($form));
        self::assertSame(['> Title   Hello'], self::marked($harness->lines()));
    }

    public function testTheKeysMoveBetweenTheRowsAndSkipTheRowsWhichCanNotBeEdited(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget([
            new FormRow('id', 'Id', RowKind::ReadOnly, 'abc'),
            new FormRow('title', 'Title', RowKind::Text, 'Hello'),
            new FormRow('slug', 'Slug', RowKind::ReadOnly, 'hello'),
            new FormRow('name', 'Name', RowKind::Text, 'World'),
            new FormRow('active', 'Active', RowKind::Bool, true),
            new FormRow('date', 'Date', RowKind::ReadOnly, 'today'),
        ])));

        self::assertSame('title', self::active($form));

        $harness->keys(Keys::TAB);
        self::assertSame('name', self::active($form));
        self::assertSame(['> Name    World'], self::marked($harness->lines()));

        $harness->keys(Keys::DOWN);
        self::assertSame('active', self::active($form));
        self::assertSame(['> Active  [x]'], self::marked($harness->lines()));

        // The last row can not be edited: the last editable one stays active
        $harness->keys(Keys::DOWN, Keys::TAB);
        self::assertSame('active', self::active($form));

        $harness->keys(Keys::SHIFT_TAB);
        self::assertSame('name', self::active($form));

        $harness->keys(Keys::UP);
        self::assertSame('title', self::active($form));
        self::assertSame(['> Title   Hello'], self::marked($harness->lines()));

        // The first row can not be edited: the first editable one stays active
        $harness->keys(Keys::UP, Keys::SHIFT_TAB);
        self::assertSame('title', self::active($form));

        $harness->keys(Keys::ENTER);
        self::assertSame('name', self::active($form));

        self::assertSame(
            ['  Id      abc', '  Title   Hello', '  Slug    hello', '> Name    World', '  Active  [x]', '  Date    today'],
            $harness->lines(),
        );
        self::assertSame([], $this->events);
    }

    public function testTypingInATextRowChangesItsValue(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows())));
        $title = self::row($form, 'title');

        $harness->type('!');
        self::assertSame('Hello!', $title->value);
        self::assertContains('> Title   Hello!', $harness->lines());

        $harness->keys(Keys::BACKSPACE, Keys::BACKSPACE);
        self::assertSame('Hell', $title->value);
        self::assertContains('> Title   Hell', $harness->lines());

        $harness->type(' 日本');
        self::assertSame('Hell 日本', $title->value);
        self::assertContains('> Title   Hell 日本', $harness->lines());

        self::assertSame(
            [
                'change:title="Hello!"',
                'change:title="Hello"',
                'change:title="Hell"',
                'change:title="Hell "',
                'change:title="Hell 日"',
                'change:title="Hell 日本"',
            ],
            $this->events,
        );
        self::assertTrue($title->isChanged());
        self::assertSame('title', self::active($form));
    }

    public function testTheKeysOfTheReadOnlyActionsAreTextInATextRow(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget([new FormRow('title', 'Title', RowKind::Text, '')])));

        $harness->type('e d q');

        self::assertSame('e d q', self::row($form, 'title')->value);
        self::assertSame(['> Title  e d q'], $harness->lines());
        self::assertNotContains('action:' . FormWidget::ACTION_EDIT, $this->events);
        self::assertCount(5, $this->events);
    }

    public function testAKeyWhichDoesNotChangeTheTextIsNotAChange(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows())));

        $harness->keys(Keys::LEFT, Keys::RIGHT, Keys::HOME, Keys::END);

        self::assertSame([], $this->events);
        self::assertSame('Hello', self::row($form, 'title')->value);
        self::assertSame('title', self::active($form));
    }

    public function testEnterInAMultilineRowAddsALine(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget([
            new FormRow('body', 'Body', RowKind::Multiline, ''),
            new FormRow('title', 'Title', RowKind::Text, 'Hello'),
        ])));
        $body = self::row($form, 'body');

        $harness->type('a');
        $harness->keys(Keys::ENTER);
        self::assertSame("a\n", $body->value);
        self::assertSame('body', self::active($form));

        $harness->type('b');
        self::assertSame("a\nb", $body->value);
        self::assertSame(['change:body="a"', 'change:body="a\n"', 'change:body="a\nb"'], $this->events);

        $lines = $harness->lines();
        self::assertSame('> Body', $lines[0]);
        self::assertContains('a', $lines);
        self::assertContains('b', $lines);
        self::assertSame('  Title  Hello', $lines[array_key_last($lines)]);
    }

    public function testEnterInAFilledMultilineRowKeepsItsLines(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = new FormWidget($this->rows()), 'body');
        $body = self::row($form, 'body');

        $harness->keys(Keys::ENTER);

        self::assertIsString($body->value);
        self::assertSame(2, substr_count($body->value, "\n"));
        self::assertSame(['l1', 'l2'], array_values(array_filter(explode("\n", $body->value))));
        self::assertSame('body', self::active($form));
    }

    public function testUpAndDownStayInTheEditorOfAMultilineRowAndTabLeavesIt(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget([
            new FormRow('title', 'Title', RowKind::Text, 'Hello'),
            new FormRow('body', 'Body', RowKind::Multiline, ''),
            new FormRow('active', 'Active', RowKind::Bool, false),
        ]));
        $body = self::row($form, 'body');

        $harness->keys(Keys::TAB);
        $harness->type('a');
        $harness->keys(Keys::ENTER);
        $harness->type('b');
        self::assertSame("a\nb", $body->value);

        $harness->keys(Keys::UP);
        self::assertSame('body', self::active($form));
        $harness->type('x');
        self::assertIsString($body->value);
        self::assertStringContainsString('x', explode("\n", $body->value)[0]);
        self::assertSame('b', explode("\n", $body->value)[1]);

        $harness->keys(Keys::UP, Keys::UP, Keys::UP);
        self::assertSame('body', self::active($form));

        $harness->keys(Keys::DOWN);
        self::assertSame('body', self::active($form));
        $harness->type('y');
        self::assertStringContainsString('y', explode("\n", $body->value)[1]);
        self::assertStringNotContainsString('y', explode("\n", $body->value)[0]);

        $harness->keys(Keys::DOWN, Keys::DOWN, Keys::DOWN);
        self::assertSame('body', self::active($form));

        $value = $body->value;
        $harness->keys(Keys::TAB);
        self::assertSame('active', self::active($form));
        self::assertSame($value, $body->value);

        $harness->keys(Keys::SHIFT_TAB);
        self::assertSame('body', self::active($form));

        $harness->keys(Keys::SHIFT_TAB);
        self::assertSame('title', self::active($form));
        self::assertSame($value, $body->value);
    }

    public function testOnlyTheActiveMultilineRowDisplaysItsEditor(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows()));

        self::assertContains('  Body    l1 … (2 lines)', $harness->lines());
        self::assertNotContains('l1', $harness->lines());

        $harness->keys(Keys::TAB);
        $lines = $harness->lines();
        $label = array_search('> Body', $lines, true);
        $first = array_search('l1', $lines, true);
        $second = array_search('l2', $lines, true);
        $next = array_search('  Active  [ ]', $lines, true);

        self::assertSame(2, $label);
        self::assertIsInt($first);
        self::assertIsInt($second);
        self::assertIsInt($next);
        self::assertGreaterThan($label, $first);
        self::assertSame($first + 1, $second);
        self::assertGreaterThan($second, $next);
        self::assertContains('    Markdown', array_slice($lines, $second, $next - $second));
        self::assertNotContains('  Body    l1 … (2 lines)', $lines);

        $harness->type('x');
        $harness->keys(Keys::ENTER, Keys::TAB);
        self::assertSame('active', self::active($form));
        self::assertContains('  Body    x … (3 lines)', $harness->lines());
        self::assertNotContains('l1', $harness->lines());
    }

    public function testABoolRowIsToggledBySpaceAndByEnter(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = $this->listen(new FormWidget($this->rows())), 'active');
        $active = self::row($form, 'active');

        $harness->keys(Keys::SPACE);
        self::assertTrue($active->value);
        self::assertContains('> Active  [x]', $harness->lines());

        $harness->keys(Keys::ENTER);
        self::assertFalse($active->value);
        self::assertContains('> Active  [ ]', $harness->lines());

        $harness->keys(Keys::ENTER, 'x', Keys::BACKSPACE, Keys::LEFT, Keys::RIGHT);
        self::assertTrue($active->value);
        self::assertSame('active', self::active($form));
        self::assertSame(['change:active=true', 'change:active=false', 'change:active=true'], $this->events);
    }

    public function testTheChoicesAreCheckedInTheOrderOfTheChoices(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = $this->listen(new FormWidget($this->rows())), 'roles');
        $roles = self::row($form, 'roles');

        // The last choice then the first one: the value is not in the order of the keys
        $harness->keys(Keys::RIGHT, Keys::RIGHT, Keys::SPACE);
        self::assertSame(['b', 'c'], $roles->value);
        self::assertContains('> Roles   [ ] a  [x] b  [x] c', $harness->lines());

        $harness->keys(Keys::LEFT, Keys::LEFT, Keys::SPACE);
        self::assertSame(['a', 'b', 'c'], $roles->value);
        self::assertContains('> Roles   [x] a  [x] b  [x] c', $harness->lines());

        $harness->keys(Keys::RIGHT, Keys::SPACE);
        self::assertSame(['a', 'c'], $roles->value);
        self::assertContains('> Roles   [x] a  [ ] b  [x] c', $harness->lines());

        $harness->keys(Keys::ENTER);
        self::assertSame(['a', 'b', 'c'], $roles->value);

        $harness->keys(Keys::LEFT, Keys::ENTER, Keys::RIGHT, Keys::RIGHT, Keys::ENTER);
        self::assertSame(['b'], $roles->value);
        self::assertFalse($roles->isChanged());
        self::assertContains('> Roles   [ ] a  [x] b  [ ] c', $harness->lines());

        self::assertSame(
            [
                'change:roles=["b","c"]',
                'change:roles=["a","b","c"]',
                'change:roles=["a","c"]',
                'change:roles=["a","b","c"]',
                'change:roles=["b","c"]',
                'change:roles=["b"]',
            ],
            $this->events,
        );
        self::assertSame('roles', self::active($form));
    }

    public function testTheCursorOfTheChoicesIsClampedAndGoesBackToTheFirstOne(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = new FormWidget($this->rows()), 'roles');
        $roles = self::row($form, 'roles');

        $harness->keys(Keys::LEFT, Keys::LEFT, Keys::SPACE);
        self::assertSame(['a', 'b'], $roles->value);

        $harness->keys(Keys::RIGHT, Keys::RIGHT, Keys::RIGHT, Keys::RIGHT, Keys::SPACE);
        self::assertSame(['a', 'b', 'c'], $roles->value);

        // Leaving the row then coming back: the cursor is on the first choice again
        $harness->keys(Keys::UP, Keys::DOWN, Keys::SPACE);
        self::assertSame(['b', 'c'], $roles->value);
    }

    public function testTheCurrentChoiceIsHighlightedInTheActiveRowOnly(): void
    {
        $form = new FormWidget([
            new FormRow('roles', 'Roles', RowKind::Choices, ['b'], choices: ['a', 'b']),
            new FormRow('others', 'Others', RowKind::Choices, [], choices: ['c']),
        ]);
        $context = new RenderContext(60, 4);

        $lines = $form->render($context);
        self::assertStringContainsString(Ansi::reverse('[ ] a') . '  [x] b', $lines[0]);
        self::assertSame('  Others  [ ] c', $lines[1]);

        $form->handleInput(Keys::RIGHT);
        $lines = $form->render($context);
        self::assertStringContainsString('[ ] a  ' . Ansi::reverse('[x] b'), $lines[0]);

        $form->handleInput(Keys::DOWN);
        $lines = $form->render($context);
        self::assertSame('  Roles   [ ] a  [x] b', $lines[0]);
        self::assertStringContainsString(Ansi::reverse('[ ] c'), $lines[1]);
    }

    public function testARowWithoutChoiceCanNotBeChecked(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget([
            new FormRow('none', 'None', RowKind::Choices, [], choices: []),
        ])));

        $harness->keys(Keys::RIGHT, Keys::SPACE, Keys::LEFT, Keys::ENTER, 'x');

        self::assertSame([], self::row($form, 'none')->value);
        self::assertSame([], $this->events);
        self::assertSame(['> None'], $harness->lines());
    }

    public function testEnterOnARelationAsksToPickAnObjectAndBackspaceClearsIt(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = new FormWidget($this->rows()), 'author');
        $author = self::row($form, 'author');
        $picked = [];
        $this->listen($form)->onPick(function (FormRow $row) use (&$picked): void {
            $picked[] = $row;
            $this->events[] = 'pick:' . $row->name;
        });

        $harness->keys(Keys::ENTER);
        self::assertSame([$author], $picked);
        self::assertSame(['id' => 'u1', 'label' => 'Ann'], $author->value);

        $harness->keys(Keys::BACKSPACE);
        self::assertNull($author->value);
        self::assertContains('> Author  (none)', $harness->lines());

        // Already empty: it is not a change
        $harness->keys(Keys::BACKSPACE, self::DELETE, 'x', Keys::SPACE, Keys::LEFT);
        self::assertNull($author->value);

        $harness->keys(Keys::ENTER);
        self::assertSame(['pick:author', 'change:author=null', 'pick:author'], $this->events);
        self::assertSame('author', self::active($form));
    }

    public function testDeleteClearsARelationLikeBackspace(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = $this->listen(new FormWidget($this->rows())), 'author');

        $harness->keys(self::DELETE);

        self::assertNull(self::row($form, 'author')->value);
        self::assertSame(['change:author=null'], $this->events);
    }

    public function testEnterOnARelationListAsksToPickObjectsAndBackspaceClearsIt(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = $this->listen(new FormWidget($this->rows())), 'tags');
        $tags = self::row($form, 'tags');

        $harness->keys(Keys::ENTER);
        self::assertSame(['pick:tags'], $this->events);
        self::assertCount(2, (array) $tags->value);

        $harness->keys(Keys::BACKSPACE);
        self::assertSame([], $tags->value);
        self::assertContains('> Tags    (none)', $harness->lines());

        // Already empty: it is not a change
        $harness->keys(Keys::BACKSPACE, self::DELETE);
        self::assertSame([], $tags->value);
        self::assertSame(['pick:tags', 'change:tags=[]'], $this->events);
    }

    public function testCtrlSAndF2SubmitTheForm(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows())));

        $harness->keys(Keys::CTRL_S);
        self::assertSame(['submit'], $this->events);

        $harness->keys(Keys::F2);
        self::assertSame(['submit', 'submit'], $this->events);

        // From any kind of row, the key is never a text
        foreach (['body', 'active', 'roles', 'author', 'tags'] as $name) {
            $form->focusRow($name);
            $harness->keys(Keys::CTRL_S, Keys::F2);
        }

        self::assertSame(array_fill(0, 12, 'submit'), $this->events);
        foreach ($form->rows() as $row) {
            self::assertFalse($row->isChanged(), $row->name);
        }
    }

    public function testTheTextsOfTheEditorsAreCopiedInTheRowsBeforeTheSubmit(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows()));
        $title = self::row($form, 'title');
        $body = self::row($form, 'body');
        $submitted = [];
        $form->onSubmit(static function () use (&$submitted, $title, $body): void {
            $submitted[] = [$title->value, $body->value];
        });

        [$input, $editor] = $form->all();
        self::assertInstanceOf(InputWidget::class, $input);
        self::assertInstanceOf(EditorWidget::class, $editor);
        $input->setValue('Set without any key');
        $editor->setText("first\nsecond");
        self::assertSame('Hello', $title->value);

        $harness->keys(Keys::CTRL_S);

        self::assertSame([['Set without any key', "first\nsecond"]], $submitted);
    }

    public function testSyncCopiesTheTextsOfTheEditorsInTheirRows(): void
    {
        $form = new FormWidget($this->rows());
        [$input, $editor] = $form->all();
        self::assertInstanceOf(InputWidget::class, $input);
        self::assertInstanceOf(EditorWidget::class, $editor);

        $input->setValue('Another title');
        $editor->setText("first\nsecond");
        self::assertSame('Hello', self::row($form, 'title')->value);
        self::assertSame("l1\nl2", self::row($form, 'body')->value);

        self::assertSame($form, $form->sync());

        self::assertSame('Another title', self::row($form, 'title')->value);
        self::assertSame("first\nsecond", self::row($form, 'body')->value);
        self::assertSame('abc', self::row($form, 'id')->value);
        self::assertFalse(self::row($form, 'active')->value);
    }

    public function testEscapeCancelsTheForm(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows())));

        $harness->keys(Keys::ESCAPE);
        self::assertSame(['cancel'], $this->events);

        foreach (['body', 'active', 'roles', 'author', 'tags'] as $name) {
            $form->focusRow($name);
            $harness->keys(Keys::ESCAPE);
        }

        self::assertSame(array_fill(0, 6, 'cancel'), $this->events);
        foreach ($form->rows() as $row) {
            self::assertFalse($row->isChanged(), $row->name);
        }
    }

    public function testTheKeysStillEditWithoutAnyCallback(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows()));

        $harness->keys(Keys::ESCAPE, Keys::CTRL_S, Keys::F2);
        $harness->type('!');
        self::assertSame('Hello!', self::row($form, 'title')->value);

        $form->focusRow('active');
        $harness->keys(Keys::SPACE);
        self::assertTrue(self::row($form, 'active')->value);

        $harness->keys(Keys::DOWN, Keys::SPACE);
        self::assertSame(['a', 'b'], self::row($form, 'roles')->value);

        $harness->keys(Keys::DOWN, Keys::ENTER);
        self::assertSame(['id' => 'u1', 'label' => 'Ann'], self::row($form, 'author')->value);
        $harness->keys(Keys::BACKSPACE);
        self::assertNull(self::row($form, 'author')->value);

        $harness->keys(Keys::DOWN, Keys::ENTER, Keys::BACKSPACE);
        self::assertSame([], self::row($form, 'tags')->value);
        self::assertSame('tags', self::active($form));
        self::assertContains('> Tags    (none)', $harness->lines());
    }

    public function testTheHintIsDisplayedOnlyUnderTheActiveRow(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows()));

        $hints = ['    The title of the post', '    Markdown', '    Space: check', '    Left, Right: move', '    Enter: choose'];
        $names = ['title', 'body', 'active', 'roles', 'author'];
        foreach ($names as $position => $name) {
            $form->focusRow($name);
            $harness->render();
            $lines = $harness->lines();

            self::assertSame([$hints[$position]], array_values(array_intersect($lines, $hints)), $name);
            self::assertNotContains('    Never displayed', $lines);
            if ('body' !== $name) {
                $marker = array_search(self::marked($lines)[0], $lines, true);
                self::assertSame($hints[$position], $lines[(int) $marker + 1], $name);
            }
        }

        // A row without hint has no line under it
        $harness->keys(Keys::DOWN);
        self::assertSame('tags', self::active($form));
        self::assertSame([], array_values(array_intersect($harness->lines(), $hints)));
        self::assertCount(7, $harness->lines());
    }

    public function testTheErrorIsDisplayedUnderItsRow(): void
    {
        $rows = $this->rows();
        $rows[1]->error = 'This value is too short';
        $rows[3]->error = 'Must be checked';
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($rows));

        self::assertSame(
            [
                '  Id      abc',
                '> Title   Hello',
                '    The title of the post',
                '    ! This value is too short',
                '  Body    l1 … (2 lines)',
                '  Active  [ ]',
                '    ! Must be checked',
                '  Roles   [ ] a  [x] b  [ ] c',
                '  Author  Ann (u1)',
                '  Tags    One (t1), t2',
            ],
            $harness->lines(),
        );

        $rows[1]->error = null;
        $form->focusRow('active');
        $harness->render();
        self::assertSame(
            ['  Title   Hello', '  Body    l1 … (2 lines)', '> Active  [ ]', '    Space: check', '    ! Must be checked'],
            array_slice($harness->lines(), 1, 5),
        );
    }

    public function testFocusRowMovesToAnEditableRow(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows()));

        self::assertSame($form, $form->focusRow('roles'));
        $harness->render();
        self::assertSame('roles', self::active($form));
        self::assertSame(['> Roles   [ ] a  [x] b  [ ] c'], self::marked($harness->lines()));

        // Neither an unknown row nor a row which can not be edited
        self::assertSame($form, $form->focusRow('unknown'));
        self::assertSame('roles', self::active($form));
        self::assertSame($form, $form->focusRow('id'));
        self::assertSame('roles', self::active($form));

        $form->focusRow('title');
        $harness->render();
        $harness->type('!');
        self::assertSame('Hello!', self::row($form, 'title')->value);
        self::assertContains('> Title   Hello!', $harness->lines());
    }

    public function testOnlyTheEditorOfTheActiveRowHasTheFocus(): void
    {
        $form = new FormWidget($this->rows());
        [$input, $editor] = $form->all();
        self::assertInstanceOf(InputWidget::class, $input);
        self::assertInstanceOf(EditorWidget::class, $editor);
        self::assertFalse($input->isFocused());

        self::assertSame($form, $form->setFocused(true));
        self::assertTrue($form->isFocused());
        self::assertTrue($input->isFocused());
        self::assertFalse($editor->isFocused());

        $form->focusRow('body');
        self::assertFalse($input->isFocused());
        self::assertTrue($editor->isFocused());

        $form->focusRow('active');
        self::assertFalse($input->isFocused());
        self::assertFalse($editor->isFocused());

        $form->focusRow('body')->setFocused(false);
        self::assertFalse($form->isFocused());
        self::assertFalse($input->isFocused());
        self::assertFalse($editor->isFocused());
    }

    public function testAFormTallerThanTheTerminalFollowsItsActiveRow(): void
    {
        $harness = new TuiHarness(40, 10);
        $harness->mount($form = new FormWidget($this->fields(20)));

        self::assertSame('> Field 1   v1', $harness->lines()[0]);
        self::assertSame('    hint 1', $harness->lines()[1]);
        self::assertCount(10, $harness->lines());

        for ($number = 2; $number <= 20; ++$number) {
            $harness->keys(Keys::TAB);
            $lines = $harness->lines();

            self::assertSame('f' . $number, self::active($form));
            self::assertLessThanOrEqual(10, count($lines));
            self::assertSame(['> Field ' . $number . ' ' . ($number < 10 ? ' ' : '') . ' v' . $number], self::marked($lines));
            self::assertContains('    hint ' . $number, $lines);
        }

        $lines = $harness->lines();
        self::assertSame('> Field 20  v20', $lines[8]);
        self::assertSame('    hint 20', $lines[9]);
        self::assertNotContains('  Field 1   v1', $lines);

        for ($number = 19; $number >= 1; --$number) {
            $harness->keys(Keys::SHIFT_TAB);
            $lines = $harness->lines();

            self::assertLessThanOrEqual(10, count($lines));
            self::assertSame(['> Field ' . $number . ' ' . ($number < 10 ? ' ' : '') . ' v' . $number], self::marked($lines));
            self::assertContains('    hint ' . $number, $lines);
        }

        self::assertSame(
            [
                '> Field 1   v1',
                '    hint 1',
                '  Field 2   v2',
                '  Field 3   v3',
                '  Field 4   v4',
                '  Field 5   v5',
                '  Field 6   v6',
                '  Field 7   v7',
                '  Field 8   v8',
                '  Field 9   v9',
            ],
            $harness->lines(),
        );
    }

    public function testTheWindowMovesOnlyWhenTheActiveRowLeavesIt(): void
    {
        $harness = new TuiHarness(40, 10);
        $harness->mount(new FormWidget($this->fields(20)));

        $harness->keys(...array_fill(0, 19, Keys::TAB));
        $harness->keys(...array_fill(0, 5, Keys::SHIFT_TAB));

        self::assertSame(
            [
                '  Field 12  v12',
                '  Field 13  v13',
                '  Field 14  v14',
                '> Field 15  v15',
                '    hint 15',
                '  Field 16  v16',
                '  Field 17  v17',
                '  Field 18  v18',
                '  Field 19  v19',
                '  Field 20  v20',
            ],
            $harness->lines(),
        );
    }

    public function testATallFormNeverDrawsMoreLinesThanItsHeight(): void
    {
        $form = new FormWidget($this->fields(20, RowKind::Bool));

        self::assertCount(10, $form->render(new RenderContext(40, 10)));
        self::assertCount(3, $form->render(new RenderContext(40, 3)));
        self::assertSame(['> Field 1   [ ]'], self::plain($form->render(new RenderContext(40, 1))));
        self::assertSame(['> Field 1   [ ]'], self::plain($form->render(new RenderContext(40, 0))));

        $form->focusRow('f20');
        self::assertSame(
            ['  Field 19  [ ]', '> Field 20  [ ]', '    hint 20'],
            self::plain($form->render(new RenderContext(40, 3))),
        );
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function provideSmallTerminals(): iterable
    {
        foreach ([1, 2, 5, 12] as $columns) {
            foreach ([1, 2, 4, 6] as $rows) {
                yield $columns . 'x' . $rows => [$columns, $rows];
            }
        }
    }

    #[DataProvider('provideSmallTerminals')]
    public function testItIsDisplayedAndEditedOnASmallTerminal(int $columns, int $rows): void
    {
        $fields = [
            new FormRow('title', 'A very long label for a title', RowKind::Text, 'Hello 日本語', hint: 'The title'),
            new FormRow('body', 'Body', RowKind::Multiline, "l1\nl2", hint: 'Markdown'),
            new FormRow('roles', 'Roles', RowKind::Choices, ['b'], choices: ['a', 'b', 'c']),
        ];
        $fields[0]->error = 'Wrong';
        $harness = new TuiHarness($columns, $rows);
        $harness->mount($form = new FormWidget($fields));

        $screens = [$harness->lines()];
        $harness->type('!');
        $screens[] = $harness->keys(Keys::TAB)->lines();
        $harness->type('x');
        $screens[] = $harness->keys(Keys::ENTER, Keys::ENTER, Keys::ENTER)->lines();
        $screens[] = $harness->keys(Keys::TAB, Keys::SPACE)->lines();

        self::assertSame('Hello 日本語!', $fields[0]->value);
        self::assertIsString($fields[1]->value);
        self::assertSame(5, substr_count($fields[1]->value, "\n") + 1);
        self::assertSame(['a', 'b'], $fields[2]->value);
        self::assertSame('roles', self::active($form));
        foreach ($screens as $lines) {
            self::assertLessThanOrEqual($rows, count($lines));
            foreach ($lines as $line) {
                self::assertLessThanOrEqual($columns, Ansi::width($line));
            }
        }
    }

    public function testAReadOnlyFormDisplaysItsRowsWithoutEditor(): void
    {
        $rows = $this->rows();
        $rows[3]->error = 'Kept';
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($rows, true));

        self::assertTrue($form->isReadOnly());
        self::assertSame([], $form->all());
        self::assertSame('id', self::active($form));
        self::assertSame(
            [
                '> Id      abc',
                '  Title   Hello',
                '  Body    l1 … (2 lines)',
                '  Active  [ ]',
                '    ! Kept',
                '  Roles   [ ] a  [x] b  [ ] c',
                '  Author  Ann (u1)',
                '  Tags    One (t1), t2',
            ],
            $harness->lines(),
        );
    }

    public function testUpAndDownMoveOverEveryRowOfAReadOnlyForm(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows(), true)));

        $names = ['id', 'title', 'body', 'active', 'roles', 'author', 'tags'];
        foreach ($names as $position => $name) {
            self::assertSame($name, self::active($form));
            self::assertCount(1, self::marked($harness->lines()));
            self::assertSame($position, array_search(self::marked($harness->lines())[0], $harness->lines(), true));
            // Never a hint in a read only form
            self::assertCount(7, $harness->lines());
            $harness->keys(Keys::DOWN);
        }

        $harness->keys(Keys::DOWN, Keys::TAB);
        self::assertSame('tags', self::active($form));

        $harness->keys(Keys::UP);
        self::assertSame('author', self::active($form));

        $harness->keys(Keys::SHIFT_TAB);
        self::assertSame('roles', self::active($form));

        $harness->keys(Keys::TAB);
        self::assertSame('author', self::active($form));

        $harness->keys(Keys::UP, Keys::UP, Keys::UP, Keys::UP, Keys::UP, Keys::UP, Keys::UP, Keys::SHIFT_TAB);
        self::assertSame('id', self::active($form));
        self::assertSame('> Id      abc', $harness->lines()[0]);
        self::assertSame([], $this->events);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideReadOnlyActions(): iterable
    {
        yield 'edit' => ['e', FormWidget::ACTION_EDIT];
        yield 'delete' => ['d', FormWidget::ACTION_DELETE];
        yield 'quit' => ['q', FormWidget::ACTION_QUIT];
    }

    #[DataProvider('provideReadOnlyActions')]
    public function testAnActionOfAReadOnlyFormIsReported(string $key, string $action): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows(), true)));

        $harness->keys($key, Keys::DOWN, $key);

        self::assertSame(['action:' . $action, 'action:' . $action], $this->events);
        self::assertSame('title', self::active($form));
    }

    public function testTheActionsHaveTheirOwnNames(): void
    {
        self::assertSame('form_edit', FormWidget::ACTION_EDIT);
        self::assertSame('form_delete', FormWidget::ACTION_DELETE);
        self::assertSame('form_quit', FormWidget::ACTION_QUIT);
    }

    public function testTypingChangesNothingInAReadOnlyForm(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = $this->listen(new FormWidget($this->rows(), true)));
        $screen = $harness->lines();

        foreach ($form->rows() as $position => $row) {
            $harness->keys('x', Keys::SPACE, Keys::ENTER, Keys::BACKSPACE, self::DELETE, Keys::LEFT, Keys::RIGHT);
            $harness->keys(Keys::CTRL_S, Keys::F2);

            self::assertSame($row, $form->activeRow());
            self::assertFalse($row->isChanged(), $row->name);
            self::assertSame(substr($screen[$position], 2), substr($harness->lines()[$position], 2));
            $harness->keys(Keys::DOWN);
        }

        self::assertSame([], $this->events);

        $harness->keys(Keys::ESCAPE);
        self::assertSame(['cancel'], $this->events);
    }

    public function testTheKeysOfAReadOnlyFormDoNothingWithoutCallback(): void
    {
        $harness = new TuiHarness(60, 16);
        $harness->mount($form = new FormWidget($this->rows(), true));

        $harness->keys('e', 'd', 'q', Keys::ESCAPE, Keys::DOWN);

        self::assertSame('title', self::active($form));
        self::assertSame('> Title   Hello', $harness->lines()[1]);
    }

    public function testTheActionsAreNotReportedByAFormWhichIsNotReadOnly(): void
    {
        $harness = new TuiHarness(60, 16);
        $this->mount($harness, $form = $this->listen(new FormWidget($this->rows())), 'active');

        $harness->keys('e', 'd', 'q');

        self::assertSame([], $this->events);
        self::assertFalse($form->isReadOnly());
    }

    public function testALongReadOnlyValueIsWrappedOnlyWhenItsRowIsActive(): void
    {
        $words = 'first line of the text' . "\n"
            . 'second line which is long enough to be wrapped on the next line of the screen' . "\n"
            . 'third';
        $harness = new TuiHarness(44, 14);
        $harness->mount(new FormWidget(
            [
                new FormRow('id', 'Id', RowKind::ReadOnly, 'abc'),
                new FormRow('body', 'Body', RowKind::ReadOnly, $words),
                new FormRow('empty', 'Empty', RowKind::ReadOnly, ''),
                new FormRow('none', 'None', RowKind::ReadOnly, null),
            ],
            true,
        ));

        self::assertSame(
            ['> Id     abc', '  Body   first line of the text … (3 lines)', '  Empty', '  None'],
            $harness->lines(),
        );

        $harness->keys(Keys::DOWN);
        $lines = $harness->lines();
        $block = array_slice($lines, 1, count($lines) - 3);

        self::assertSame('  Id     abc', $lines[0]);
        self::assertSame('> Body   first line of the text', $lines[1]);
        self::assertGreaterThan(3, count($block));
        self::assertSame('         third', $block[array_key_last($block)]);
        self::assertSame(['  Empty', '  None'], array_slice($lines, -2));
        $text = [];
        foreach ($block as $line) {
            self::assertLessThanOrEqual(44, Ansi::width($line));
            // Under the label, the next lines of the value are aligned with its first line
            self::assertContains(substr($line, 0, 9), ['> Body   ', '         ']);
            $text[] = trim(substr($line, 9));
        }

        self::assertSame(
            preg_split('/\s+/', $words),
            preg_split('/\s+/', implode(' ', $text)),
        );

        // An empty value is still one line high when its row is active
        $harness->keys(Keys::DOWN);
        self::assertSame(
            ['  Id     abc', '  Body   first line of the text … (3 lines)', '> Empty', '  None'],
            $harness->lines(),
        );

        $harness->keys(Keys::DOWN);
        self::assertSame('> None', $harness->lines()[3]);
        self::assertCount(4, $harness->lines());
    }

    public function testALongReadOnlyValueIsNotWrappedInAFormWhichIsNotReadOnly(): void
    {
        $harness = new TuiHarness(30, 8);
        $harness->mount($form = new FormWidget([
            new FormRow('body', 'Body', RowKind::ReadOnly, 'a single line which is too long for the terminal'),
            new FormRow('text', 'Text', RowKind::ReadOnly, "two\nlines"),
        ]));

        // Without any editable row, the first one is the active one and nothing can be done on it
        $harness->keys('x', Keys::ENTER, Keys::SPACE, Keys::BACKSPACE, Keys::DOWN, Keys::TAB);

        self::assertSame('body', self::active($form));
        self::assertSame(['> Body  a single line which i…', '  Text  two … (2 lines)'], $harness->lines());
        self::assertFalse($form->rows()[0]->isChanged());
    }

    public function testAReadOnlyValueHigherThanTheTerminalIsCut(): void
    {
        $text = implode("\n", array_map(static fn (int $number): string => 'line ' . $number, range(1, 30)));
        $harness = new TuiHarness(40, 6);
        $harness->mount($form = new FormWidget(
            [
                new FormRow('id', 'Id', RowKind::ReadOnly, 'abc'),
                new FormRow('body', 'Body', RowKind::ReadOnly, $text),
                new FormRow('end', 'End', RowKind::ReadOnly, 'last'),
            ],
            true,
        ));

        $harness->keys(Keys::DOWN);
        self::assertSame(
            ['  Id    abc', '> Body  line 1', '        line 2', '        line 3', '        line 4', '        line 5'],
            $harness->lines(),
        );

        $harness->keys(Keys::DOWN);
        self::assertSame('end', self::active($form));
        self::assertSame(['  Id    abc', '  Body  line 1 … (30 lines)', '> End   last'], $harness->lines());
    }

    public function testItGivesItsRowsAndItsEditors(): void
    {
        $rows = $this->rows();
        $form = new FormWidget($rows);

        self::assertSame($rows, $form->rows());
        self::assertFalse($form->isReadOnly());
        self::assertSame($rows[1], $form->activeRow());
        self::assertCount(2, $form->all());
        self::assertContainsOnlyInstancesOf(InputWidget::class, array_slice($form->all(), 0, 1));
        self::assertContainsOnlyInstancesOf(EditorWidget::class, array_slice($form->all(), 1));

        $readOnly = new FormWidget($rows, true);
        self::assertSame($rows, $readOnly->rows());
        self::assertTrue($readOnly->isReadOnly());
        self::assertSame($rows[0], $readOnly->activeRow());
        self::assertSame([], $readOnly->all());
    }

    public function testATextRowWithoutTextHasNoEditor(): void
    {
        $form = new FormWidget([
            new FormRow('count', 'Count', RowKind::Text, 3),
            new FormRow('none', 'None', RowKind::Multiline, null),
        ]);

        self::assertSame([], $form->all());
        self::assertSame($form, $form->sync());
        self::assertSame(3, $form->rows()[0]->value);
        self::assertSame(['> Count  3', '  None'], self::plain($form->render(new RenderContext(40, 2))));
    }

    public function testAnEmptyFormDoesNothing(): void
    {
        $harness = new TuiHarness(40, 6);
        $harness->mount($form = $this->listen(new FormWidget([])));

        $harness->keys(Keys::TAB, Keys::SHIFT_TAB, Keys::DOWN, Keys::UP, Keys::ENTER, Keys::SPACE, 'x', Keys::BACKSPACE);

        self::assertNull($form->activeRow());
        self::assertSame([], $form->rows());
        self::assertSame([], $form->all());
        self::assertSame($form, $form->focusRow('title'));
        self::assertSame([], $harness->lines());
        self::assertSame([], $this->events);

        $harness->keys(Keys::CTRL_S, Keys::ESCAPE);
        self::assertSame(['submit', 'cancel'], $this->events);

        $readOnly = new FormWidget([], true);
        $readOnly->handleInput(Keys::DOWN);
        $readOnly->handleInput(Keys::UP);
        self::assertNull($readOnly->activeRow());
        self::assertSame(['', ''], $readOnly->render(new RenderContext(40, 2)));
    }

    public function testItFillsTheHeightByDefault(): void
    {
        $form = new FormWidget($this->rows());

        self::assertTrue($form->isVerticallyExpanded());
        self::assertCount(12, $form->render(new RenderContext(60, 12)));
        self::assertSame(['', '', '', ''], array_slice($form->render(new RenderContext(60, 12)), 8));
    }

    public function testItIsNotHigherThanItsRowsWhenItIsNotExpanded(): void
    {
        $form = new FormWidget($this->rows());

        self::assertSame($form, $form->expandVertically(false));
        self::assertFalse($form->isVerticallyExpanded());
        self::assertCount(8, $form->render(new RenderContext(60, 12)));
        self::assertCount(3, $form->render(new RenderContext(60, 3)));

        self::assertTrue($form->expandVertically(true)->isVerticallyExpanded());
        self::assertCount(12, $form->render(new RenderContext(60, 12)));
    }

    public function testItIsRenderedWithoutBeingAttachedToAnInterface(): void
    {
        $rows = $this->rows();
        $rows[3]->error = 'Must be checked';
        $form = new FormWidget($rows);
        $context = new RenderContext(60, 9);

        // Without the interface, the editors can not be drawn: the texts are displayed as they are
        self::assertSame(
            [
                '  Id      abc',
                '> Title   Hello',
                '    The title of the post',
                '  Body    l1 … (2 lines)',
                '  Active  [ ]',
                '    ! Must be checked',
                '  Roles   [ ] a  [x] b  [ ] c',
                '  Author  Ann (u1)',
                '  Tags    One (t1), t2',
            ],
            self::plain($form->render($context)),
        );

        $form->focusRow('body');
        self::assertSame(
            ['  Title   Hello', '> Body    l1 … (2 lines)', '    Markdown', '  Active  [ ]'],
            array_slice(self::plain($form->render($context)), 1, 4),
        );
    }

    public function testTheKeysAreHandledWithoutBeingAttachedToAnInterface(): void
    {
        $form = $this->listen(new FormWidget($this->rows()));
        $context = new RenderContext(60, 9);

        $form->handleInput('!');
        $form->handleInput(Keys::TAB);
        $form->handleInput('x');
        $form->handleInput(Keys::TAB);
        $form->handleInput(Keys::SPACE);
        $form->handleInput(Keys::CTRL_S);

        self::assertSame('Hello!', self::row($form, 'title')->value);
        $body = self::row($form, 'body')->value;
        self::assertIsString($body);
        self::assertStringContainsString('x', $body);
        self::assertSame("l1\nl2", str_replace('x', '', $body));
        self::assertTrue(self::row($form, 'active')->value);
        self::assertSame(['submit'], array_slice($this->events, -1));
        self::assertSame(
            ['  Id      abc', '  Title   Hello!'],
            array_slice(self::plain($form->render($context)), 0, 2),
        );
        self::assertContains('> Active  [x]', self::plain($form->render($context)));
    }

    public function testAnErrorIsNeverDrawnRaw(): void
    {
        // An error can be a message of the server: its escape sequences and its line breaks must not reach the terminal
        $rows = [new FormRow('title', 'Title', RowKind::Text, 'Hello'), new FormRow('active', 'Active', RowKind::Bool, true)];
        $rows[0]->error = "too short\x1b]0;owned\x07\x1b[2J\nsecond line";

        $harness = new TuiHarness(44, 6);
        $harness->mount(new FormWidget($rows));

        self::assertStringNotContainsString("\x1b[2J", $harness->terminal->getOutput());
        self::assertStringNotContainsString("\x1b]0;owned", $harness->terminal->getOutput());
        self::assertSame(
            ['> Title   Hello', '    ! too short]0;owned[2J second line', '  Active  [x]'],
            $harness->lines(),
        );
    }
}
