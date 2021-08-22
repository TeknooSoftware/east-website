<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Object;

use RuntimeException;
use Teknoo\East\Website\Contracts\User\AuthDataInterface;
use Teknoo\East\Website\Contracts\User\AuthenticatorInterface;
use Teknoo\East\Website\Contracts\User\UserInterface;

use function is_a;

/**
 * Class to defined persisted user's password to authenticate it on a website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class StoredPassword implements AuthDataInterface
{
    public function __construct(
        private ?string $algo = '',
        private ?string $password = null,
        private ?string $originalPassword = null,
        private string $salt = '',
    ) {
    }

    public function getAlgo(): ?string
    {
        return $this->algo;
    }

    public function setAlgo(string $algo): self
    {
        $this->algo = $algo;

        return $this;
    }

    public function getPassword(): string
    {
        if (empty($this->originalPassword)) {
            $this->originalPassword = $this->password;
        }

        return (string) $this->password;
    }

    public function getOriginalPassword(): string
    {
        return (string) $this->originalPassword;
    }

    public function hasUpdatedPassword(): bool
    {
        return (empty($this->originalPassword) && !empty($this->password))
            || ($this->originalPassword !== $this->password);
    }

    public function setPassword(?string $password): self
    {
        if (empty($this->originalPassword)) {
            $this->originalPassword = $this->password;
        }

        $this->password = $password;

        return $this;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function eraseCredentials(): self
    {
        $this->password = '';
        $this->originalPassword = '';

        return $this;
    }
}