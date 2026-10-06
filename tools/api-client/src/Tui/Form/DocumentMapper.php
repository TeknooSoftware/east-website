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

namespace Teknoo\East\Website\Tools\Tui\Form;

use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Resource\BlockTypes;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\Screen\FormMode;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;

use function array_key_exists;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * Builds the rows of a form from a document of the API. The documents nest the related objects (an author, a
 * type, tags), the forms keep their id and a label; the blocks of a type are edited as texts "<name>:<kind>", like
 * with the option of the command line.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DocumentMapper
{
    private const array HIDDEN_KEYS = ['id', '@class', 'parts'];

    private const array MULTILINE_BLOCKS = ['textarea', 'raw'];

    public function __construct(
        private readonly Registry $registry,
    ) {
    }

    /**
     * @param array<mixed> $data document of the object, empty for a creation
     * @param list<array{name: string, kind: string}> $blocks blocks of the type of a content
     */
    public function state(
        ResourceDefinition $definition,
        array $data,
        FormMode $mode,
        array $blocks = [],
        ?Payload $prefill = null,
    ): FormState {
        $state = new FormState($this->rows($definition, $data, $mode, $blocks));
        if (null !== $prefill) {
            $this->overlay($state, $prefill);
        }

        return $state;
    }

    /**
     * @param array<mixed> $data
     * @param list<array{name: string, kind: string}> $blocks
     * @return list<FormRow>
     */
    public function rows(ResourceDefinition $definition, array $data, FormMode $mode, array $blocks = []): array
    {
        $rows = [];
        if (FormMode::Create !== $mode && array_key_exists('id', $data)) {
            $rows[] = new FormRow('id', 'id', RowKind::ReadOnly, CellFormatter::cell($data['id']));
        }

        $parts = is_array($data['parts'] ?? null) ? $data['parts'] : [];

        if (FormMode::View === $mode) {
            $fields = [];
            foreach ($definition->fields as $field) {
                $fields[] = $field->name;
                if (array_key_exists($field->name, $data)) {
                    $rows[] = $this->information($field->name, $data[$field->name], $field);
                }
            }

            foreach ($data as $key => $value) {
                if (!in_array($key, $fields, true) && !in_array($key, self::HIDDEN_KEYS, true)) {
                    $rows[] = $this->information((string) $key, $value);
                }
            }

            foreach ($parts as $name => $value) {
                $rows[] = $this->information('block ' . $name, $value);
            }

            return $rows;
        }

        foreach ($definition->fields as $field) {
            $rows[] = $this->editable($field, $data[$field->name] ?? null);
        }

        if (!$definition->hasParts) {
            return $rows;
        }

        foreach ($blocks as $block) {
            $value = $parts[$block['name']] ?? null;
            $multiline = in_array($block['kind'], self::MULTILINE_BLOCKS, true);

            $rows[] = new FormRow(
                FormRow::PART_PREFIX . $block['name'],
                sprintf('%s (%s)', $block['name'], $block['kind']),
                $multiline ? RowKind::Multiline : RowKind::Text,
                is_scalar($value) && !is_bool($value) ? CellFormatter::clean((string) $value, $multiline) : '',
                hint: 'Block of the content',
            );
        }

        $published = $data['publishedAt'] ?? null;
        $rows[] = new FormRow(
            FormRow::PUBLISH,
            'publish',
            RowKind::Bool,
            false,
            hint: is_string($published) && '' !== $published
                ? sprintf('Publish the content again (published at %s)', CellFormatter::clean($published))
                : 'Publish the content now',
        );

        return $rows;
    }

    /**
     * Applies on the rows what was given on the command line (the typed options, --data, --part, --publish): they
     * are sent even when they are not changed in the form. What has no row is kept to be sent as it is.
     */
    public function overlay(FormState $state, Payload $prefill): void
    {
        $extraFields = [];
        foreach ($prefill->fields as $name => $value) {
            // The numeric keys of a JSON object are integers for PHP
            $row = $state->row((string) $name);
            if (null === $row || null === $row->field) {
                $extraFields[$name] = $value;

                continue;
            }

            if (!$row->forced) {
                $row->force($this->fromPayload($row, $value), $value);
            }
        }

        $extraParts = [];
        foreach ($prefill->parts as $name => $value) {
            $row = $state->row(FormRow::PART_PREFIX . $name);
            if (null === $row) {
                $extraParts[$name] = $value;

                continue;
            }

            if (!$row->forced) {
                $row->force(
                    is_scalar($value) && !is_bool($value)
                        ? CellFormatter::clean((string) $value, RowKind::Multiline === $row->kind)
                        : '',
                    $value,
                );
            }
        }

        $publish = $state->row(FormRow::PUBLISH);
        if ($prefill->publish && null !== $publish && !$publish->forced) {
            $publish->force(true, true);
        }

        $state->setExtra($extraFields, $extraParts);
    }

    /**
     * @param array<mixed> $type document of a type of content
     * @return list<array{name: string, kind: string}>
     */
    public function blocks(array $type): array
    {
        $blocks = [];
        foreach (is_array($type['blocks'] ?? null) ? $type['blocks'] : [] as $block) {
            $name = is_array($block) ? ($block['name'] ?? null) : null;
            if (!is_array($block) || !is_string($name) || '' === $name) {
                continue;
            }

            $blocks[] = ['name' => CellFormatter::clean($name), 'kind' => $this->kind($block['type'] ?? null)];
        }

        return $blocks;
    }

    /**
     * @return array{id: string, label: string}|null the related object of a document, as a form keeps it
     */
    public function reference(mixed $value, ?string $target = null): ?array
    {
        if (is_scalar($value) && !is_bool($value) && '' !== (string) $value) {
            return ['id' => (string) $value, 'label' => (string) $value];
        }

        $id = is_array($value) ? ($value['id'] ?? null) : null;
        if (!is_array($value) || !is_scalar($id) || is_bool($id) || '' === (string) $id) {
            return null;
        }

        $labelField = null !== $target ? $this->registry->resource($target)?->labelField : null;

        return ['id' => (string) $id, 'label' => CellFormatter::label($value, $labelField) ?? (string) $id];
    }

    private function information(string $label, mixed $value, ?FieldDefinition $field = null): FormRow
    {
        $text = match (true) {
            FieldKind::Blocks === $field?->kind => $this->blockLines($value),
            is_string($value) => CellFormatter::clean($value, true),
            default => CellFormatter::cell($value),
        };

        return new FormRow($label, $label, RowKind::ReadOnly, $text);
    }

    private function editable(FieldDefinition $field, mixed $value): FormRow
    {
        $text = is_scalar($value) && !is_bool($value) ? (string) $value : '';

        return match ($field->kind) {
            FieldKind::Bool => new FormRow($field->name, $field->name, RowKind::Bool, true === $value, $field),
            FieldKind::Id => new FormRow(
                $field->name,
                $field->name,
                null !== $field->target ? RowKind::Relation : RowKind::Text,
                null !== $field->target ? $this->reference($value, $field->target) : $text,
                $field,
                hint: null !== $field->target ? 'Enter: choose · Backspace: none' : $field->description,
            ),
            FieldKind::IdList => new FormRow(
                $field->name,
                $field->name,
                null !== $field->target ? RowKind::RelationList : RowKind::Multiline,
                null !== $field->target ? $this->references($value, $field->target) : $this->lines($value),
                $field,
                hint: null !== $field->target ? 'Enter: choose · Backspace: none' : 'One id by line',
            ),
            FieldKind::StringList => [] !== $field->choices
                ? new FormRow(
                    $field->name,
                    $field->name,
                    RowKind::Choices,
                    $this->strings($value),
                    $field,
                    $field->choices,
                    'Left, Right: move · Space: check',
                )
                : new FormRow(
                    $field->name,
                    $field->name,
                    RowKind::Multiline,
                    $this->lines($value),
                    $field,
                    hint: 'One value by line',
                ),
            FieldKind::Blocks => new FormRow(
                $field->name,
                $field->name,
                RowKind::Multiline,
                $this->blockLines($value),
                $field,
                hint: 'One block by line, as <name>:<kind> with kind in ' . implode('|', BlockTypes::kinds()),
            ),
            default => new FormRow(
                $field->name,
                $field->name,
                $field->multiline ? RowKind::Multiline : RowKind::Text,
                CellFormatter::clean($text, $field->multiline),
                $field,
                hint: $field->description,
            ),
        };
    }

    /**
     * Value of a row from the value of the body built from the command line.
     */
    private function fromPayload(FormRow $row, mixed $value): mixed
    {
        $target = $row->field?->target;

        return match ($row->kind) {
            RowKind::Bool => true === $value,
            RowKind::Choices => $this->strings(is_array($value) ? $value : [$value]),
            RowKind::Relation => $this->reference($value, $target),
            RowKind::RelationList => $this->references(is_array($value) ? $value : [$value], $target),
            RowKind::Multiline => match (true) {
                FieldKind::Blocks === $row->field?->kind => $this->blockLines($value),
                is_array($value) => $this->lines($value),
                default => is_scalar($value) && !is_bool($value) ? CellFormatter::clean((string) $value, true) : '',
            },
            default => is_scalar($value) && !is_bool($value) ? CellFormatter::clean((string) $value) : '',
        };
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function references(mixed $values, ?string $target): array
    {
        $references = [];
        foreach (is_array($values) ? $values : [] as $value) {
            $reference = $this->reference($value, $target);
            if (null !== $reference) {
                $references[] = $reference;
            }
        }

        return $references;
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        $strings = [];
        foreach (is_array($values) ? $values : [] as $value) {
            if (is_string($value) && '' !== $value) {
                $strings[] = CellFormatter::clean($value);
            }
        }

        return $strings;
    }

    private function lines(mixed $values): string
    {
        return implode("\n", $this->strings($values));
    }

    /**
     * The blocks of a type as lines "<name>:<kind>".
     */
    private function blockLines(mixed $blocks): string
    {
        $lines = [];
        foreach ($this->blocks(['blocks' => $blocks]) as $block) {
            $lines[] = $block['name'] . ':' . $block['kind'];
        }

        return implode("\n", $lines);
    }

    /**
     * The documents give the kind of a block, the body of a request its index.
     */
    private function kind(mixed $type): string
    {
        $type = is_scalar($type) && !is_bool($type) ? (string) $type : '';

        return in_array($type, BlockTypes::kinds(), true) ? $type : (BlockTypes::kind($type) ?? 'text');
    }
}
