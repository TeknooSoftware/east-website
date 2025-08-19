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
use Teknoo\East\Website\Object\Block;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Block::class)]
class BlockTest extends TestCase
{
    use PopulateObjectTrait;

    public function buildObject(): Block
    {
        return new Block('', BlockType::Text);
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

    public function testGetType(): void
    {
        $this->assertEquals(BlockType::Textarea, $this->generateObjectPopulated(['type' => BlockType::Textarea])->getType());
    }

    public function testSetType(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setType(BlockType::Raw));

        $this->assertEquals(BlockType::Raw, $Object->getType());
    }

    public function testSetTypeExceptionOnBadArgument(): void
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setType(new \stdClass());
    }
}
