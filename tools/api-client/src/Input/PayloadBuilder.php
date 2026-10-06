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

namespace Teknoo\East\Website\Tools\Input;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Resource\BlockTypes;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;

use function count;
use function explode;
use function filter_var;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strrpos;
use function substr;

use const FILTER_VALIDATE_INT;

/**
 * Builds the body of a creation or of an update from the raw JSON (--data) and from the typed options. Only the
 * options really provided are sent, because the API handles a JSON body as a partial update.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PayloadBuilder
{
    public function configure(Command $command, ResourceDefinition $definition): void
    {
        foreach ($definition->fields as $field) {
            $command->getDefinition()->addOption($field->inputOption());
        }

        DataSource::configure($command);

        if ($definition->hasParts) {
            $command->addOption(
                'part',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Value of a block of the content, as <name>=<value> (repeatable)',
            );
            $command->addOption(
                'part-file',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Value of a block of the content read from a file, as <name>=<path> (repeatable)',
            );
            $command->addOption('publish', null, InputOption::VALUE_NONE, 'Publish the content now');
        }
    }

    public function build(ResourceDefinition $definition, InputInterface $input): Payload
    {
        $fields = [];
        $parts = [];
        $publish = false;

        foreach (DataSource::read($input) as $key => $value) {
            // The numeric keys of a JSON object are integers for PHP
            $key = (string) $key;
            if ($definition->hasParts && str_starts_with($key, 'block_')) {
                $parts[substr($key, 6)] = $value;
            } elseif ($definition->hasParts && 'publish' === $key) {
                $publish = true === $value;
            } else {
                $fields[$key] = $value;
            }
        }

        foreach ($definition->fields as $field) {
            $value = $this->value($field, $input);
            if (null !== $value) {
                $fields[$field->name] = $value['value'];
            }
        }

        if ($definition->hasParts) {
            foreach (InputReader::list($input, 'part') as $part) {
                [$name, $value] = $this->pair($part, '=', 'part');
                $parts[$name] = $value;
            }

            foreach (InputReader::list($input, 'part-file') as $part) {
                [$name, $path] = $this->pair($part, '=', 'part-file');
                $parts[$name] = InputReader::content($input, $path);
            }

            $publish = InputReader::flag($input, 'publish') || $publish;
        }

        return new Payload($fields, $parts, $publish);
    }

    /**
     * Types a raw value like the API expects it, whatever its source (an option of the command line or a field of
     * the interactive form).
     *
     * @param string|bool|int|array<mixed>|null $raw
     * @throws ApiException when the value is not valid for the field
     */
    public function cast(FieldDefinition $field, string|bool|int|array|null $raw): mixed
    {
        if ($field->kind->isList()) {
            $values = [];
            foreach (is_array($raw) ? $raw : [] as $value) {
                if (is_string($value) && '' !== $value) {
                    $values[] = $value;
                }
            }

            return $this->listValue($field, $values);
        }

        if (is_array($raw)) {
            throw ApiException::usage(sprintf('The option --%s expects a single value', $field->optionName()));
        }

        switch ($field->kind) {
            case FieldKind::Bool:
                return true === $raw;
            case FieldKind::Int:
                if (is_int($raw)) {
                    return $raw;
                }

                $int = filter_var($raw, FILTER_VALIDATE_INT);
                if (false === $int) {
                    throw ApiException::usage(sprintf(
                        'The option --%s expects an integer, "%s" given',
                        $field->optionName(),
                        (string) $raw,
                    ));
                }

                return $int;
            case FieldKind::Id:
                return null === $raw || '' === $raw ? null : (string) $raw;
            default:
                return (string) $raw;
        }
    }

    /**
     * @return array{value: mixed}|null null when the option was not provided, the value can be null (JSON null)
     */
    private function value(FieldDefinition $field, InputInterface $input): ?array
    {
        $option = $field->optionName();

        if ($field->kind->isList()) {
            $values = InputReader::list($input, $option);

            return [] === $values ? null : ['value' => $this->cast($field, $values)];
        }

        $raw = FieldKind::Bool === $field->kind
            ? InputReader::bool($input, $option)
            : InputReader::string($input, $option);

        return null === $raw ? null : ['value' => $this->cast($field, $raw)];
    }

    /**
     * @param list<string> $values
     * @return list<mixed>
     */
    private function listValue(FieldDefinition $field, array $values): array
    {
        if (FieldKind::Blocks === $field->kind) {
            $blocks = [];
            foreach ($values as $value) {
                $position = strrpos($value, ':');
                $name = false !== $position ? substr($value, 0, $position) : '';
                $kind = false !== $position ? substr($value, $position + 1) : '';
                $index = BlockTypes::index($kind);
                if ('' === $name || null === $index) {
                    throw ApiException::usage(sprintf(
                        'The option --%s expects <name>:<kind> with kind in %s, "%s" given',
                        $field->optionName(),
                        implode('|', BlockTypes::kinds()),
                        $value,
                    ));
                }

                $blocks[] = ['name' => $name, 'type' => $index];
            }

            return $blocks;
        }

        foreach ($values as $value) {
            if ([] !== $field->choices && !in_array($value, $field->choices, true)) {
                throw ApiException::usage(sprintf(
                    'The option --%s expects one of %s, "%s" given',
                    $field->optionName(),
                    implode(', ', $field->choices),
                    $value,
                ));
            }
        }

        return $values;
    }

    /**
     * @param non-empty-string $separator
     * @return array{string, string}
     */
    private function pair(string $value, string $separator, string $option): array
    {
        $parts = explode($separator, $value, 2);
        if (2 !== count($parts) || '' === $parts[0]) {
            throw ApiException::usage(
                sprintf('The option --%s expects <name>%s<value>, "%s" given', $option, $separator, $value)
            );
        }

        return [$parts[0], $parts[1]];
    }
}
