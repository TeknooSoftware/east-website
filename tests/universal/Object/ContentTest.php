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
use stdClass;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Common\Service\FindSlugService;
use Teknoo\East\Website\Object\Content\Draft;
use Teknoo\East\Website\Object\Content\Published;
use Teknoo\Tests\East\Website\Object\Traits\PublishableTestTrait;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Common\Object\User;
use Throwable;

use function hash;
use function json_encode;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Published::class)]
#[CoversClass(Draft::class)]
#[CoversClass(Content::class)]
class ContentTest extends TestCase
{
    use PublishableTestTrait;

    public function buildObject(): Content
    {
        return new Content();
    }

    public function testGetParts(): void
    {
        $object = $this->generateObjectPopulated(['parts' => json_encode(['fooBar'])]);

        $this->assertEquals(['fooBar'], $object->getParts()->toArray());

        $this->assertEquals(['fooBar'], $object->getParts()->toArray());

        $this->assertEquals(['fooBar'], $object->getParts()->toArray());
    }

    public function testSetParts(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setParts(['fooBar']));

        $this->assertEquals(['fooBar'], $object->getParts()->toArray());

        $this->assertEquals(['fooBar'], $object->getParts()->toArray());

        $this->assertInstanceOf($object::class, $object->setParts(['fooBar2']));

        $this->assertEquals(['fooBar2'], $object->getParts()->toArray());

        $this->assertEquals(['fooBar2'], $object->getParts()->toArray());

        $this->assertInstanceOf($object::class, $object->setParts(null));

        $this->assertEmpty($object->getParts()->toArray());
    }

    public function testSetPartsExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setContent(new stdClass());
    }

    public function testGetSanitizedParts(): void
    {
        $object = $this->generateObjectPopulated(
            [
                'sanitizedParts' => $value = json_encode(['fooBar']),
                'sanitizedHash' => hash(
                    algo: 'sha256',
                    data: 'barFoo' . $value,
                )
            ]
        );

        $this->assertNull($object->getSanitizedParts('barFoo2'));

        $this->assertEquals(['fooBar'], $object->getSanitizedParts('barFoo')->toArray());

        $this->assertEquals(['fooBar'], $object->getSanitizedParts('barFoo')->toArray());

        $this->assertEquals(['fooBar'], $object->getSanitizedParts('barFoo')->toArray());

        $this->assertEquals(['fooBar'], $object->getSanitizedParts('barFoo2')->toArray());
    }

    public function testSetSanitizedParts(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setSanitizedParts(['fooBar'], 'barFoo'));

        $this->assertEquals(['fooBar'], ($a1 = $object->getSanitizedParts('barFoo'))->toArray());

        $this->assertEquals(['fooBar'], ($a2 = $object->getSanitizedParts('barFoo'))->toArray());

        $this->assertSame($a1, $a2);

        $this->assertInstanceOf($object::class, $object->setSanitizedParts(['fooBar2'], 'barFoo'));

        $this->assertEquals(['fooBar2'], ($a3 = $object->getSanitizedParts('barFoo'))->toArray());

        $this->assertEquals(['fooBar2'], ($a4 = $object->getSanitizedParts('barFoo'))->toArray());

        $this->assertSame($a3, $a4);
        $this->assertNotSame($a1, $a3);

        $this->assertInstanceOf($object::class, $object->setSanitizedParts(null, 'barFoo'));

        $this->assertEmpty($object->getSanitizedParts('barFoo')->toArray());
    }

    public function testSetSanitizedPartsExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setContent(new stdClass());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['description' => 'fooBar'])->getDescription());
    }

    public function testSetDescription(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setDescription('fooBar'));

        $this->assertEquals('fooBar', $object->getDescription());

        $this->assertInstanceOf($object::class, $object->setDescription(null));

        $this->assertEmpty($object->getDescription());
    }

    public function testSetDescriptionExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setDescription(new stdClass());
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

        $this->assertInstanceOf(Content::class, $this->buildObject()->setTitle('titleValue')->prepareSlugNear(
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

        $this->assertInstanceOf(Content::class, $this->buildObject()->setSlug('currentValue')->prepareSlugNear(
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

    public function testGetSubtitle(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['subtitle' => 'fooBar'])->getSubtitle());
    }

    public function testSetSubtitle(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setSubtitle('fooBar'));

        $this->assertEquals('fooBar', $object->getSubtitle());
    }

    public function testSetSubtitleExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setSubtitle(new stdClass());
    }

    public function testGetTitle(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['title' => 'fooBar'])->getTitle());
    }

    public function testToString(): void
    {
        $this->assertEquals('fooBar', (string) $this->generateObjectPopulated(['title' => 'fooBar']));
    }

    public function testSetTitle(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setTitle('fooBar'));

        $this->assertEquals('fooBar', $object->getTitle());
    }

    public function testSetTitleExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setTitle(new stdClass());
    }

    public function testGetAuthor(): void
    {
        $object = new User();
        $this->assertEquals($object, $this->generateObjectPopulated(['author' => $object])->getAuthor());
    }

    public function testSetAuthor(): void
    {
        $user = new User();

        $object = $this->buildObject();
        $this->assertInstanceOf(Content::class, $object->setAuthor($user));

        $this->assertEquals($user, $object->getAuthor());
    }

    public function testSetAuthorExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setAuthor(new stdClass());
    }

    public function testGetType(): void
    {
        $object = new Type();
        $this->assertEquals($object, $this->generateObjectPopulated(['type' => $object])->getType());
    }

    public function testSetType(): void
    {
        $type = new Type();

        $object = $this->buildObject();
        $this->assertInstanceOf(Content::class, $object->setType($type));

        $this->assertEquals($type, $object->getType());
    }

    public function testSetTypeExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setType(new stdClass());
    }

    public function testGetTags(): void
    {
        $this->assertEquals([], $this->generateObjectPopulated(['tags' => []])->getTags());
    }

    public function testSetTags(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setTags(['foo' => 'bar']));

        $this->assertEquals(['foo' => 'bar'], $object->getTags());
    }

    public function testSetTagsExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setTags(new stdClass());
    }

    public function testGetLocaleField(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['localeField' => 'fooBar'])->getLocaleField());
    }

    public function testSetLocaleField(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setLocaleField('fooBar'));

        $this->assertEquals('fooBar', $object->getLocaleField());
    }

    public function testSetLocaleFieldToNull(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setLocaleField(null));

        $this->assertNull($object->getLocaleField());
    }

    public function testSetLocaleFieldExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setLocaleField(new stdClass());
    }
}
