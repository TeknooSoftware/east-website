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

use BadMethodCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentNotFoundException;
use Teknoo\East\Website\Object\Environments;

use function iterator_to_array;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Environments::class)]
class EnvironmentsTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::reset();
        parent::tearDown();
    }

    public function testEmptyCollectionHasTheDefaultEnvironment(): void
    {
        $environments = new Environments();

        $this->assertCount(1, $environments);
        $this->assertTrue($environments->has('default'));
        $this->assertFalse($environments->has('validation'));
        $this->assertSame(Environment::default(), $environments->get('default'));
        $this->assertSame(['default' => Environment::default()], $environments->toArray());
    }

    public function testCollection(): void
    {
        $validation = Environment::define('validation', Environment::default());
        $testing = Environment::define('testing', Environment::default());

        $environments = new Environments([$validation, $testing]);

        $this->assertCount(3, $environments);
        $this->assertTrue($environments->has('validation'));
        $this->assertTrue(isset($environments['testing']));
        $this->assertFalse(isset($environments['foo']));
        $this->assertSame($validation, $environments->get('validation'));
        $this->assertSame($testing, $environments['testing']);
        $this->assertSame(
            ['default' => Environment::default(), 'validation' => $validation, 'testing' => $testing],
            $environments->toArray(),
        );
        $this->assertSame(
            ['default' => Environment::default(), 'validation' => $validation, 'testing' => $testing],
            iterator_to_array($environments),
        );
    }

    public function testGetAnUndefinedEnvironment(): void
    {
        $this->expectException(EnvironmentNotFoundException::class);
        (new Environments())->get('validation');
    }

    public function testOffsetGetAnUndefinedEnvironment(): void
    {
        $this->expectException(EnvironmentNotFoundException::class);
        (new Environments())['validation'];
    }

    public function testOffsetSetIsForbidden(): void
    {
        $environments = new Environments();

        $this->expectException(BadMethodCallException::class);
        $environments['validation'] = Environment::get('validation');
    }

    public function testOffsetUnsetIsForbidden(): void
    {
        $environments = new Environments();

        $this->expectException(BadMethodCallException::class);
        unset($environments['default']);
    }
}
