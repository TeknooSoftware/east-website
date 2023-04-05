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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Object\Traits;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait PublishableTestTrait
{
    use ObjectTestTrait;

    public function testGetPublishedAt()
    {
        $date = new \DateTime('2017-06-13');
        self::assertEquals(
            $date,
            $this->generateObjectPopulated(['publishedAt' => $date])->getPublishedAt()
        );
    }

    public function testSetPublishedAt()
    {
        $date = new \DateTime('2017-06-13');
        $date2 = new \DateTime('2017-06-14');

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setPublishedAt($date)
        );

        self::assertInstanceOf(
            $object::class,
            $object->setPublishedAt($date2)
        );

        self::assertEquals(
            $date,
            $object->getPublishedAt()
        );
    }

    public function testSetPublishedAtExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setPublishedAt(new \stdClass());
    }
}
