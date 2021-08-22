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

namespace Teknoo\East\WebsiteBundle\Object;

use RuntimeException;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Teknoo\East\Website\Object\StoredPassword;
use Teknoo\East\Website\Object\User as BaseUser;


/**
 * Symfony user class, implementing Symfony interface and wrapping East Website User.
 * To use with Symfony 5.2
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PasswordAuthenticatedUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    public function __construct(
        private BaseUser $user,
        protected StoredPassword $password,
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function getRoles(): iterable
    {
        return $this->user->getRoles();
    }

    public function getPassword(): string
    {
        return $this->password->getPassword();
    }

    public function getSalt()
    {
        throw new RuntimeException("UserInterface::getSalt is deprecated");
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->user->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getUserIdentifier();
    }

    public function eraseCredentials(): self
    {
        $this->password->eraseCredentials();

        return $this;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self &&  $user->getUsername() === $this->getUsername();
    }
}
