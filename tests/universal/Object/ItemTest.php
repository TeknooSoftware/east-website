<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Item::class)]
class ItemTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): Item
    {
        return new Item();
    }

    public function testGetName(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['name' => 'fooBar'])->getName());
    }

    public function testToString(): void
    {
        $this->assertEquals('fooBar', (string) $this->generateObjectPopulated(['name' => 'fooBar']));
    }

    public function testSetName(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setName('fooBar'));

        $this->assertEquals('fooBar', $Object->getName());
    }

    public function testSetNameExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setName(new \stdClass());
    }

    public function testGetSlug(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['slug' => 'fooBar'])->getSlug());
    }

    public function testPrepareSlugNear(): void
    {
        $loader = $this->createStub(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects($this->once())->method('process');

        $this->assertInstanceOf(Item::class, $this->buildObject()->setName('nameValue')->prepareSlugNear(
            $loader,
            $findSlugService,
            'slug',
        ));
    }

    public function testPrepareSlugNearWithCurrentSlugValue(): void
    {
        $loader = $this->createStub(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects($this->once())->method('process');

        $this->assertInstanceOf(Item::class, $this->buildObject()->setSlug('currentValue')->prepareSlugNear(
            $loader,
            $findSlugService,
            'slug',
        ));
    }

    public function testSetSlug(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setSlug('fooBar'));

        $this->assertEquals('fooBar', $Object->getSlug());
    }

    public function testSetSlugExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setSlug(new \stdClass());
    }

    public function testGetContent(): void
    {
        $object = new Content();
        $this->assertEquals($object, $this->generateObjectPopulated(['content' => $object])->getContent());
    }

    public function testSetContent(): void
    {
        $object = new Content();

        $Object = $this->buildObject();
        $this->assertInstanceOf(Item::class, $Object->setContent($object));

        $this->assertEquals($object, $Object->getContent());

        $this->assertInstanceOf(Item::class, $Object->setContent(null));

        $this->assertNotInstanceOf(\Teknoo\East\Website\Object\Content::class, $Object->getContent());
    }

    public function testSetContentExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setContent(new \stdClass());
    }

    public function testGetLocation(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['location' => 'fooBar'])->getLocation());
    }

    public function testSetLocation(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setLocation('fooBar'));

        $this->assertEquals('fooBar', $Object->getLocation());
    }

    public function testSetLocationExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setLocation(new \stdClass());
    }

    public function testIsHidden(): void
    {
        $this->assertFalse($this->generateObjectPopulated(['hidden' => false])->isHidden());
        $this->assertTrue($this->generateObjectPopulated(['hidden' => true])->isHidden());
    }

    public function testSetHidden(): void
    {
        $Object = $this->buildObject();
        $this->assertFalse($Object->isHidden());
        $this->assertInstanceOf($Object::class, $Object->setHidden(true));

        $this->assertTrue($Object->isHidden());
    }

    public function testGetLocaleField(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['localeField' => 'fooBar'])->getLocaleField());
    }

    public function testSetLocaleField(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setLocaleField('fooBar'));

        $this->assertEquals('fooBar', $Object->getLocaleField());
    }

    public function testSetLocaleFieldToNull(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setLocaleField(null));

        $this->assertNull($Object->getLocaleField());
    }

    public function testSetLocaleFieldExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setLocaleField(new \stdClass());
    }

    public function testGetPosition(): void
    {
        $this->assertEquals(123, $this->generateObjectPopulated(['position' => 123])->getPosition());
    }

    public function testSetPosition(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setPosition(123));

        $this->assertEquals(123, $Object->getPosition());
    }

    public function testSetPositionExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setPosition(new \stdClass());
    }

    public function testGetParent(): void
    {
        $object = new Item();
        $this->assertEquals($object, $this->generateObjectPopulated(['parent' => $object])->getParent());
    }

    public function testSetParent(): void
    {
        $object = new Item();

        $Object = $this->buildObject();
        $this->assertInstanceOf(Item::class, $Object->setParent($object));

        $this->assertEquals($object, $Object->getParent());

        $this->assertInstanceOf(Item::class, $Object->setParent(null));

        $this->assertNotInstanceOf(\Teknoo\East\Website\Object\Item::class, $Object->getParent());
    }

    public function testSetParentExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setParent(new \stdClass());
    }

    public function testGetChildren(): void
    {
        $this->assertEquals([], $this->generateObjectPopulated(['children' => []])->getChildren());
    }

    public function testSetChildren(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setChildren(['foo' => 'bar']));

        $this->assertEquals(['foo' => 'bar'], $Object->getChildren());
    }

    public function testSetChildrenExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setChildren(new \stdClass());
    }
}
