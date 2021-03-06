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

namespace Teknoo\Tests\East\WebsiteBundle\Writer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\WebsiteBundle\Writer\UserWriter;
use Teknoo\East\Website\Writer\UserWriter as UniversalWriter;
use Teknoo\East\Website\Object\User as BaseUser;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\WebsiteBundle\Writer\UserWriter
 */
class UserWriterTest extends TestCase
{
    /**
     * @var UniversalWriter
     */
    private $universalWriter;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @return UniversalWriter|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getUniversalWriter(): UniversalWriter
    {
        if (!$this->universalWriter instanceof UniversalWriter) {
            $this->universalWriter = $this->createMock(UniversalWriter::class);
        }

        return $this->universalWriter;
    }

    /**
     * @return EncoderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getEncoderFactory(): EncoderFactoryInterface
    {
        if (!$this->encoderFactory instanceof EncoderFactoryInterface) {
            $this->encoderFactory = $this->createMock(EncoderFactoryInterface::class);
        }

        return $this->encoderFactory;
    }

    public function buildWriter(): UserWriter
    {
        return new UserWriter(
            $this->getUniversalWriter(),
            $this->getEncoderFactory()
        );
    }

    public function testExceptionOnSaveWithBadPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildWriter()->save(new \stdClass(), new \stdClass());
    }

    public function testSaveWithWrongObject()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $object = $this->createMock(ObjectInterface::class);

        $this->getUniversalWriter()
            ->expects(self::never())
            ->method('save');

        $promise->expects(self::once())
            ->method('fail');

        self::assertInstanceOf(
            UserWriter::class,
            $this->buildWriter()->save($object, $promise)
        );
    }

    public function testSaveWithUserWithNoUpdatedPassword()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $user = $this->createMock(BaseUser::class);
        $user->expects(self::once())
            ->method('hasUpdatedPassword')
            ->willReturn(false);

        $user->expects(self::once())
            ->method('eraseCredentials');

        $user->expects(self::never())
            ->method('setPassword');

        $this->getUniversalWriter()
            ->expects(self::once())
            ->method('save')
            ->with($user, $promise)
            ->willReturnSelf();

        self::assertInstanceOf(
            UserWriter::class,
            $this->buildWriter()->save($user, $promise)
        );
    }

    public function testSaveWithUserWithUpdatedPassword()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $user = $this->createMock(BaseUser::class);
        $encoder = $this->createMock(PasswordEncoderInterface::class);

        $this->getEncoderFactory()
            ->expects(self::any())
            ->method('getEncoder')
            ->willReturn($encoder);

        $encoder->expects(self::once())
            ->method('encodePassword')
            ->willReturn('fooBar');

        $user->expects(self::once())
            ->method('hasUpdatedPassword')
            ->willReturn(true);

        $user->expects(self::never())
            ->method('eraseCredentials');

        $user->expects(self::once())
            ->method('getSalt');

        $user->expects(self::once())
            ->method('setPassword')
            ->with('fooBar')
            ->willReturnSelf();

        $this->getUniversalWriter()
            ->expects(self::once())
            ->method('save')
            ->with($user, $promise)
            ->willReturnSelf();

        self::assertInstanceOf(
            UserWriter::class,
            $this->buildWriter()->save($user, $promise)
        );
    }

    public function testRemove()
    {
        $object = $this->createMock(ObjectInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $this->getUniversalWriter()
            ->expects(self::once())
            ->method('remove')
            ->with($object, $promise)
            ->willReturnSelf();

        self::assertInstanceOf(
            UserWriter::class,
            $this->buildWriter()->remove($object, $promise)
        );
    }
}
