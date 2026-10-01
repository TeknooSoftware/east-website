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

/**
 * Credentials used to reach the API: a username (<keyName>:<email>) with its API key, or a JWT already available.
 * The secrets are never exposed by debug dumps.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Credentials
{
    public function __construct(
        public readonly ?string $username = null,
        private readonly ?string $apiKey = null,
        public readonly ?string $token = null,
    ) {
    }

    public function apiKey(): ?string
    {
        return $this->apiKey;
    }

    public function withUsername(string $username): self
    {
        return new self($username, $this->apiKey, $this->token);
    }

    public function canLogin(): bool
    {
        return null !== $this->username && '' !== $this->username && null !== $this->apiKey && '' !== $this->apiKey;
    }

    /**
     * @return array<string, string|null>
     */
    public function __debugInfo(): array
    {
        return [
            'username' => $this->username,
            'apiKey' => null !== $this->apiKey ? '***' : null,
            'token' => null !== $this->token ? '***' : null,
        ];
    }
}
