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

use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function ltrim;
use function sprintf;
use function trim;

/**
 * Rows of a form and the body to send from them. Like with the options of the commands, only what was changed (or
 * given on the command line) is sent: the API handles a body as a partial update.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FormState
{
    /**
     * @var array<string, mixed> fields of the command line (--data) without any row, sent as they are
     */
    private array $extraFields = [];

    /**
     * @var array<string, mixed> blocks of the command line unknown to the type, sent as they are
     */
    private array $extraParts = [];

    /**
     * @param list<FormRow> $rows
     */
    public function __construct(
        private array $rows,
    ) {
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $parts
     */
    public function setExtra(array $fields, array $parts): void
    {
        $this->extraFields = $fields;
        $this->extraParts = $parts;
    }

    /**
     * @return list<FormRow>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function row(string $name): ?FormRow
    {
        foreach ($this->rows as $row) {
            if ($row->name === $name) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Replaces the rows, the ones already known keep their value: the blocks follow the type of a content, the other
     * rows must not lose what was typed.
     *
     * @param list<FormRow> $rows
     */
    public function replace(array $rows): void
    {
        $merged = [];
        foreach ($rows as $row) {
            $merged[] = $this->row($row->name) ?? $row;
        }

        $this->rows = $merged;
    }

    public function isDirty(): bool
    {
        foreach ($this->rows as $row) {
            if ($row->isEditable() && $row->isChanged()) {
                return true;
            }
        }

        return false;
    }

    public function hasErrors(): bool
    {
        foreach ($this->rows as $row) {
            if (null !== $row->error) {
                return true;
            }
        }

        return false;
    }

    public function clearErrors(): void
    {
        foreach ($this->rows as $row) {
            $row->error = null;
        }
    }

    /**
     * The saved values become the initial ones. Only the fields when the blocks were not saved (a partial failure).
     */
    public function commit(bool $withParts = true): void
    {
        foreach ($this->rows as $row) {
            if ($withParts || (!$row->isPart() && FormRow::PUBLISH !== $row->name)) {
                $row->commit();
            }
        }

        $this->extraFields = [];
        if ($withParts) {
            $this->extraParts = [];
        }
    }

    /**
     * Body to send. A value refused by its field (a text for an integer...) is reported on its row.
     *
     * @return Payload|null null when a value is not valid, the errors are on the rows
     */
    public function payload(PayloadBuilder $builder): ?Payload
    {
        $fields = $this->extraFields;
        $parts = $this->extraParts;
        $publish = false;
        $valid = true;

        foreach ($this->rows as $row) {
            if (!$row->mustBeSent()) {
                continue;
            }

            // A value of the command line not changed in the form is sent as it was given
            $given = $row->given();

            if (FormRow::PUBLISH === $row->name) {
                $publish = true === $row->value;
            } elseif ($row->isPart()) {
                $parts[$row->partName()] = null !== $given ? $given[0] : (is_string($row->value) ? $row->value : '');
            } elseif (null !== $row->field && null !== $given) {
                $fields[$row->field->name] = $given[0];
            } elseif (null !== $row->field) {
                try {
                    $fields[$row->field->name] = $builder->cast($row->field, $this->raw($row));
                } catch (ApiException $error) {
                    $row->error = CellFormatter::clean($error->getMessage());
                    $valid = false;
                }
            }
        }

        return $valid ? new Payload($fields, $parts, $publish) : null;
    }

    /**
     * Reports the validation errors of the API on their rows. The keys are the paths of the form of the server:
     * ".title", ".blocks.0.type", ".block_intro", or "." for the whole object.
     *
     * @param array<mixed> $errors
     * @return list<string> the errors without any row
     */
    public function applyErrors(array $errors): array
    {
        $unmapped = [];
        foreach ($errors as $path => $message) {
            // A message of the server is never sent raw to the terminal
            $text = CellFormatter::clean(match (true) {
                is_scalar($message) => (string) $message,
                is_array($message) => implode(' ', array_filter($message, is_string(...))),
                default => '',
            });

            $name = CellFormatter::clean(explode('.', ltrim((string) $path, '.'), 2)[0]);
            $row = '' !== $name ? $this->row($name) : null;
            if (null === $row || !$row->isEditable()) {
                $unmapped[] = '' === $name ? $text : sprintf('%s: %s', $name, $text);

                continue;
            }

            $row->error = null === $row->error ? $text : $row->error . ' ' . $text;
        }

        return $unmapped;
    }

    /**
     * @return string|bool|list<string> the value of a row, like the same option of the command line gives it
     */
    private function raw(FormRow $row): string|bool|array
    {
        if (RowKind::Bool === $row->kind) {
            return true === $row->value;
        }

        if (RowKind::Choices === $row->kind) {
            return $row->strings();
        }

        if (RowKind::Relation === $row->kind) {
            return $row->ids()[0] ?? '';
        }

        if (RowKind::RelationList === $row->kind) {
            return $row->ids();
        }

        $text = is_string($row->value) ? $row->value : '';
        if (null !== $row->field && $row->field->kind->isList()) {
            // A list typed as a text: one value by line
            return array_values(array_filter(
                array_map(trim(...), explode("\n", $text)),
                static fn (string $line): bool => '' !== $line,
            ));
        }

        return FieldKind::Int === $row->field?->kind ? trim($text) : $text;
    }
}
