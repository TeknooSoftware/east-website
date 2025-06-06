<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Object\Traits;

use Teknoo\East\Common\Contracts\Object\DeletableInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait ObjectTestTrait
{
    use PopulateObjectTrait;

    public function testGetId()
    {
        self::assertEquals(
            123,
            $this->generateObjectPopulated(['id' => 123])->getId()
        );
    }

    public function testSetId()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setId('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getId()
        );
    }

    public function testSetIdExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setId(new \stdClass());
    }

    public function testCreatedAt()
    {
        $date = new \DateTime('2017-06-13');
        self::assertEquals(
            $date,
            $this->generateObjectPopulated(['createdAt' => $date])->createdAt()
        );
    }

    public function testUpdatedAt()
    {
        $date = new \DateTime('2017-06-13');
        self::assertEquals(
            $date,
            $this->generateObjectPopulated(['updatedAt' => $date])->updatedAt()
        );
    }

    public function testSetUpdatedAt()
    {
        $date = new \DateTime('2017-06-13');

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setUpdatedAt($date)
        );

        self::assertEquals(
            $date,
            $object->updatedAt()
        );
    }

    public function testSetUpdatedAtExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setUpdatedAt(new \stdClass());
    }

    public function testDeletedAt()
    {
        $object = $this->buildObject();
        if (!$object instanceof DeletableInterface) {
            self::assertTrue(true); //To avoid warning about skipped test
            return;
        }

        $date = new \DateTime('2017-06-13');
        self::assertEquals(
            $date,
            $this->generateObjectPopulated(['deletedAt' => $date])->getDeletedAt()
        );
    }

    public function testSetDeletedAt()
    {
        $object = $this->buildObject();
        if (!$object instanceof DeletableInterface) {
            self::assertTrue(true); //To avoid warning about skipped test
            return;
        }

        $date = new \DateTime('2017-06-13');

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setDeletedAt($date)
        );

        self::assertEquals(
            $date,
            $object->getDeletedAt()
        );
    }

    public function testSetDeletedAtExceptionOnBadArgument()
    {
        $object = $this->buildObject();
        if (!$object instanceof DeletableInterface) {
            self::assertTrue(true); //To avoid warning about skipped test
            return;
        }

        $this->expectException(\Throwable::class);
        $this->buildObject()->setDeletedAt(new \stdClass());
    }
}
