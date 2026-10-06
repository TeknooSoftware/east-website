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
use Teknoo\East\Website\Tools\Http\Json;

use function array_is_list;

/**
 * Raw JSON payload of a command (--data, --data-file), the typed options of the command are applied over it. It
 * is the escape hatch for agents, to send exactly the body they built.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DataSource
{
    public static function configure(Command $command): void
    {
        $command->addOption(
            'data',
            null,
            InputOption::VALUE_REQUIRED,
            'JSON object used as base of the payload, or --data=- to read it from stdin; typed options override it',
        );
        $command->addOption(
            'data-file',
            null,
            InputOption::VALUE_REQUIRED,
            'File containing the JSON object used as base of the payload',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function read(InputInterface $input): array
    {
        $inline = InputReader::string($input, 'data');
        $file = InputReader::string($input, 'data-file');
        if (null !== $inline && null !== $file) {
            throw ApiException::usage('The options --data and --data-file are mutually exclusive');
        }

        $raw = match (true) {
            null !== $file => InputReader::content($input, $file),
            '-' === $inline => InputReader::content($input, '-'),
            default => $inline,
        };

        if (null === $raw) {
            return [];
        }

        $decoded = Json::decode($raw);
        if (null === $decoded || ([] !== $decoded && array_is_list($decoded))) {
            throw ApiException::usage('The payload must be a valid JSON object');
        }

        $payload = [];
        foreach ($decoded as $key => $value) {
            $payload[(string) $key] = $value;
        }

        return $payload;
    }
}
