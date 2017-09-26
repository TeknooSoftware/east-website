<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website\Object;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class User implements DeletableInterface
{
    use ObjectTrait;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string[]
     */
    private $roles = [];

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $originalPassword;

    /**
     * @var string
     */
    private $salt;

    /**
     * User constructor.
     */
    public function __construct()
    {
        //initialize for new user
        $this->salt = \sha1(\uniqid(null, true));
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return (string) $this->firstName;
    }

    /**
     * @param string $firstName
     * @return self
     */
    public function setFirstName(string $firstName): User
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return (string) $this->lastName;
    }

    /**
     * @param string $lastName
     * @return self
     */
    public function setLastName(string $lastName): User
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return \string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param \string[] $roles
     * @return self
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return (string) $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        if (empty($this->originalPassword)) {
            $this->originalPassword = $this->password;
        }

        return (string) $this->password;
    }

    /**
     * @return string
     */
    public function getOriginalPassword(): string
    {
        return (string) $this->originalPassword;
    }

    /**
     * @return bool
     */
    public function hasUpdatedPassword(): bool
    {
        $pwd = $this->getPassword();
        $originalPwd = $this->getOriginalPassword();

        return !empty($pwd) && $originalPwd != $pwd;
    }

    /**
     * @return User
     */
    public function resetPassword(): User
    {
        $this->password = $this->originalPassword;

        return $this;
    }

    /**
     * @param string|null $password
     * @return self
     */
    public function setPassword(string $password=null): User
    {
        if (!empty($this->password)) {
            $this->originalPassword = $this->password;
        }

        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return (string) $this->salt;
    }

    /**
     * @param string $salt
     * @return self
     */
    public function setSalt(string $salt): User
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->getEmail();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->password = '';

        return $this;
    }
}