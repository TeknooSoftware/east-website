<?php

/**
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

namespace Teknoo\Tests\East\WebsiteBundle\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Teknoo\East\Website\Object\StoredPassword;
use Teknoo\East\Website\Object\User;
use Teknoo\East\WebsiteBundle\Object\AbstractUser;
use Teknoo\East\WebsiteBundle\Object\PasswordAuthenticatedUser;
use Teknoo\East\Website\Object\User as BaseUser;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
abstract class AbstractUserTest extends TestCase
{
    private ?BaseUser $user = null;

    private ?StoredPassword $storedPassword = null;

    /**
     * @return BaseUser|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getUser(): BaseUser
    {
        if (!$this->user instanceof BaseUser) {
            $this->user = $this->createMock(BaseUser::class);

            $this->user->expects(self::any())->method('getAuthData')->willReturn([$this->getStoredPassword()]);
        }

        return $this->user;
    }

    /**
     * @return StoredPassword|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getStoredPassword(): StoredPassword
    {
        if (!$this->storedPassword instanceof StoredPassword) {
            $this->storedPassword = $this->createMock(StoredPassword::class);
        }

        return $this->storedPassword;
    }

    abstract public function buildObject(): AbstractUser;

    public function testExceptionWithBadUser()
    {
        $this->expectException(\TypeError::class);
        new PasswordAuthenticatedUser(new \stdClass(), $this->getStoredPassword());
    }

    public function testExceptionWithBadStoredPassword()
    {
        $this->expectException(\TypeError::class);
        new PasswordAuthenticatedUser($this->getUser(), new \stdClass());
    }

    public function testGetRoles()
    {
        $this->getUser()
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn(['foo','bar']);

        self::assertEquals(
            ['foo','bar'],
            $this->buildObject()->getRoles()
        );
    }

    public function testGetPassword()
    {
        $this->getStoredPassword()
            ->expects(self::once())
            ->method('getHash')
            ->willReturn('foo');

        self::assertEquals(
            'foo',
            $this->buildObject()->getPassword()
        );
    }

    public function testGetSalt()
    {
        self::assertEmpty($this->buildObject()->getSalt());
    }

    public function testGetUsername()
    {
        $this->getUser()
            ->expects(self::once())
            ->method('getUserIdentifier')
            ->willReturn('username');

        self::assertEquals(
            'username',
            $this->buildObject()->getUsername()
        );
    }

    public function testGetUserIdentifier()
    {
        $this->getUser()
            ->expects(self::once())
            ->method('getUserIdentifier')
            ->willReturn('username');

        self::assertEquals(
            'username',
            $this->buildObject()->getUserIdentifier()
        );
    }

    public function testEraseCredentials()
    {
        $this->getStoredPassword()
            ->expects(self::once())
            ->method('eraseCredentials')
            ->willReturnSelf();

        self::assertInstanceOf(
            PasswordAuthenticatedUserInterface::class,
            $this->buildObject()->eraseCredentials()
        );
    }

    public function testExceptionOnIsEqualToWithBadUser()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->isEqualTo(new \stdClass());
    }

    public function testIsEqualToNotSameUserName()
    {
        if (!\method_exists(UserInterface::class, 'getUsername')) {
            self::markTestSkipped('Method removed in Interface');
        }

        $this->getUser()
            ->expects(self::any())
            ->method('getUserIdentifier')
            ->willReturn('myUserName');

        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::any())
            ->method('getUsername')
            ->willReturn('notUserName');

        self::assertFalse(
            $this->buildObject()->isEqualTo($user)
        );
    }

    public function testIsEqualToSameUserName()
    {
        if (!\method_exists(UserInterface::class, 'getUsername')) {
            self::markTestSkipped('Method removed in Interface');
        }
        
        $this->getUser()
            ->expects(self::once())
            ->method('getUserIdentifier')
            ->willReturn('myUserName');

        $user = $this->createMock(PasswordAuthenticatedUser::class);
        $user
            ->expects(self::any())
            ->method('getUsername')
            ->willReturn('myUserName');

        self::assertTrue(
            $this->buildObject()->isEqualTo($user)
        );
    }

    public function testGetWrappedUser()
    {
        self::assertInstanceOf(
            User::class,
            $this->buildObject()->getWrappedUser()
        );
    }

    public function testGetWrappedStoredPassword()
    {
        self::assertInstanceOf(
            StoredPassword::class,
            $this->buildObject()->getWrappedStoredPassword()
        );
    }
}
