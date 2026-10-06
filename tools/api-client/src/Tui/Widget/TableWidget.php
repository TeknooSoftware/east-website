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

namespace Teknoo\East\Website\Tools\Tui\Widget;

use Symfony\Component\Tui\Input\Key;
use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Symfony\Component\Tui\Widget\FocusableTrait;
use Symfony\Component\Tui\Widget\KeybindingsTrait;
use Symfony\Component\Tui\Widget\VerticallyExpandableInterface;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;

use function array_key_last;
use function array_pop;
use function array_slice;
use function array_sum;
use function count;
use function implode;
use function in_array;
use function intdiv;
use function max;
use function min;
use function str_repeat;

/**
 * Table with a selected row, for the TUI component which has none. It knows nothing about the API: the cells are
 * texts, already cleaned. It never draws more lines than the height it gets, because the component has no scrolling
 * container: the rows are windowed around the selection, and the columns are shrunk then dropped to fit the width.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TableWidget extends AbstractWidget implements FocusableInterface, VerticallyExpandableInterface
{
    use FocusableTrait;
    use KeybindingsTrait;

    private const int MAX_COLUMN_WIDTH = 40;

    private const int MIN_COLUMN_WIDTH = 4;

    private const string SEPARATOR = '  ';

    /**
     * @var list<string>
     */
    private array $columns = [];

    /**
     * @var list<array<string, string>>
     */
    private array $rows = [];

    /**
     * @var list<int>|null indexes of the marked rows, null when the rows can not be marked
     */
    private ?array $marked = null;

    private int $selected = 0;

    private int $capacity = 1;

    private string $footer = '';

    private string $emptyMessage = 'Nothing to display';

    private bool $expanded = true;

    /**
     * @var (callable(TableAction, int|null): void)|null
     */
    private $onAction = null;

    /**
     * @param list<string> $columns
     * @param list<array<string, string>> $rows cells indexed by the name of their column
     */
    public function setRows(array $columns, array $rows): static
    {
        $this->columns = $columns;
        $this->rows = $rows;
        $this->selected = max(0, min($this->selected, count($rows) - 1));
        $this->invalidate();

        return $this;
    }

    public function setFooter(string $footer): static
    {
        $this->footer = $footer;
        $this->invalidate();

        return $this;
    }

    public function setEmptyMessage(string $message): static
    {
        $this->emptyMessage = $message;
        $this->invalidate();

        return $this;
    }

    /**
     * @param list<int>|null $marked indexes of the marked rows, null to hide the marks
     */
    public function setMarked(?array $marked): static
    {
        $this->marked = $marked;
        $this->invalidate();

        return $this;
    }

    /**
     * @return int|null index of the selected row, null when the table is empty
     */
    public function selected(): ?int
    {
        return [] === $this->rows ? null : $this->selected;
    }

    public function select(int $index): static
    {
        $this->selected = max(0, min($index, count($this->rows) - 1));
        $this->invalidate();

        return $this;
    }

    /**
     * @param callable(TableAction, int|null): void $callback called with the action and the selected row
     */
    public function onAction(callable $callback): static
    {
        $this->onAction = $callback;

        return $this;
    }

    public function expandVertically(bool $expand): static
    {
        $this->expanded = $expand;
        $this->invalidate();

        return $this;
    }

    public function isVerticallyExpanded(): bool
    {
        return $this->expanded;
    }

    public function handleInput(string $data): void
    {
        $keys = $this->getKeybindings();
        $last = count($this->rows) - 1;

        $target = match (true) {
            $keys->matches($data, 'table_up') => $this->selected - 1,
            $keys->matches($data, 'table_down') => $this->selected + 1,
            $keys->matches($data, 'table_page_up') => $this->selected - $this->capacity,
            $keys->matches($data, 'table_page_down') => $this->selected + $this->capacity,
            $keys->matches($data, 'table_first') => 0,
            $keys->matches($data, 'table_last') => $last,
            default => null,
        };

        if (null !== $target) {
            $this->select($target);

            return;
        }

        if (null === $this->onAction) {
            return;
        }

        foreach (TableAction::cases() as $action) {
            if ($keys->matches($data, $action->value)) {
                ($this->onAction)($action, $this->selected());

                return;
            }
        }
    }

    public function render(RenderContext $context): array
    {
        $width = $context->getColumns();
        $height = max(1, $context->getRows());
        $this->capacity = max(1, $height - 3);

        $lines = [];
        if ([] === $this->rows) {
            $lines[] = Ansi::dim(Ansi::fit($this->emptyMessage, $width));
        } else {
            $lines = $this->table($width);
        }

        $lines = array_slice($lines, 0, max(0, $height - 1));
        if ($this->expanded) {
            while (count($lines) < $height - 1) {
                $lines[] = '';
            }
        }

        if ('' !== $this->footer && count($lines) < $height) {
            $lines[] = Ansi::dim(Ansi::fit($this->footer, $width));
        } elseif ($this->expanded && count($lines) < $height) {
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @return array<string, string[]>
     */
    protected static function getDefaultKeybindings(): array
    {
        $bindings = [
            'table_up' => [Key::UP, 'k'],
            'table_down' => [Key::DOWN, 'j'],
            'table_page_up' => [Key::PAGE_UP],
            'table_page_down' => [Key::PAGE_DOWN],
            'table_first' => [Key::HOME],
            'table_last' => [Key::END],
        ];

        foreach (TableAction::cases() as $action) {
            $bindings[$action->value] = $action->keys();
        }

        return $bindings;
    }

    /**
     * @return list<string> the header, its rule and the visible rows
     */
    private function table(int $width): array
    {
        $prefix = null !== $this->marked ? 6 : 2;
        $widths = $this->widths(max(1, $width - $prefix));
        $columns = array_slice($this->columns, 0, count($widths));

        $titles = [];
        $rules = [];
        foreach ($columns as $index => $column) {
            $titles[] = Ansi::fit($column, $widths[$index], true);
            $rules[] = str_repeat('─', $widths[$index]);
        }

        $pad = str_repeat(' ', $prefix);
        $lines = [
            Ansi::clip(Ansi::bold($pad . implode(self::SEPARATOR, $titles)), $width),
            Ansi::clip(Ansi::dim($pad . implode(self::SEPARATOR, $rules)), $width),
        ];

        $first = max(0, min($this->selected - intdiv($this->capacity, 2), count($this->rows) - $this->capacity));
        foreach (array_slice($this->rows, $first, $this->capacity, true) as $index => $row) {
            $cells = [];
            foreach ($columns as $position => $column) {
                $cells[] = Ansi::fit($row[$column] ?? '', $widths[$position], true);
            }

            $mark = '';
            if (null !== $this->marked) {
                $mark = in_array($index, $this->marked, true) ? '[x] ' : '[ ] ';
            }

            $line = ($index === $this->selected ? '> ' : '  ') . $mark . implode(self::SEPARATOR, $cells);
            $lines[] = Ansi::clip($index === $this->selected ? Ansi::reverse($line) : $line, $width);
        }

        return $lines;
    }

    /**
     * Width of each displayed column: the widest ones are shrunk first, then the last ones are dropped.
     *
     * @return list<int>
     */
    private function widths(int $available): array
    {
        $widths = [];
        foreach ($this->columns as $column) {
            $natural = Ansi::width($column);
            foreach ($this->rows as $row) {
                $natural = max($natural, Ansi::width($row[$column] ?? ''));
            }

            $widths[] = max(self::MIN_COLUMN_WIDTH, min(self::MAX_COLUMN_WIDTH, $natural));
        }

        $separator = Ansi::width(self::SEPARATOR);
        while ([] !== $widths && array_sum($widths) + $separator * (count($widths) - 1) > $available) {
            $widest = 0;
            foreach ($widths as $index => $size) {
                if ($size > $widths[$widest]) {
                    $widest = $index;
                }
            }

            if ($widths[$widest] > self::MIN_COLUMN_WIDTH) {
                --$widths[$widest];
            } elseif (count($widths) > 1) {
                array_pop($widths);
            } else {
                $widths[array_key_last($widths)] = max(1, $available);

                break;
            }
        }

        return $widths;
    }
}
