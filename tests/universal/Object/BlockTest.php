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

namespace Teknoo\Tests\East\Website\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\Block;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Website\Object\Block
 */
class BlockTest extends TestCase
{
    use PopulateObjectTrait;

    /**
     * @return Block
     */
    public function buildObject(): Block
    {
        return new Block('', BlockType::Text);
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

    public function testGetType()
    {
        self::assertEquals(
            BlockType::Textarea,
            $this->generateObjectPopulated(['type' => BlockType::Textarea])->getType()
        );
    }

    public function testSetType()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setType(BlockType::Raw)
        );

        self::assertEquals(
            BlockType::Raw,
            $Object->getType()
        );
    }

    public function testSetTypeExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setType(new \stdClass());
    }
}
