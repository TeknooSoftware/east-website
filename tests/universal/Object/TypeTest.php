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
use Teknoo\East\Website\Object\Block;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;
use Teknoo\East\Website\Object\Type;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Type::class)]
class TypeTest extends TestCase
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
            $Object::class,
            $Object->setTemplate('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getTemplate()
        );
    }

    public function testSetTemplateExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setTemplate(new \stdClass());
    }


    public function testGetBlocks()
    {
        self::assertEquals(
            [new Block('foo', BlockType::Raw)],
            $this->generateObjectPopulated(['blocks' => ['foo'=>BlockType::Raw->value]])->getBlocks()
        );
    }

    public function testSetBlocks()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setBlocks([new Block('foo', BlockType::Textarea)])
        );

        self::assertEquals(
            [new Block('foo', BlockType::Textarea)],
            $Object->getBlocks()
        );
    }

    public function testSetBlocksExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setBlocks(new \stdClass());
    }
}
