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
use Teknoo\East\Common\Service\FindSlugService;
use Teknoo\East\Website\Object\Tag;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;
use Throwable;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Tag::class)]
class TagTest extends TestCase
{
    use PopulateObjectTrait;

    protected function buildObject(): Tag
    {
        return new Tag();
    }

    public function testSetName(): void
    {
        $this->assertInstanceOf(Tag::class, $this->buildObject()->setName('foo'));
    }

    public function testGetName(): void
    {
        $this->assertEquals('foo', $this->buildObject()->setName('foo')->getName());
    }

    public function testToString(): void
    {
        $this->assertEquals('foo', (string) $this->buildObject()->setName('foo'));
    }

    public function testIsHighlighted(): void
    {
        $this->assertIsBool($this->buildObject()->isHighlighted());
    }

    public function testSetIsHighlighted(): void
    {
        $this->assertInstanceOf(Tag::class, $this->buildObject()->setIsHighlighted(true));
    }

    public function testGetSlug(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['slug' => 'fooBar'])->getSlug());
    }

    public function testPrepareSlugNear(): void
    {
        $loader = $this->createMock(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects($this->once())->method('process');

        $this->assertInstanceOf(Tag::class, $this->buildObject()->setName('titleValue')->prepareSlugNear(
            $loader,
            $findSlugService,
            'slug',
        ));
    }

    public function testPrepareSlugNearWithCurrentSlugValue(): void
    {
        $loader = $this->createMock(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects($this->once())->method('process');

        $this->assertInstanceOf(Tag::class, $this->buildObject()->setSlug('currentValue')->prepareSlugNear(
            $loader,
            $findSlugService,
            'slug',
        ));
    }

    public function testSetSlug(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setSlug('fooBar'));

        $this->assertEquals('fooBar', $object->getSlug());
    }

    public function testSetSlugExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setSlug(new stdClass());
    }
}
