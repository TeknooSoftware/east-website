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

namespace Teknoo\East\Website\Tools\Output;

use Teknoo\East\Website\Tools\Http\ApiException;

use function implode;
use function sprintf;

/**
 * Output format of the commands: JSON is the default and the only one used by agents, the table is for humans.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
enum OutputFormat: string
{
    case Json = 'json';
    case Table = 'table';

    public static function fromOption(?string $value): self
    {
        if (null === $value) {
            return self::Json;
        }

        return self::tryFrom($value) ?? throw ApiException::usage(sprintf(
            'The format "%s" is not supported, use one of: %s',
            $value,
            implode(', ', array_map(static fn (self $format): string => $format->value, self::cases())),
        ));
    }
}
