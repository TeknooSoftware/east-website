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

/**
 * @license     https://teknoo.software/license/mit         MIT License
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
