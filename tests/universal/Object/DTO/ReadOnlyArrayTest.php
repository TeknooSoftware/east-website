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
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Object\DTO;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\DTO\ReadOnlyArray;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Website\Object\DTO\ReadOnlyArray
 */
class ReadOnlyArrayTest extends TestCase
{
    private function buildObject(): ReadOnlyArray
    {
        return new ReadOnlyArray(
            [
                'foo' => 'bar',
                'bar' => 123,
            ],
        );
    }

    public function testOffsetExists()
    {
        $object = $this->buildObject();
        self::assertTrue(isset($object['foo']));
        self::assertFalse(isset($object['foo1']));
    }

    public function testOffsetGet()
    {
        $object = $this->buildObject();
        self::assertEquals('bar', $object['foo']);
        self::assertEquals(123, $object['bar']);
        self::assertNull($object['foo1']);
    }

    public function testOffsetSet()
    {
        $object = $this->buildObject();
        $this->expectException(BadMethodCallException::class);
        $object['foo'] = 'bar';
    }

    public function testOffsetUnset()
    {
        $object = $this->buildObject();
        $this->expectException(BadMethodCallException::class);
        unset($object['foo']);
    }

    public function testToArray()
    {
        $object = $this->buildObject();
        self::assertEquals(
            [
                'foo' => 'bar',
                'bar' => 123,
            ],
            $object->toArray(),
        );
    }

    public function testCount()
    {
        $object = $this->buildObject();
        self::assertEquals(
            2,
            count($object),
        );

        self::assertEquals(
            2,
            $object->count(),
        );
    }
}