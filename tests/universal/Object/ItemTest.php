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

namespace Teknoo\Tests\East\Website\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Item;
use Teknoo\East\Common\Service\FindSlugService;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Item::class)]
class ItemTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Item
     */
    public function buildObject(): Item
    {
        return new Item();
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['name' => 'fooBar'])->getName()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated(['name' => 'fooBar'])
        );
    }

    public function testSetName()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setName('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getName()
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setName(new \stdClass());
    }

    public function testGetSlug()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['slug' => 'fooBar'])->getSlug()
        );
    }

    public function testPrepareSlugNear()
    {
        $loader = $this->createMock(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects($this->once())->method('process');

        self::assertInstanceOf(
            Item::class,
            $this->buildObject()->setName('nameValue')->prepareSlugNear(
                $loader,
                $findSlugService,
                'slug',
                ['nameValue'],
            )
        );
    }

    public function testPrepareSlugNearWithCurrentSlugValue()
    {
        $loader = $this->createMock(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects($this->once())->method('process');

        self::assertInstanceOf(
            Item::class,
            $this->buildObject()->setSlug('currentValue')->prepareSlugNear(
                $loader,
                $findSlugService,
                'slug',
                ['currentValue'],
            )
        );
    }

    public function testSetSlug()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setSlug('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getSlug()
        );
    }

    public function testSetSlugExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setSlug(new \stdClass());
    }

    public function testGetContent()
    {
        $object = new Content();
        self::assertEquals(
            $object,
            $this->generateObjectPopulated(['content' => $object])->getContent()
        );
    }

    public function testSetContent()
    {
        $object = new Content();

        $Object = $this->buildObject();
        self::assertInstanceOf(
            Item::class,
            $Object->setContent($object)
        );

        self::assertEquals(
            $object,
            $Object->getContent()
        );

        self::assertInstanceOf(
            Item::class,
            $Object->setContent(null)
        );

        self::assertEmpty($Object->getContent());
    }

    public function testSetContentExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setContent(new \stdClass());
    }

    public function testGetLocation()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['location' => 'fooBar'])->getLocation()
        );
    }

    public function testSetLocation()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setLocation('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getLocation()
        );
    }

    public function testSetLocationExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setLocation(new \stdClass());
    }

    public function testIsHidden()
    {
        self::assertFalse($this->generateObjectPopulated(['hidden' => false])->isHidden());
        self::assertTrue($this->generateObjectPopulated(['hidden' => true])->isHidden());
    }

    public function testSetHidden()
    {
        $Object = $this->buildObject();
        self::assertFalse($Object->isHidden());
        self::assertInstanceOf(
            $Object::class,
            $Object->setHidden(true)
        );

        self::assertTrue($Object->isHidden());
    }

    public function testGetLocaleField()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['localeField' => 'fooBar'])->getLocaleField()
        );
    }

    public function testSetLocaleField()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setLocaleField('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getLocaleField()
        );
    }

    public function testSetLocaleFieldToNull()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setLocaleField(null)
        );

        self::assertNull(
            $Object->getLocaleField()
        );
    }

    public function testSetLocaleFieldExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setLocaleField(new \stdClass());
    }

    public function testGetPosition()
    {
        self::assertEquals(
            123,
            $this->generateObjectPopulated(['position' => 123])->getPosition()
        );
    }

    public function testSetPosition()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setPosition(123)
        );

        self::assertEquals(
            123,
            $Object->getPosition()
        );
    }

    public function testSetPositionExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setPosition(new \stdClass());
    }
    
    public function testGetParent()
    {
        $object = new Item();
        self::assertEquals(
            $object,
            $this->generateObjectPopulated(['parent' => $object])->getParent()
        );
    }

    public function testSetParent()
    {
        $object = new Item();

        $Object = $this->buildObject();
        self::assertInstanceOf(
            Item::class,
            $Object->setParent($object)
        );

        self::assertEquals(
            $object,
            $Object->getParent()
        );

        self::assertInstanceOf(
            Item::class,
            $Object->setParent(null)
        );

        self::assertEmpty($Object->getParent());
    }

    public function testSetParentExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setParent(new \stdClass());
    }

    public function testGetChildren()
    {
        self::assertEquals(
            [],
            $this->generateObjectPopulated(['children' => []])->getChildren()
        );
    }

    public function testSetChildren()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setChildren(['foo'=>'bar'])
        );

        self::assertEquals(
            ['foo'=>'bar'],
            $Object->getChildren()
        );
    }

    public function testSetChildrenExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setChildren(new \stdClass());
    }

    public function testStatesListDeclaration()
    {
        self::assertIsArray(Item::statesListDeclaration());
    }
}
