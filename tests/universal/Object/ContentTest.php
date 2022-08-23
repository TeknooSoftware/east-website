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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Website\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Common\Service\FindSlugService;
use Teknoo\Tests\East\Website\Object\Traits\PublishableTestTrait;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Common\Object\User;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Website\Object\PublishableTrait
 * @covers \Teknoo\East\Website\Object\Content
 * @covers \Teknoo\East\Website\Object\Content\Draft
 * @covers \Teknoo\East\Website\Object\Content\Published
 */
class ContentTest extends TestCase
{
    use PublishableTestTrait;

    /**
     * @return Content
     */
    public function buildObject(): Content
    {
        return new Content();
    }

    public function testGetParts()
    {
        $object = $this->generateObjectPopulated(['parts' => \json_encode(['fooBar'])]);

        self::assertEquals(
            ['fooBar'],
            $object->getParts()->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            $object->getParts()->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            $object->getParts()->toArray(),
        );
    }

    public function testSetParts()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setParts(['fooBar'])
        );

        self::assertEquals(
            ['fooBar'],
            $object->getParts()->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            $object->getParts()->toArray(),
        );

        self::assertInstanceOf(
            $object::class,
            $object->setParts(['fooBar2'])
        );

        self::assertEquals(
            ['fooBar2'],
            $object->getParts()->toArray(),
        );

        self::assertEquals(
            ['fooBar2'],
            $object->getParts()->toArray(),
        );

        self::assertInstanceOf(
            $object::class,
            $object->setParts(null)
        );

        self::assertEmpty(
            $object->getParts()->toArray(),
        );
    }

    public function testSetPartsExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setContent(new \stdClass());
    }

    public function testGetSanitizedParts()
    {
        $object = $this->generateObjectPopulated(
            [
                'sanitizedParts' => $value = \json_encode(['fooBar']),
                'sanitizedHash' => \hash(
                    algo: 'sha256',
                    data: 'barFoo' . $value,
                )
            ]
        );

        self::assertNull(
            $object->getSanitizedParts('barFoo2')
        );

        self::assertEquals(
            ['fooBar'],
            $object->getSanitizedParts('barFoo')->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            $object->getSanitizedParts('barFoo')->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            $object->getSanitizedParts('barFoo')->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            $object->getSanitizedParts('barFoo2')->toArray(),
        );
    }

    public function testSetSanitizedParts()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setSanitizedParts(['fooBar'], 'barFoo')
        );

        self::assertEquals(
            ['fooBar'],
            ($a1 = $object->getSanitizedParts('barFoo'))->toArray(),
        );

        self::assertEquals(
            ['fooBar'],
            ($a2 = $object->getSanitizedParts('barFoo'))->toArray(),
        );

        self::assertSame($a1, $a2);

        self::assertInstanceOf(
            $object::class,
            $object->setSanitizedParts(['fooBar2'], 'barFoo')
        );

        self::assertEquals(
            ['fooBar2'],
            ($a3 = $object->getSanitizedParts('barFoo'))->toArray(),
        );

        self::assertEquals(
            ['fooBar2'],
            ($a4 = $object->getSanitizedParts('barFoo'))->toArray(),
        );

        self::assertSame($a3, $a4);
        self::assertNotSame($a1, $a3);

        self::assertInstanceOf(
            $object::class,
            $object->setSanitizedParts(null, 'barFoo')
        );

        self::assertEmpty(
            $object->getSanitizedParts('barFoo')->toArray(),
        );
    }

    public function testSetSanitizedPartsExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setContent(new \stdClass());
    }

    public function testGetDescription()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['description' => 'fooBar'])->getDescription()
        );
    }

    public function testSetDescription()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setDescription('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getDescription()
        );

        self::assertInstanceOf(
            $object::class,
            $object->setDescription(null)
        );

        self::assertEmpty(
            $object->getDescription()
        );
    }

    public function testSetDescriptionExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setDescription(new \stdClass());
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
        $findSlugService->expects(self::once())->method('process');

        self::assertInstanceOf(
            Content::class,
            $this->buildObject()->setTitle('titleValue')->prepareSlugNear(
                $loader,
                $findSlugService,
                'slug',
                ['titleValue'],
            )
        );
    }

    public function testPrepareSlugNearWithCurrentSlugValue()
    {
        $loader = $this->createMock(LoaderInterface::class);

        $findSlugService = $this->createMock(FindSlugService::class);
        $findSlugService->expects(self::once())->method('process');

        self::assertInstanceOf(
            Content::class,
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
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setSlug('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getSlug()
        );
    }

    public function testSetSlugExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setSlug(new \stdClass());
    }

    public function testGetSubtitle()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['subtitle' => 'fooBar'])->getSubtitle()
        );
    }

    public function testSetSubtitle()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setSubtitle('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getSubtitle()
        );
    }

    public function testSetSubtitleExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setSubtitle(new \stdClass());
    }

    public function testGetTitle()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['title' => 'fooBar'])->getTitle()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated(['title' => 'fooBar'])
        );
    }

    public function testSetTitle()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setTitle('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getTitle()
        );
    }

    public function testSetTitleExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setTitle(new \stdClass());
    }

    public function testGetAuthor()
    {
        $object = new User();
        self::assertEquals(
            $object,
            $this->generateObjectPopulated(['author' => $object])->getAuthor()
        );
    }

    public function testSetAuthor()
    {
        $user = new User();

        $object = $this->buildObject();
        self::assertInstanceOf(
            Content::class,
            $object->setAuthor($user)
        );

        self::assertEquals(
            $user,
            $object->getAuthor()
        );
    }

    public function testSetAuthorExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setAuthor(new \stdClass());
    }

    public function testGetType()
    {
        $object = new Type();
        self::assertEquals(
            $object,
            $this->generateObjectPopulated(['type' => $object])->getType()
        );
    }

    public function testSetType()
    {
        $type = new Type();

        $object = $this->buildObject();
        self::assertInstanceOf(
            Content::class,
            $object->setType($type)
        );

        self::assertEquals(
            $type,
            $object->getType()
        );
    }

    public function testSetTypeExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setType(new \stdClass());
    }

    public function testGetTags()
    {
        self::assertEquals(
            [],
            $this->generateObjectPopulated(['tags' => []])->getTags()
        );
    }

    public function testSetTags()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setTags(['foo'=>'bar'])
        );

        self::assertEquals(
            ['foo'=>'bar'],
            $object->getTags()
        );
    }

    public function testSetTagsExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setTags(new \stdClass());
    }

    public function testStatesListDeclaration()
    {
        self::assertIsArray(Content::statesListDeclaration());
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
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setLocaleField('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $object->getLocaleField()
        );
    }

    public function testSetLocaleFieldToNull()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setLocaleField(null)
        );

        self::assertNull(
            $object->getLocaleField()
        );
    }

    public function testSetLocaleFieldExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setLocaleField(new \stdClass());
    }
}
