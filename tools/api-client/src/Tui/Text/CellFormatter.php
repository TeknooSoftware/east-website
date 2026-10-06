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

namespace Teknoo\East\Website\Tools\Tui\Text;

use Teknoo\East\Website\Tools\Http\Json;

use function array_is_list;
use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_scalar;
use function mb_scrub;
use function preg_replace;
use function str_replace;

/**
 * Text of the values of the API documents for the interactive mode. The content of the website is never sent raw
 * to the terminal: its control characters are removed, or a title could carry escape sequences.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CellFormatter
{
    private const array LABEL_KEYS = ['name', 'title', 'email', 'slug', 'id'];

    /**
     * Text of a value on a single line: a nested object is displayed by its label, a list by its labels.
     */
    public static function cell(mixed $value): string
    {
        return match (true) {
            null === $value => '',
            is_bool($value) => $value ? 'yes' : 'no',
            is_scalar($value) => self::clean((string) $value),
            is_array($value) => self::fromArray($value),
            default => '',
        };
    }

    /**
     * Label of a nested object (an author, a type, a tag...), null when it has none.
     *
     * @param array<mixed> $object
     */
    public static function label(array $object, ?string $labelField = null): ?string
    {
        foreach ([$labelField, ...self::LABEL_KEYS] as $key) {
            $value = null !== $key ? ($object[$key] ?? null) : null;
            if (is_scalar($value) && !is_bool($value) && '' !== (string) $value) {
                return self::clean((string) $value);
            }
        }

        return null;
    }

    /**
     * Removes the control characters (C0, C1, escape). The line breaks and the tabulations are kept as line breaks
     * and spaces for a multi-line text, replaced by spaces otherwise.
     */
    public static function clean(string $text, bool $multiline = false): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", mb_scrub($text, 'UTF-8'));
        $text = str_replace("\t", $multiline ? '    ' : ' ', $text);
        if (!$multiline) {
            $text = str_replace("\n", ' ', $text);
        }

        return (string) preg_replace('/[\x{00}-\x{09}\x{0B}-\x{1F}\x{7F}-\x{9F}]/u', '', $text);
    }

    /**
     * @param array<mixed> $value
     */
    private static function fromArray(array $value): string
    {
        if ([] === $value) {
            return '';
        }

        if (array_is_list($value)) {
            return implode(', ', array_map(self::cell(...), $value));
        }

        return self::label($value) ?? self::clean(Json::encode($value));
    }
}
