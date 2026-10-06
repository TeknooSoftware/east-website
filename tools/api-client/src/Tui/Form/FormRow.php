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

use Teknoo\East\Website\Tools\Resource\FieldDefinition;

use function array_map;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function sprintf;
use function str_starts_with;
use function substr;

/**
 * A row of a form: a field of the resource, a block of a content ("block_<name>"), its publication ("publish") or
 * an information. It keeps its initial value, to send only what was changed.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FormRow
{
    public const string PART_PREFIX = 'block_';

    public const string PUBLISH = 'publish';

    public mixed $initial;

    public ?string $error = null;

    /**
     * @var bool true when the value was given on the command line: it is sent even when it is the initial one
     */
    public bool $forced = false;

    /**
     * @var array{display: mixed, raw: mixed}|null the value given on the command line, as the row shows it and as
     *                                              it was given
     */
    private ?array $given = null;

    /**
     * @param mixed $value its type depends on the kind, see RowKind
     * @param list<string> $choices
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly RowKind $kind,
        public mixed $value,
        public readonly ?FieldDefinition $field = null,
        public readonly array $choices = [],
        public readonly string $hint = '',
    ) {
        $this->initial = $value;
    }

    public function isEditable(): bool
    {
        return RowKind::ReadOnly !== $this->kind;
    }

    public function isPart(): bool
    {
        return str_starts_with($this->name, self::PART_PREFIX);
    }

    /**
     * @return string name of the block of a row "block_<name>"
     */
    public function partName(): string
    {
        return substr($this->name, 6);
    }

    public function isChanged(): bool
    {
        return $this->value !== $this->initial;
    }

    public function mustBeSent(): bool
    {
        return $this->isEditable() && ($this->forced || $this->isChanged());
    }

    /**
     * Sets the value given on the command line. A row shows a text without control characters, a relation its id:
     * what was given is kept, to be sent as it is while the row is not changed, like the command does without the
     * interactive mode.
     *
     * @param mixed $value value of the row, its type depends on the kind
     * @param mixed $raw value of the body built from the command line
     */
    public function force(mixed $value, mixed $raw): void
    {
        $this->value = $value;
        $this->forced = true;
        $this->given = ['display' => $value, 'raw' => $raw];
    }

    /**
     * @return array{mixed}|null the value given on the command line when the row still shows it, null otherwise
     */
    public function given(): ?array
    {
        return $this->forced && null !== $this->given && $this->value === $this->given['display']
            ? [$this->given['raw']]
            : null;
    }

    /**
     * The current value becomes the initial one, after it was saved.
     */
    public function commit(): void
    {
        $this->initial = $this->value;
        $this->forced = false;
        $this->given = null;
    }

    /**
     * @return string the value on a single line
     */
    public function text(): string
    {
        $value = $this->value;

        return match ($this->kind) {
            RowKind::Bool => true === $value ? '[x]' : '[ ]',
            RowKind::Choices => implode(', ', $this->strings()),
            RowKind::Relation => is_array($value) ? self::reference($value) : '(none)',
            RowKind::RelationList => is_array($value) && [] !== $value
                ? implode(', ', array_map(self::reference(...), $value))
                : '(none)',
            RowKind::Multiline, RowKind::ReadOnly => $this->summary(),
            default => is_scalar($value) && !is_bool($value) ? (string) $value : '',
        };
    }

    /**
     * @return list<string> the values of a row with choices
     */
    public function strings(): array
    {
        $values = [];
        foreach (is_array($this->value) ? $this->value : [] as $value) {
            if (is_string($value)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @return list<string> the ids of a relation, of one or of several objects
     */
    public function ids(): array
    {
        $value = $this->value;
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach (RowKind::Relation === $this->kind ? [$value] : $value as $item) {
            if (is_array($item) && isset($item['id']) && is_string($item['id'])) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }

    private function summary(): string
    {
        $text = is_string($this->value) ? $this->value : '';
        if ('' === $text) {
            return '';
        }

        $lines = explode("\n", $text);

        return 1 === count($lines) ? $lines[0] : sprintf('%s … (%d lines)', $lines[0], count($lines));
    }

    private static function reference(mixed $item): string
    {
        $id = is_array($item) && is_string($item['id'] ?? null) ? $item['id'] : '';
        $label = is_array($item) && is_string($item['label'] ?? null) ? $item['label'] : '';

        return '' === $label || $label === $id ? $id : sprintf('%s (%s)', $label, $id);
    }
}
