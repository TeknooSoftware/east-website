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
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environment\Exception\CyclicEnvironmentException;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentAlreadyDefinedException;
use Teknoo\East\Website\Object\Environment\Exception\InvalidEnvironmentNameException;
use Teknoo\Immutable\Exception\ImmutableException;

use function json_encode;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::reset();
        parent::tearDown();
    }

    public function testDefault(): void
    {
        $default = Environment::default();

        $this->assertSame('default', $default->getName());
        $this->assertTrue($default->isDefault());
        $this->assertNull($default->getParent());
        $this->assertSame(['default'], $default->getChain());
        $this->assertSame($default, Environment::default());
        $this->assertSame($default, Environment::get('default'));
        $this->assertTrue(Environment::isDefined('default'));
    }

    public function testGetReturnsAlwaysTheSameInstance(): void
    {
        $environment = Environment::get('validation');

        $this->assertSame('validation', $environment->getName());
        $this->assertFalse($environment->isDefault());
        $this->assertSame($environment, Environment::get('validation'));
        $this->assertNotSame($environment, Environment::get('testing'));
    }

    public function testGetOfAnUndefinedEnvironmentHasTheDefaultAsParent(): void
    {
        $environment = Environment::get('validation');

        $this->assertFalse(Environment::isDefined('validation'));
        $this->assertSame(Environment::default(), $environment->getParent());
        $this->assertSame(['validation', 'default'], $environment->getChain());
    }

    public function testGetWithAnInvalidName(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        Environment::get('Not Valid');
    }

    public function testGetWithAnEmptyName(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        Environment::get('');
    }

    public function testDefine(): void
    {
        $validation = Environment::define('validation', Environment::default());
        $testing = Environment::define('testing', Environment::default());
        $testA = Environment::define('test-a', $testing);

        $this->assertTrue(Environment::isDefined('validation'));
        $this->assertTrue(Environment::isDefined('test-a'));
        $this->assertFalse(Environment::isDefined('test-b'));

        $this->assertSame($validation, Environment::get('validation'));
        $this->assertSame(Environment::default(), $validation->getParent());
        $this->assertSame($testing, $testA->getParent());
        $this->assertSame(['test-a', 'testing', 'default'], $testA->getChain());
        $this->assertSame(['validation', 'default'], $validation->getChain());
    }

    public function testDefineIsIdempotentWithTheSameParent(): void
    {
        $testing = Environment::define('testing', Environment::default());
        $testA = Environment::define('test-a', $testing);

        $this->assertSame($testA, Environment::define('test-a', $testing));
    }

    public function testDefineAfterGetKeepsTheInstance(): void
    {
        //Hydrated from the database before the definitions are loaded
        $testA = Environment::get('test-a');
        $this->assertSame(['test-a', 'default'], $testA->getChain());

        $testing = Environment::define('testing', Environment::default());
        $this->assertSame($testA, Environment::define('test-a', $testing));
        $this->assertSame($testing, $testA->getParent());
        $this->assertSame(['test-a', 'testing', 'default'], $testA->getChain());
    }

    public function testDefineWithAnotherParent(): void
    {
        $testing = Environment::define('testing', Environment::default());
        Environment::define('test-a', $testing);

        $this->expectException(EnvironmentAlreadyDefinedException::class);
        Environment::define('test-a', Environment::default());
    }

    public function testDefineTheDefaultEnvironment(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        Environment::define('default', Environment::get('validation'));
    }

    public function testDefineWithAnInvalidName(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        Environment::define('not valid', Environment::default());
    }

    public function testDefineACycle(): void
    {
        //`a` is not defined yet (hydrated from the database), `b` is defined with `a` as parent
        $a = Environment::get('a');
        $b = Environment::define('b', $a);

        $this->expectException(CyclicEnvironmentException::class);
        Environment::define('a', $b);
    }

    public function testDefineItselfAsParent(): void
    {
        $a = Environment::get('a');

        $this->expectException(CyclicEnvironmentException::class);
        Environment::define('a', $a);
    }

    public function testReset(): void
    {
        $validation = Environment::define('validation', Environment::default());

        Environment::reset();

        $this->assertFalse(Environment::isDefined('validation'));
        $this->assertNotSame($validation, Environment::get('validation'));
    }

    public function testToString(): void
    {
        $this->assertSame('validation', (string) Environment::get('validation'));
        $this->assertSame('default', (string) Environment::default());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"validation"', json_encode(Environment::get('validation')));
    }

    public function testIsImmutable(): void
    {
        $environment = Environment::get('validation');

        $this->expectException(ImmutableException::class);
        $environment->foo = 'bar';
    }
}
