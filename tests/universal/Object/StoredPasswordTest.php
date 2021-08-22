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

namespace Teknoo\Tests\East\Website\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Contracts\User\AuthenticatorInterface;
use Teknoo\East\Website\Contracts\User\UserInterface;
use Teknoo\East\Website\Object\StoredPassword;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Website\Object\StoredPassword
 */
class StoredPasswordTest extends TestCase
{
    use PopulateObjectTrait;

    /**
     * @return StoredPassword
     */
    public function buildObject(): StoredPassword
    {
        return new StoredPassword();
    }

    public function testGetAuthenticatorClass()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['authenticatorClass' => 'fooBar'])->getAuthenticatorClass()
        );
    }

    public function testSetAuthenticatorClass()
    {
        $object = $this->buildObject();
        $fakeClass = new class implements AuthenticatorInterface {
        };
        self::assertInstanceOf(
            \get_class($object),
            $object->setAuthenticatorClass(\get_class($fakeClass))
        );

        self::assertEquals(
            \get_class($fakeClass),
            $object->getAuthenticatorClass()
        );
    }

    public function testSetAuthenticatorClassExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setAuthenticatorClass(new \stdClass());
    }

    public function testSetUserExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setUser(new \stdClass());
    }

    public function testGetPassword()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['password' => 'fooBar'])->getPassword()
        );
    }

    public function testSetPassword()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getPassword()
        );
    }

    public function testEraseCredentials()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getPassword()
        );

        self::assertEquals(
            'fooBar',
            $object->getOriginalPassword()
        );

        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar2')
        );

        self::assertEquals(
            'fooBar2',
            $object->getPassword()
        );

        self::assertEquals(
            'fooBar',
            $object->getOriginalPassword()
        );

        self::assertInstanceOf(
            \get_class($object),
            $object->eraseCredentials()
        );

        self::assertEmpty($object->getPassword());
        self::assertEmpty($object->getOriginalPassword());
    }

    public function testHasUpdatedPassword()
    {
        $object = $this->buildObject();
        self::assertFalse($object->hasUpdatedPassword());
        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar')
        );

        self::assertTrue($object->hasUpdatedPassword());

        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar2')
        );

        self::assertTrue($object->hasUpdatedPassword());

        $object = $this->buildObject(['password' => 'fooBar']);
        self::assertFalse($object->hasUpdatedPassword());

        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword(null)
        );

        self::assertFalse($object->hasUpdatedPassword());

        $object = $this->buildObject();
        $refProperty = new \ReflectionProperty($object, 'password');
        $refProperty->setAccessible(true);
        $refProperty->setValue($object, 'fooBar');

        self::assertTrue($object->hasUpdatedPassword());

        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar')
        );

        self::assertFalse($object->hasUpdatedPassword());

        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar2')
        );

        self::assertTrue($object->hasUpdatedPassword());

        self::assertInstanceOf(
            \get_class($object),
            $object->setPassword('fooBar3')
        );

        self::assertTrue($object->hasUpdatedPassword());
    }

    public function testSetPasswordExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setPassword(new \stdClass());
    }

    public function testGetSalt()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['salt' => 'fooBar'])->getSalt()
        );
    }

    public function testSetSalt()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setSalt('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getSalt()
        );
    }

    public function testGetAlgo()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['algo' => 'fooBar'])->getAlgo()
        );
    }

    public function testSetAlgo()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setAlgo('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getAlgo()
        );
    }

    public function testSetSaltExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setSalt(new \stdClass());
    }
}
