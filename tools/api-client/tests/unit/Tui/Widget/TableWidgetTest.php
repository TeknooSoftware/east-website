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
use Teknoo\East\Website\Tools\Tui\Text\Ansi;
use Teknoo\East\Website\Tools\Tui\Widget\TableAction;
use Teknoo\East\Website\Tools\Tui\Widget\TableWidget;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the table of the interactive mode: its selection, its actions, and its drawing which never exceeds
 * the size of the terminal, whatever the number of rows and the width of the columns
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TableWidget::class)]
class TableWidgetTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $events = [];

    private function posts(): TableWidget
    {
        return (new TableWidget())->setRows(
            ['id', 'title', 'author'],
            [
                ['id' => 'a1', 'title' => 'First post of the blog', 'author' => 'Ann'],
                ['id' => 'b2', 'title' => '日本語のタイトルです', 'author' => 'Bob'],
                ['id' => 'c3', 'title' => 'Third', 'author' => 'Cid'],
            ],
        );
    }

    /**
     * @return TableWidget a table of rows "row 1", "row 2"...
     */
    private function numbered(int $count): TableWidget
    {
        $rows = [];
        for ($number = 1; $number <= $count; ++$number) {
            $rows[] = ['name' => 'row ' . $number];
        }

        return (new TableWidget())->setRows(['name'], $rows);
    }

    private function record(TableWidget $table): TableWidget
    {
        return $table->onAction(function (TableAction $action, ?int $index): void {
            $this->events[] = $action->name . ':' . (null === $index ? 'null' : $index);
        });
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

    public function testItDisplaysTheHeaderTheRuleTheRowsAndTheFooter(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($this->posts()->setFooter('3 posts — page 1/1'));

        self::assertSame(
            [
                '  id    title                   author',
                '  ────  ──────────────────────  ──────',
                '> a1    First post of the blog  Ann',
                '  b2    日本語のタイトルです    Bob',
                '  c3    Third                   Cid',
                '',
                '',
                '3 posts — page 1/1',
            ],
            $harness->lines(),
        );
    }

    public function testThereIsNoFooterLineWithoutFooter(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->posts());

        self::assertCount(5, $harness->lines());

        $table->setFooter('footer')->setFooter('');
        $harness->render();
        self::assertCount(5, $harness->lines());
    }

    public function testACellMissingInARowIsEmpty(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount((new TableWidget())->setRows(
            ['id', 'title', 'author'],
            [['id' => 'a1', 'author' => 'Ann', 'unknown' => 'never displayed'], ['id' => 'b2', 'title' => 'Second']],
        ));

        self::assertSame(
            [
                '  id    title   author',
                '  ────  ──────  ──────',
                '> a1            Ann',
                '  b2    Second',
            ],
            $harness->lines(),
        );
    }

    public function testTheFirstRowIsSelectedByDefault(): void
    {
        $table = $this->posts();

        self::assertSame(0, $table->selected());
    }

    public function testTheSelectionMovesWithTheArrowsAndTheLetters(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->posts());

        $harness->keys(Keys::DOWN);
        self::assertSame(1, $table->selected());
        self::assertSame('  a1    First post of the blog  Ann', $harness->lines()[2]);
        self::assertSame('> b2    日本語のタイトルです    Bob', $harness->lines()[3]);

        $harness->keys('j');
        self::assertSame(2, $table->selected());
        self::assertSame('> c3    Third                   Cid', $harness->lines()[4]);

        $harness->keys(Keys::UP);
        self::assertSame(1, $table->selected());

        $harness->keys('k');
        self::assertSame(0, $table->selected());
        self::assertSame('> a1    First post of the blog  Ann', $harness->lines()[2]);
        self::assertSame('  b2    日本語のタイトルです    Bob', $harness->lines()[3]);
    }

    public function testTheSelectionIsClampedAtBothEnds(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->posts());

        $harness->keys(Keys::UP, 'k', Keys::PAGE_UP, Keys::HOME);
        self::assertSame(0, $table->selected());
        self::assertSame('> a1    First post of the blog  Ann', $harness->lines()[2]);

        $harness->keys(Keys::DOWN, Keys::DOWN, Keys::DOWN, 'j', Keys::PAGE_DOWN, Keys::END);
        self::assertSame(2, $table->selected());
        self::assertSame('> c3    Third                   Cid', $harness->lines()[4]);
    }

    public function testHomeAndEndSelectTheFirstAndTheLastRows(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->numbered(20));

        $harness->keys(Keys::END);
        self::assertSame(19, $table->selected());
        self::assertSame('> row 20', $harness->lines()[6]);

        $harness->keys(Keys::HOME);
        self::assertSame(0, $table->selected());
        self::assertSame('> row 1', $harness->lines()[2]);
    }

    public function testThePageKeysMoveByTheNumberOfDisplayedRows(): void
    {
        // 8 lines: the header, its rule, 5 rows and the footer
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->numbered(20));

        $harness->keys(Keys::PAGE_DOWN);
        self::assertSame(5, $table->selected());

        $harness->keys(Keys::PAGE_DOWN);
        self::assertSame(10, $table->selected());

        $harness->keys(Keys::PAGE_UP);
        self::assertSame(5, $table->selected());

        $harness->keys(Keys::PAGE_DOWN, Keys::PAGE_DOWN, Keys::PAGE_DOWN, Keys::PAGE_DOWN);
        self::assertSame(19, $table->selected());

        $harness->keys(Keys::PAGE_UP, Keys::PAGE_UP, Keys::PAGE_UP, Keys::PAGE_UP, Keys::PAGE_UP);
        self::assertSame(0, $table->selected());
    }

    public function testSelectMovesTheSelection(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->posts());

        self::assertSame($table, $table->select(2));
        $harness->render();
        self::assertSame(2, $table->selected());
        self::assertSame('> c3    Third                   Cid', $harness->lines()[4]);

        self::assertSame(2, $table->select(50)->selected());
        self::assertSame(0, $table->select(-5)->selected());
        self::assertSame(1, $table->select(1)->selected());
    }

    public function testTheSelectionIsKeptInTheNewRows(): void
    {
        $table = $this->numbered(10)->select(7);

        self::assertSame(7, $table->setRows(['name'], array_fill(0, 12, ['name' => 'x']))->selected());
        self::assertSame(3, $table->setRows(['name'], array_fill(0, 4, ['name' => 'x']))->selected());
        self::assertNull($table->setRows(['name'], [])->selected());
        self::assertSame(0, $table->setRows(['name'], [['name' => 'x'], ['name' => 'y']])->selected());
    }

    /**
     * @return iterable<string, array{TableAction, string}> an action and the bytes sent by the terminal for its key
     */
    public static function provideActions(): iterable
    {
        yield 'Open' => [TableAction::Open, Keys::ENTER];
        yield 'Edit' => [TableAction::Edit, 'e'];
        yield 'Create' => [TableAction::Create, 'n'];
        yield 'Delete' => [TableAction::Delete, 'd'];
        yield 'PreviousPage' => [TableAction::PreviousPage, Keys::LEFT];
        yield 'NextPage' => [TableAction::NextPage, Keys::RIGHT];
        yield 'Reload' => [TableAction::Reload, 'r'];
        yield 'Toggle' => [TableAction::Toggle, Keys::SPACE];
        yield 'Clear' => [TableAction::Clear, Keys::BACKSPACE];
        yield 'Back' => [TableAction::Back, Keys::ESCAPE];
        yield 'Quit' => [TableAction::Quit, 'q'];
    }

    public function testEveryActionIsChecked(): void
    {
        $checked = [];
        foreach (self::provideActions() as [$action]) {
            $checked[] = $action;
        }

        self::assertSame(TableAction::cases(), $checked);
    }

    #[DataProvider('provideActions')]
    public function testAnActionIsReportedWithTheSelectedIndex(TableAction $action, string $key): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->record($this->posts()));

        $harness->keys($key);
        self::assertSame([$action->name . ':0'], $this->events);

        $harness->keys(Keys::DOWN, Keys::DOWN, $key);
        self::assertSame([$action->name . ':0', $action->name . ':2'], $this->events);
        self::assertSame(2, $table->selected());
    }

    #[DataProvider('provideActions')]
    public function testAnActionIsReportedWithoutIndexWhenTheTableIsEmpty(TableAction $action, string $key): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($this->record((new TableWidget())->setRows(['id', 'title'], [])));

        $harness->keys(Keys::DOWN, Keys::END, $key);

        self::assertSame([$action->name . ':null'], $this->events);
    }

    public function testTheKeysMovingTheSelectionAndTheUnknownKeysAreNotActions(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($this->record($this->posts()));

        $harness->keys(
            Keys::DOWN,
            Keys::UP,
            'j',
            'k',
            Keys::PAGE_DOWN,
            Keys::PAGE_UP,
            Keys::END,
            Keys::HOME,
            'x',
            Keys::TAB,
            Keys::F2,
        );

        self::assertSame([], $this->events);
    }

    public function testAnActionWithoutCallbackIsIgnored(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->posts());

        $harness->keys(Keys::ENTER, 'e', 'd', Keys::ESCAPE, 'q', Keys::DOWN);

        self::assertSame(1, $table->selected());
        self::assertSame('> b2    日本語のタイトルです    Bob', $harness->lines()[3]);
    }

    public function testAnEmptyTableDisplaysItsMessage(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = (new TableWidget())->setRows(['id', 'title'], [])->setFooter('0 post'));

        self::assertNull($table->selected());
        self::assertSame(['Nothing to display', '', '', '', '', '', '', '0 post'], $harness->lines());

        self::assertSame($table, $table->setEmptyMessage('No post, press n to create one'));
        $harness->render();
        self::assertSame('No post, press n to create one', $harness->lines()[0]);

        $harness->keys(Keys::DOWN, 'j', Keys::UP, Keys::PAGE_DOWN, Keys::END, Keys::HOME);
        self::assertNull($table->selected());
        self::assertSame('No post, press n to create one', $harness->lines()[0]);
    }

    public function testATableWithoutAnythingIsEmpty(): void
    {
        $harness = new TuiHarness(10, 4);
        $harness->mount($table = new TableWidget());

        self::assertNull($table->selected());
        self::assertNull($table->select(3)->selected());
        self::assertSame(['Nothing t…'], $harness->lines());
    }

    public function testTheMarksAreDisplayedWhenTheRowsCanBeMarked(): void
    {
        $harness = new TuiHarness(46, 8);
        $harness->mount($table = $this->posts());
        self::assertSame($table, $table->setMarked([0, 2]));
        $harness->render();

        self::assertSame(
            [
                '      id    title                   author',
                '      ────  ──────────────────────  ──────',
                '> [x] a1    First post of the blog  Ann',
                '  [ ] b2    日本語のタイトルです    Bob',
                '  [x] c3    Third                   Cid',
            ],
            $harness->lines(),
        );

        $table->setMarked([]);
        $harness->keys(Keys::DOWN);
        self::assertSame(
            [
                '  [ ] a1    First post of the blog  Ann',
                '> [ ] b2    日本語のタイトルです    Bob',
                '  [ ] c3    Third                   Cid',
            ],
            array_slice($harness->lines(), 2),
        );
    }

    public function testThereIsNoMarkColumnWhenTheRowsCanNotBeMarked(): void
    {
        $harness = new TuiHarness(46, 8);
        $harness->mount($table = $this->posts()->setMarked([1]));
        self::assertStringContainsString('[x]', $harness->screen());

        $table->setMarked(null);
        $harness->render();

        self::assertStringNotContainsString('[', $harness->screen());
        self::assertSame('  id    title                   author', $harness->lines()[0]);
        self::assertSame('> a1    First post of the blog  Ann', $harness->lines()[2]);
    }

    public function testTheRowsAreWindowedAroundTheSelection(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->numbered(20)->setFooter('20 rows'));

        self::assertSame(
            ['  name', '  ──────', '> row 1', '  row 2', '  row 3', '  row 4', '  row 5', '20 rows'],
            $harness->lines(),
        );

        $table->select(10);
        $harness->render();
        self::assertSame(
            ['  name', '  ──────', '  row 9', '  row 10', '> row 11', '  row 12', '  row 13', '20 rows'],
            $harness->lines(),
        );

        $harness->keys(Keys::END);
        self::assertSame(
            ['  name', '  ──────', '  row 16', '  row 17', '  row 18', '  row 19', '> row 20', '20 rows'],
            $harness->lines(),
        );
    }

    public function testTheSelectedRowIsAlwaysDisplayed(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->numbered(20)->setFooter('20 rows'));

        for ($index = 0; $index < 20; ++$index) {
            self::assertSame($index, $table->selected());
            self::assertContains('> row ' . ($index + 1), $harness->lines());
            self::assertCount(8, $harness->lines());
            $harness->keys(Keys::DOWN);
        }

        for ($index = 19; $index >= 0; --$index) {
            self::assertContains('> row ' . ($index + 1), $harness->lines());
            self::assertCount(8, $harness->lines());
            $harness->keys('k');
        }
    }

    public function testTheWidestColumnsAreShrunkOnANarrowTerminal(): void
    {
        $harness = new TuiHarness(30, 8);
        $harness->mount($this->posts());

        self::assertSame(
            [
                '  id    title           author',
                '  ────  ──────────────  ──────',
                '> a1    First post of…  Ann',
                '  b2    日本語のタイ…   Bob',
                '  c3    Third           Cid',
            ],
            $harness->lines(),
        );
    }

    public function testTheTitlesAreShrunkLikeTheCells(): void
    {
        $harness = new TuiHarness(20, 8);
        $harness->mount($this->posts());

        self::assertSame(
            [
                '  id    title  auth…',
                '  ────  ─────  ─────',
                '> a1    Firs…  Ann',
                '  b2    日本…  Bob',
                '  c3    Third  Cid',
            ],
            $harness->lines(),
        );
    }

    public function testTheLastColumnsAreDroppedWhenTheyCanNotBeShrunkAnymore(): void
    {
        $harness = new TuiHarness(16, 8);
        $harness->mount($this->posts());

        self::assertSame(
            ['  id    tit…', '  ────  ────', '> a1    Fir…', '  b2    日…', '  c3    Thi…'],
            $harness->lines(),
        );

        $harness = new TuiHarness(8, 8);
        $harness->mount($this->posts());

        self::assertSame(['  id', '  ────', '> a1', '  b2', '  c3'], $harness->lines());
    }

    public function testTheMarkColumnIsKeptOnANarrowTerminal(): void
    {
        $harness = new TuiHarness(12, 8);
        $harness->mount($this->posts()->setMarked([1])->setFooter('3 posts'));

        self::assertSame(
            ['      id', '      ────', '> [ ] a1', '  [x] b2', '  [ ] c3', '', '', '3 posts'],
            $harness->lines(),
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function provideNarrowTerminals(): iterable
    {
        foreach ([7, 6, 5, 4, 3, 2, 1] as $columns) {
            yield $columns . ' columns' => [$columns, false];
            yield $columns . ' columns, with the marks' => [$columns, true];
        }
    }

    #[DataProvider('provideNarrowTerminals')]
    public function testItIsDisplayedOnATerminalNarrowerThanAColumn(int $columns, bool $marked): void
    {
        $harness = new TuiHarness($columns, 6);
        $harness->mount($table = $this->posts()->setFooter('3 posts — page 1/1')->setMarked($marked ? [0] : null));
        $harness->keys(Keys::DOWN, Keys::END, Keys::HOME, Keys::DOWN);

        $lines = $harness->lines();
        self::assertSame(1, $table->selected());
        self::assertCount(6, $lines);
        self::assertSame('>', substr($lines[3], 0, 1));
        foreach ($lines as $line) {
            self::assertLessThanOrEqual($columns, Ansi::width($line));
        }
    }

    public function testTheOnlyColumnIsCutToTheWidthOfTheTerminal(): void
    {
        $harness = new TuiHarness(4, 6);
        $harness->mount($this->posts());

        self::assertSame(['  id', '  ──', '> a1', '  b2', '  c3'], $harness->lines());

        $harness = new TuiHarness(3, 6);
        $harness->mount($this->posts());

        self::assertSame(['  …', '  ─', '> …', '  …', '  …'], $harness->lines());
    }

    public function testRenderingNeverThrowsWhateverTheSize(): void
    {
        $table = $this->posts()->setFooter('3 posts')->setMarked([1]);

        foreach ([0, 1, 2, 3, 5, 9, 40, 200] as $columns) {
            foreach ([0, 1, 2, 3, 4, 10] as $rows) {
                $lines = $table->render(new RenderContext($columns, $rows));

                self::assertLessThanOrEqual(max(1, $rows), count($lines));
                foreach ($lines as $line) {
                    self::assertLessThanOrEqual($columns, Ansi::width($line));
                }
            }
        }
    }

    public function testAWideCellIsCutByItsDisplayedWidth(): void
    {
        $harness = new TuiHarness(24, 6);
        $harness->mount((new TableWidget())->setRows(
            ['題名', 'name'],
            [['題名' => '日本語のタイトルです', 'name' => 'a'], ['題名' => 'これは', 'name' => 'b']],
        ));

        // 24 columns: 2 of marker, 16 for the first column, 2 of separator and 4 for the last column
        self::assertSame(
            [
                '  題名              name',
                '  ────────────────  ────',
                '> 日本語のタイト…   a',
                '  これは            b',
            ],
            $harness->lines(),
        );
    }

    public function testAColumnIsNeverWiderThanFortyColumns(): void
    {
        $harness = new TuiHarness(80, 6);
        $harness->mount((new TableWidget())->setRows(
            ['text', 'end'],
            [['text' => str_repeat('a', 60), 'end' => 'z'], ['text' => str_repeat('語', 30), 'end' => 'y']],
        ));

        self::assertSame(
            [
                '  text' . str_repeat(' ', 38) . 'end',
                '  ' . str_repeat('─', 40) . '  ────',
                '> ' . str_repeat('a', 39) . '…  z',
                '  ' . str_repeat('語', 19) . '…   y',
            ],
            $harness->lines(),
        );
    }

    public function testItFillsTheHeightByDefault(): void
    {
        $table = $this->posts()->setFooter('3 posts');

        self::assertTrue($table->isVerticallyExpanded());
        self::assertSame(
            [
                '  id    title                   author',
                '  ────  ──────────────────────  ──────',
                '> a1    First post of the blog  Ann',
                '  b2    日本語のタイトルです    Bob',
                '  c3    Third                   Cid',
                '',
                '',
                '3 posts',
            ],
            self::plain($table->render(new RenderContext(40, 8))),
        );
    }

    public function testItIsNotHigherThanItsRowsWhenItIsNotExpanded(): void
    {
        $harness = new TuiHarness(40, 8);
        $harness->mount($table = $this->posts()->setFooter('3 posts'));
        self::assertSame($table, $table->expandVertically(false));
        $harness->render();

        self::assertFalse($table->isVerticallyExpanded());
        self::assertSame(
            [
                '  id    title                   author',
                '  ────  ──────────────────────  ──────',
                '> a1    First post of the blog  Ann',
                '  b2    日本語のタイトルです    Bob',
                '  c3    Third                   Cid',
                '3 posts',
            ],
            $harness->lines(),
        );
        self::assertCount(6, $table->render(new RenderContext(40, 8)));
        self::assertCount(5, $table->setFooter('')->render(new RenderContext(40, 8)));

        self::assertTrue($table->setFooter('3 posts')->expandVertically(true)->isVerticallyExpanded());
        self::assertCount(8, $table->render(new RenderContext(40, 8)));
    }

    public function testItNeverDrawsMoreLinesThanItsHeight(): void
    {
        $table = $this->numbered(20)->setFooter('20 rows');

        self::assertSame(['  name', '  ──────', '> row 1', '20 rows'], self::plain($table->render(new RenderContext(40, 4))));

        // Not enough lines for a row: the footer is still the last line, a height of zero is drawn as one line
        foreach ([3 => 3, 2 => 2, 1 => 1, 0 => 1] as $height => $expected) {
            $lines = self::plain($table->render(new RenderContext(40, $height)));

            self::assertCount($expected, $lines);
            self::assertSame('20 rows', array_pop($lines));
        }

        $table->setFooter('')->expandVertically(false);
        self::assertSame(['  name', '  ──────', '> row 1'], self::plain($table->render(new RenderContext(40, 4))));
        foreach ([3, 2, 1, 0] as $height) {
            self::assertLessThanOrEqual(max(1, $height), count($table->render(new RenderContext(40, $height))));
        }
    }

    public function testAnExpandedTableWithoutFooterFillsItsHeight(): void
    {
        $table = (new TableWidget())->setRows(['id'], [['id' => 'a']]);

        self::assertCount(6, $table->render(new RenderContext(20, 6)));
        self::assertCount(3, $table->expandVertically(false)->render(new RenderContext(20, 6)));
    }
}
