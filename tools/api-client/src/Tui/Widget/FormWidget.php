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

use Symfony\Component\Tui\Ansi\TextWrapper;
use Symfony\Component\Tui\Input\Key;
use Symfony\Component\Tui\Input\Keybindings;
use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\EditorWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Symfony\Component\Tui\Widget\FocusableTrait;
use Symfony\Component\Tui\Widget\InputWidget;
use Symfony\Component\Tui\Widget\KeybindingsTrait;
use Symfony\Component\Tui\Widget\ParentInterface;
use Symfony\Component\Tui\Widget\VerticallyExpandableInterface;
use Teknoo\East\Website\Tools\Tui\Form\FormRow;
use Teknoo\East\Website\Tools\Tui\Form\RowKind;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;

use function array_filter;
use function array_slice;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_string;
use function max;
use function min;
use function str_repeat;

/**
 * Form of the interactive mode, for the TUI component which has none. It is a single focusable widget owning the
 * editors of its rows: the component has no scrolling container, so the form draws itself a window of its rows
 * around the active one, and never more lines than the height it gets. The editors are created with the form and
 * never added later, the component attaches the children of a widget only when it is added to the interface.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FormWidget extends AbstractWidget implements FocusableInterface, ParentInterface, VerticallyExpandableInterface
{
    use FocusableTrait {
        setFocused as private setOwnFocus;
    }
    use KeybindingsTrait;

    public const string ACTION_EDIT = 'form_edit';

    public const string ACTION_DELETE = 'form_delete';

    public const string ACTION_QUIT = 'form_quit';

    private const int LABEL_MAX_WIDTH = 24;

    private const int EDITOR_MAX_LINES = 10;

    /**
     * @var array<int, InputWidget|EditorWidget> editors of the rows edited as a text, by the index of their row
     */
    private array $editors = [];

    private int $active = 0;

    private int $offset = 0;

    private int $choice = 0;

    private int $height = 0;

    private int $labelWidth = 1;

    private bool $expanded = true;

    /**
     * @var (callable(): void)|null
     */
    private $onSubmit = null;

    /**
     * @var (callable(): void)|null
     */
    private $onCancel = null;

    /**
     * @var (callable(FormRow): void)|null
     */
    private $onPick = null;

    /**
     * @var (callable(FormRow): void)|null
     */
    private $onChange = null;

    /**
     * @var (callable(string): void)|null
     */
    private $onAction = null;

    /**
     * @param list<FormRow> $rows
     * @param bool $readOnly true to only display the rows: nothing can be changed, even the editable rows
     */
    public function __construct(
        private readonly array $rows,
        private readonly bool $readOnly = false,
    ) {
        foreach ($rows as $index => $row) {
            $this->labelWidth = max($this->labelWidth, min(self::LABEL_MAX_WIDTH, Ansi::width($row->label)));

            if ($readOnly || !is_string($row->value)) {
                continue;
            }

            if (RowKind::Text === $row->kind) {
                $this->editors[$index] = (new InputWidget())->setValue($row->value);
            } elseif (RowKind::Multiline === $row->kind) {
                // In a form, Enter adds a line: the form is submitted by its own key
                $this->editors[$index] = (new EditorWidget(
                    new Keybindings(['new_line' => [Key::ENTER], 'submit' => []]),
                ))->setText($row->value)->setMinVisibleLines(3);
            }
        }

        $this->active = $this->focusable()[0] ?? 0;
        $this->prompts();
    }

    /**
     * @return list<FormRow>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function activeRow(): ?FormRow
    {
        return $this->rows[$this->active] ?? null;
    }

    /**
     * Moves to a row, to show where an error is.
     */
    public function focusRow(string $name): static
    {
        foreach ($this->focusable() as $index) {
            if ($this->rows[$index]->name === $name) {
                $this->activate($index);

                break;
            }
        }

        return $this;
    }

    /**
     * Copies the texts of the editors in their rows. Already done after each key, it is a guard before a submit.
     */
    public function sync(): static
    {
        foreach ($this->editors as $index => $editor) {
            $row = $this->rows[$index];
            $row->value = $editor instanceof InputWidget ? $editor->getValue() : $editor->getText();
        }

        return $this;
    }

    /**
     * @param callable(): void $callback
     */
    public function onSubmit(callable $callback): static
    {
        $this->onSubmit = $callback;

        return $this;
    }

    /**
     * @param callable(): void $callback
     */
    public function onCancel(callable $callback): static
    {
        $this->onCancel = $callback;

        return $this;
    }

    /**
     * @param callable(FormRow): void $callback called to choose the objects of a relation
     */
    public function onPick(callable $callback): static
    {
        $this->onPick = $callback;

        return $this;
    }

    /**
     * @param callable(FormRow): void $callback called when the value of a row changes
     */
    public function onChange(callable $callback): static
    {
        $this->onChange = $callback;

        return $this;
    }

    /**
     * @param callable(string): void $callback called with an ACTION_* of a read only form
     */
    public function onAction(callable $callback): static
    {
        $this->onAction = $callback;

        return $this;
    }

    public function all(): array
    {
        return array_values($this->editors);
    }

    public function setFocused(bool $focused): static
    {
        $this->setOwnFocus($focused);
        $this->focusEditors();

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

        if ($keys->matches($data, 'form_cancel')) {
            if (null !== $this->onCancel) {
                ($this->onCancel)();
            }

            return;
        }

        if ($this->readOnly) {
            $this->handleReadOnly($keys, $data);

            return;
        }

        if ($keys->matches($data, 'form_submit')) {
            $this->sync();
            if (null !== $this->onSubmit) {
                ($this->onSubmit)();
            }

            return;
        }

        $row = $this->activeRow();
        $inText = RowKind::Multiline === $row?->kind;

        if ($keys->matches($data, 'form_next') || (!$inText && $keys->matches($data, 'form_down'))) {
            $this->move(1);

            return;
        }

        if ($keys->matches($data, 'form_previous') || (!$inText && $keys->matches($data, 'form_up'))) {
            $this->move(-1);

            return;
        }

        if (null !== $row && $row->isEditable()) {
            $this->edit($row, $keys, $data);
        }
    }

    public function render(RenderContext $context): array
    {
        $width = $context->getColumns();
        $height = max(1, $context->getRows());
        if ($height !== $this->height) {
            $this->height = $height;
            $this->sizeEditors();
        }

        $lines = [];
        $start = 0;
        $end = 0;
        foreach ($this->rows as $index => $row) {
            $block = $this->block($index, $row, $context, $width, $height);
            if ($index === $this->active) {
                $start = count($lines);
                $end = $start + count($block);
            }

            foreach ($block as $line) {
                $lines[] = Ansi::clip($line, $width);
            }
        }

        // The window follows the active row
        if ($end > $this->offset + $height) {
            $this->offset = $end - $height;
        }

        if ($start < $this->offset) {
            $this->offset = $start;
        }

        $this->offset = max(0, min($this->offset, max(0, count($lines) - $height)));

        $visible = array_slice($lines, $this->offset, $height);
        if ($this->expanded) {
            while (count($visible) < $height) {
                $visible[] = '';
            }
        }

        return $visible;
    }

    /**
     * @return array<string, string[]>
     */
    protected static function getDefaultKeybindings(): array
    {
        return [
            'form_next' => [Key::TAB],
            'form_previous' => ['shift+tab'],
            'form_down' => [Key::DOWN],
            'form_up' => [Key::UP],
            'form_enter' => [Key::ENTER],
            'form_toggle' => [Key::SPACE],
            'form_left' => [Key::LEFT],
            'form_right' => [Key::RIGHT],
            'form_clear' => [Key::BACKSPACE, Key::DELETE],
            'form_submit' => ['ctrl+s', Key::F2],
            'form_cancel' => [Key::ESCAPE],
            self::ACTION_EDIT => ['e'],
            self::ACTION_DELETE => ['d'],
            self::ACTION_QUIT => ['q'],
        ];
    }

    private function handleReadOnly(Keybindings $keys, string $data): void
    {
        if ($keys->matches($data, 'form_next') || $keys->matches($data, 'form_down')) {
            $this->move(1);

            return;
        }

        if ($keys->matches($data, 'form_previous') || $keys->matches($data, 'form_up')) {
            $this->move(-1);

            return;
        }

        foreach ([self::ACTION_EDIT, self::ACTION_DELETE, self::ACTION_QUIT] as $action) {
            if (null !== $this->onAction && $keys->matches($data, $action)) {
                ($this->onAction)($action);

                return;
            }
        }
    }

    private function edit(FormRow $row, Keybindings $keys, string $data): void
    {
        $editor = $this->editors[$this->active] ?? null;
        $enter = $keys->matches($data, 'form_enter');

        if (null !== $editor) {
            if ($enter && $editor instanceof InputWidget) {
                $this->move(1);

                return;
            }

            $editor->handleInput($data);
            $value = $editor instanceof InputWidget ? $editor->getValue() : $editor->getText();
            if ($value !== $row->value) {
                $row->value = $value;
                $this->changed($row);
            }

            $this->invalidate();

            return;
        }

        if (RowKind::Bool === $row->kind) {
            if ($enter || $keys->matches($data, 'form_toggle')) {
                $row->value = true !== $row->value;
                $this->changed($row);
            }

            return;
        }

        if (RowKind::Choices === $row->kind) {
            $this->editChoices($row, $keys, $data);

            return;
        }

        if ($enter) {
            if (null !== $this->onPick) {
                ($this->onPick)($row);
            }

            return;
        }

        if ($keys->matches($data, 'form_clear')) {
            $empty = RowKind::Relation === $row->kind ? null : [];
            if ($empty !== $row->value) {
                $row->value = $empty;
                $this->changed($row);
            }
        }
    }

    private function editChoices(FormRow $row, Keybindings $keys, string $data): void
    {
        $last = max(0, count($row->choices) - 1);

        if ($keys->matches($data, 'form_left')) {
            $this->choice = max(0, $this->choice - 1);
            $this->invalidate();
        } elseif ($keys->matches($data, 'form_right')) {
            $this->choice = min($last, $this->choice + 1);
            $this->invalidate();
        } elseif ($keys->matches($data, 'form_toggle') || $keys->matches($data, 'form_enter')) {
            $choice = $row->choices[$this->choice] ?? null;
            if (null === $choice) {
                return;
            }

            $values = $row->strings();
            $values = in_array($choice, $values, true)
                ? array_filter($values, static fn (string $value): bool => $value !== $choice)
                : [...$values, $choice];

            // Always in the order of the choices, so checking and unchecking gives back the initial value
            $row->value = array_values(array_filter(
                $row->choices,
                static fn (string $value): bool => in_array($value, $values, true),
            ));
            $this->changed($row);
        }
    }

    private function changed(FormRow $row): void
    {
        $this->invalidate();
        if (null !== $this->onChange) {
            ($this->onChange)($row);
        }
    }

    /**
     * @return list<int> indexes of the rows which can be active
     */
    private function focusable(): array
    {
        $indexes = [];
        foreach ($this->rows as $index => $row) {
            if ($this->readOnly || $row->isEditable()) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private function move(int $delta): void
    {
        $indexes = $this->focusable();
        $position = 0;
        foreach ($indexes as $key => $index) {
            if ($index === $this->active) {
                $position = $key;
            }
        }

        $target = $indexes[max(0, min(count($indexes) - 1, $position + $delta))] ?? null;
        if (null !== $target && $target !== $this->active) {
            $this->activate($target);
        }
    }

    private function activate(int $index): void
    {
        $this->active = $index;
        $this->choice = 0;
        $this->prompts();
        $this->focusEditors();
        $this->invalidate();
    }

    private function focusEditors(): void
    {
        foreach ($this->editors as $index => $editor) {
            $editor->setFocused($this->isFocused() && $index === $this->active);
        }
    }

    /**
     * The editor of a single line draws its whole row: its prompt is the marker of the active row and the label.
     */
    private function prompts(): void
    {
        foreach ($this->editors as $index => $editor) {
            if ($editor instanceof InputWidget) {
                $editor->setPrompt($this->prefix($index, $this->rows[$index]));
            }
        }
    }

    private function sizeEditors(): void
    {
        foreach ($this->editors as $editor) {
            if ($editor instanceof EditorWidget) {
                $editor->setMaxVisibleLines(max(3, min(self::EDITOR_MAX_LINES, $this->height - 4)));
            }
        }
    }

    private function prefix(int $index, FormRow $row): string
    {
        return ($index === $this->active ? '> ' : '  ') . Ansi::fit($row->label, $this->labelWidth, true) . '  ';
    }

    /**
     * @return list<string> the lines of a row: its value, then its hint when it is active and its error
     */
    private function block(int $index, FormRow $row, RenderContext $context, int $width, int $height): array
    {
        $active = $index === $this->active;
        $prefix = $this->prefix($index, $row);
        $valueWidth = max(1, $width - Ansi::width($prefix));
        $editor = $this->editors[$index] ?? null;
        $widgets = $this->getContext();

        $lines = match (true) {
            $editor instanceof InputWidget && null !== $widgets => $widgets->renderWidget(
                $editor,
                $context->withSize($width, 1),
            ),
            $editor instanceof EditorWidget && $active && null !== $widgets => [
                $prefix,
                ...$widgets->renderWidget($editor, $context->withSize($width, max(1, $height - 1))),
            ],
            RowKind::Choices === $row->kind => [$prefix . $this->choices($row, $active)],
            RowKind::ReadOnly === $row->kind => $this->information($row, $prefix, $valueWidth, $active, $height),
            default => [$prefix . Ansi::fit($row->text(), $valueWidth)],
        };

        if ($active && !$this->readOnly && '' !== $row->hint) {
            $lines[] = Ansi::dim(Ansi::fit('    ' . CellFormatter::clean($row->hint), $width));
        }

        if (null !== $row->error) {
            // The error can be a message of the server: it is never drawn raw
            $lines[] = Ansi::error(Ansi::fit('    ! ' . CellFormatter::clean($row->error), $width));
        }

        return array_values($lines);
    }

    private function choices(FormRow $row, bool $active): string
    {
        $checked = $row->strings();
        $cells = [];
        foreach ($row->choices as $position => $choice) {
            $cell = (in_array($choice, $checked, true) ? '[x] ' : '[ ] ') . $choice;
            $cells[] = $active && !$this->readOnly && $position === $this->choice ? Ansi::reverse($cell) : $cell;
        }

        return implode('  ', $cells);
    }

    /**
     * A value which can not be edited. Only the active row of a read only form shows a long text entirely.
     *
     * @return list<string>
     */
    private function information(FormRow $row, string $prefix, int $valueWidth, bool $active, int $height): array
    {
        $text = is_string($row->value) ? $row->value : '';
        if (!$active || !$this->readOnly) {
            return [$prefix . Ansi::dim(Ansi::fit($row->text(), $valueWidth))];
        }

        $wrapped = '' === $text
            ? ['']
            : array_slice(TextWrapper::wrapTextWithAnsi($text, $valueWidth), 0, max(1, $height - 1));
        $indent = str_repeat(' ', Ansi::width($prefix));

        $lines = [];
        foreach (array_values($wrapped) as $position => $line) {
            $lines[] = (0 === $position ? $prefix : $indent) . $line;
        }

        return $lines;
    }
}
