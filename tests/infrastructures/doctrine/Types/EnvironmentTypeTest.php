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

namespace Teknoo\Tests\East\Website\Doctrine\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Website\Doctrine\Types\EnvironmentType;
use Teknoo\East\Website\Object\Environment;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(EnvironmentType::class)]
class EnvironmentTypeTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::reset();
        parent::tearDown();
    }

    private function buildType(): EnvironmentType
    {
        EnvironmentType::register();
        $type = Type::getType(EnvironmentType::NAME);
        $this->assertInstanceOf(EnvironmentType::class, $type);

        return $type;
    }

    public function testRegisterIsIdempotent(): void
    {
        EnvironmentType::register();
        EnvironmentType::register();

        $this->assertTrue(Type::hasType(EnvironmentType::NAME));
        $this->assertInstanceOf(EnvironmentType::class, Type::getType(EnvironmentType::NAME));
    }

    public function testConvertToDatabaseValue(): void
    {
        $type = $this->buildType();

        $this->assertNull($type->convertToDatabaseValue(null));
        $this->assertSame('validation', $type->convertToDatabaseValue(Environment::get('validation')));
        $this->assertSame('default', $type->convertToDatabaseValue(Environment::default()));
        $this->assertSame('testing', $type->convertToDatabaseValue('testing'));
    }

    public function testConvertToDatabaseValueWithAnInvalidValue(): void
    {
        $type = $this->buildType();

        $this->expectException(InvalidArgumentException::class);
        $type->convertToDatabaseValue(new stdClass());
    }

    public function testConvertToPHPValue(): void
    {
        $type = $this->buildType();

        $this->assertNull($type->convertToPHPValue(null));
        $this->assertSame(Environment::default(), $type->convertToPHPValue('default'));
        $this->assertSame(Environment::get('validation'), $type->convertToPHPValue('validation'));
        //Always the same flyweight instance
        $this->assertSame($type->convertToPHPValue('validation'), $type->convertToPHPValue('validation'));
        $this->assertSame(Environment::get('testing'), $type->convertToPHPValue(Environment::get('testing')));
    }

    public function testConvertToPHPValueWithAnInvalidValue(): void
    {
        $type = $this->buildType();

        $this->expectException(InvalidArgumentException::class);
        $type->convertToPHPValue(123);
    }

    public function testClosureToPHPUsesConvertToPHPValue(): void
    {
        $this->assertStringContainsString('convertToPHPValue', $this->buildType()->closureToPHP());
    }
}
