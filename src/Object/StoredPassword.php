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

use Teknoo\East\Website\Contracts\User\AuthDataInterface;
use Teknoo\East\Website\Contracts\User\AuthenticatorInterface;
use Teknoo\East\Website\Contracts\User\UserInterface;

/**
 * Class to defined persisted user's password to authenticate it on a website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class StoredPassword implements AuthDataInterface
{
    public function __construct(
        private ?UserInterface $user = null,
        private string $salt = '',
        private ?string $password = null,
        private ?string $originalPassword = null,
        private string $authenticatorClass = '',
    ) {
        //initialize for new user
        if (empty($this->salt)) {
            $this->salt = sha1(uniqid('', true));
        }

        if (!empty($this->authenticatorClass) && !\is_a($this->authenticatorClass, AuthenticatorInterface::class, true)) {
            throw new \RuntimeException("{$this->authenticatorClass} is not an instance of AuthenticatorInterface");
        }
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

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

    public function eraseCredentials(): self
    {
        $this->password = '';
        $this->originalPassword = '';

        return $this;
    }

    public function getAuthenticatorClass(): string
    {
        return $this->authenticatorClass;
    }

    public function setAuthenticatorClass(string $authenticatorClass): self
    {
        if (!empty($authenticatorClass) && !\is_a($authenticatorClass, AuthenticatorInterface::class, true)) {
            throw new \RuntimeException("$authenticatorClass is not an instance of AuthenticatorInterface");
        }

        $this->authenticatorClass = $authenticatorClass;

        return $this;
    }
}
