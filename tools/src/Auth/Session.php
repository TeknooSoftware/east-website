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

namespace Teknoo\East\Website\Tools\Auth;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * JWT obtained for an API, with the information needed to reuse it. The API key is never part of a session.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Session
{
    /**
     * Minimal remaining validity, in seconds, to reuse a session.
     */
    public const int LEEWAY = 30;

    public function __construct(
        public readonly string $baseUrl,
        public readonly string $username,
        public readonly string $token,
        public readonly ?int $expiresAt,
    ) {
    }

    public function matches(string $baseUrl, ?string $username): bool
    {
        return $this->baseUrl === $baseUrl && (null === $username || '' === $username || $this->username === $username);
    }

    public function isValidAt(int $now): bool
    {
        return null === $this->expiresAt || $this->expiresAt > $now + self::LEEWAY;
    }

    public function expirationDate(): ?string
    {
        if (null === $this->expiresAt) {
            return null;
        }

        return (new DateTimeImmutable('@' . $this->expiresAt))->format(DateTimeInterface::ATOM);
    }
}
