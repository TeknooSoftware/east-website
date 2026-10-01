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

namespace Teknoo\East\Website\Tools\Http;

/**
 * Kinds of failures of the CLI, each one is mapped to a stable process exit code usable by scripts and agents.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
enum ErrorKind: string
{
    case Usage = 'usage';
    case Validation = 'validation';
    case Auth = 'auth';
    case NotFound = 'not_found';
    case Server = 'server';
    case Transport = 'transport';

    public function exitCode(): int
    {
        return match ($this) {
            self::Server, self::Transport => 1,
            self::Usage, self::Validation => 2,
            self::Auth => 3,
            self::NotFound => 4,
        };
    }

    public static function fromStatus(int $status): self
    {
        return match (true) {
            400 === $status, 422 === $status => self::Validation,
            401 === $status, 403 === $status => self::Auth,
            404 === $status => self::NotFound,
            default => self::Server,
        };
    }
}
