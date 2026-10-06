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

namespace Teknoo\East\Website\Tools\Config;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Credentials stored in the configuration file by the login: the username (<keyName>:<email>) with its API key, to
 * login again when the JWT expires, and the last JWT with its expiration. The secrets are never exposed by debug
 * dumps.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Credentials
{
    /**
     * Minimal remaining validity, in seconds, to reuse a JWT.
     */
    public const int LEEWAY = 30;

    public function __construct(
        public readonly ?string $username = null,
        private readonly ?string $apiKey = null,
        public readonly ?string $token = null,
        public readonly ?int $expiresAt = null,
    ) {
    }

    public function apiKey(): ?string
    {
        return $this->apiKey;
    }

    public function withToken(string $token, ?int $expiresAt): self
    {
        return new self($this->username, $this->apiKey, $token, $expiresAt);
    }

    public function canLogin(): bool
    {
        return null !== $this->username && '' !== $this->username && null !== $this->apiKey && '' !== $this->apiKey;
    }

    public function hasToken(): bool
    {
        return null !== $this->token && '' !== $this->token;
    }

    /**
     * A JWT is reused when it is valid for more than the leeway, or when it has no expiration.
     */
    public function isValidAt(int $now): bool
    {
        return $this->hasToken() && (null === $this->expiresAt || $this->expiresAt > $now + self::LEEWAY);
    }

    public function expirationDate(): ?string
    {
        if (null === $this->expiresAt) {
            return null;
        }

        return (new DateTimeImmutable('@' . $this->expiresAt))->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array<string, string|int|null>
     */
    public function __debugInfo(): array
    {
        return [
            'username' => $this->username,
            'apiKey' => null !== $this->apiKey ? '***' : null,
            'token' => null !== $this->token ? '***' : null,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
