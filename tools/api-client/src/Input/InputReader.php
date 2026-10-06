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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Teknoo\East\Website\Tools\Http\ApiException;

use function fopen;
use function file_get_contents;
use function filter_var;
use function is_array;
use function is_bool;
use function is_file;
use function is_readable;
use function is_resource;
use function is_string;
use function sprintf;
use function stream_get_contents;

use const FILTER_VALIDATE_INT;

/**
 * Typed reading of the options and the arguments of the console input: the values of the Console component are
 * mixed, and "not provided" must stay distinguishable from an empty value, because a partial update must send
 * only what was provided.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class InputReader
{
    public static function string(InputInterface $input, string $name): ?string
    {
        if (!$input->hasOption($name)) {
            return null;
        }

        $value = $input->getOption($name);

        return is_string($value) ? $value : null;
    }

    public static function flag(InputInterface $input, string $name): bool
    {
        return $input->hasOption($name) && true === $input->getOption($name);
    }

    /**
     * Tri-state of a negatable option (--hidden / --no-hidden): null when not provided.
     */
    public static function bool(InputInterface $input, string $name): ?bool
    {
        if (!$input->hasOption($name)) {
            return null;
        }

        $value = $input->getOption($name);

        return is_bool($value) ? $value : null;
    }

    public static function int(InputInterface $input, string $name): ?int
    {
        $value = self::string($input, $name);
        if (null === $value) {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);
        if (false === $int) {
            throw ApiException::usage(sprintf('The option --%s expects an integer, "%s" given', $name, $value));
        }

        return $int;
    }

    /**
     * Values of a repeatable option, [] when not provided. The empty string is kept, it is the way to ask an
     * empty list.
     *
     * @return list<string>
     */
    public static function list(InputInterface $input, string $name): array
    {
        if (!$input->hasOption($name)) {
            return [];
        }

        $values = $input->getOption($name);
        $list = [];
        foreach (is_array($values) ? $values : [] as $value) {
            if (is_string($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    public static function argument(InputInterface $input, string $name): ?string
    {
        if (!$input->hasArgument($name)) {
            return null;
        }

        $value = $input->getArgument($name);

        return is_string($value) ? $value : null;
    }

    /**
     * Reads a file, or the standard input when the source is "-" (use the form --option=- on the command line,
     * because the Console component does not accept a value starting by a dash after a space).
     */
    public static function content(InputInterface $input, string $source): string
    {
        if ('-' === $source) {
            $stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
            $stream ??= fopen('php://stdin', 'r');
            $content = is_resource($stream) ? stream_get_contents($stream) : false;

            return false !== $content ? $content : '';
        }

        if (!is_file($source) || !is_readable($source)) {
            throw ApiException::usage(sprintf('The file "%s" does not exist or is not readable', $source));
        }

        $content = file_get_contents($source);
        if (false === $content) {
            throw ApiException::usage(sprintf('The file "%s" can not be read', $source));
        }

        return $content;
    }
}
