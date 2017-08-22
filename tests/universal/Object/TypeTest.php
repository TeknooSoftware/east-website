<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Website\Object;

use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;
use Teknoo\East\Website\Object\Type;

/**
 * @covers \Teknoo\East\Website\Object\PublishableTrait
 * @covers \Teknoo\East\Website\Object\ObjectTrait
 * @covers \Teknoo\East\Website\Object\Type
 */
class TypeTest extends \PHPUnit_Framework_TestCase
{
    use ObjectTestTrait;

    /**
     * @return Type
     */
    public function buildObject(): Type
    {
        return new Type();
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['name' => 'fooBar'])->getName()
        );
    }

    public function testSetName()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($Object),
            $Object->setName('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getName()
        );
    }

    /**
     * @expectedException \Throwable
     */
    public function testSetNameExceptionOnBadArgument()
    {
        $this->buildObject()->setName(new \stdClass());
    }


    public function testGetTemplate()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['template' => 'fooBar'])->getTemplate()
        );
    }

    public function testSetTemplate()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($Object),
            $Object->setTemplate('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getTemplate()
        );
    }

    /**
     * @expectedException \Throwable
     */
    public function testSetTemplateExceptionOnBadArgument()
    {
        $this->buildObject()->setTemplate(new \stdClass());
    }

    public function testGetParent()
    {
        $object = new Type();
        self::assertEquals(
            $object,
            $this->generateObjectPopulated(['parent' => $object])->getParent()
        );
    }

    public function testSetParent()
    {
        $object = new Type();

        $Object = $this->buildObject();
        self::assertInstanceOf(
            Type::class,
            $Object->setParent($object)
        );

        self::assertEquals(
            $object,
            $Object->getParent()
        );
    }

    /**
     * @expectedException \Throwable
     */
    public function testSetParentExceptionOnBadArgument()
    {
        $this->buildObject()->setParent(new \stdClass());
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
            \get_class($Object),
            $Object->setLocaleField('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getLocaleField()
        );
    }

    public function testSetTranslatableLocale()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($Object),
            $Object->setTranslatableLocale('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getLocaleField()
        );
    }

    /**
     * @expectedException \Throwable
     */
    public function testSetLocaleFieldExceptionOnBadArgument()
    {
        $this->buildObject()->setLocaleField(new \stdClass());
    }
}