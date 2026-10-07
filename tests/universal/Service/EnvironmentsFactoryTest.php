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

namespace Teknoo\Tests\East\Website\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environment\Exception\CyclicEnvironmentException;
use Teknoo\East\Website\Object\Environment\Exception\InvalidEnvironmentNameException;
use Teknoo\East\Website\Object\Environment\Exception\UnknownParentEnvironmentException;
use Teknoo\East\Website\Object\Environments;
use Teknoo\East\Website\Service\EnvironmentsFactory;

use function array_keys;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(EnvironmentsFactory::class)]
class EnvironmentsFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::reset();
        parent::tearDown();
    }

    public function testFromEmptyDefinitions(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions([]);
        $this->assertInstanceOf(Environments::class, $environments);
        $this->assertSame(['default' => Environment::default()], $environments->toArray());
    }

    public function testFromDefinitionsInAnyOrder(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions([
            'test-b' => 'testing',
            'validation' => 'default',
            'test-a' => 'testing',
            'testing' => 'default',
        ]);

        $this->assertSame(['default', 'testing', 'test-b', 'validation', 'test-a'], array_keys($environments->toArray()));
        $this->assertSame(Environment::default(), $environments['default']);
        $this->assertSame(Environment::get('test-a'), $environments['test-a']);
        $this->assertSame(['test-a', 'testing', 'default'], [...$environments['test-a']->getChain()]);
        $this->assertSame(['test-b', 'testing', 'default'], [...$environments['test-b']->getChain()]);
        $this->assertSame(['validation', 'default'], [...$environments['validation']->getChain()]);
        $this->assertTrue(Environment::isDefined('test-b'));
    }

    public function testFromDefinitionsWithNonStringName(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::fromDefinitions([0 => 'default']);
    }

    public function testFromDefinitionsWithNonStringParent(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::fromDefinitions(['validation' => ['default']]);
    }

    public function testFromDefinitionsRedefiningDefault(): void
    {
        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::fromDefinitions(['default' => 'validation']);
    }

    public function testFromDefinitionsWithUnknownParent(): void
    {
        $this->expectException(UnknownParentEnvironmentException::class);
        EnvironmentsFactory::fromDefinitions(['validation' => 'foo']);
    }

    public function testFromDefinitionsWithCycle(): void
    {
        $this->expectException(CyclicEnvironmentException::class);
        EnvironmentsFactory::fromDefinitions(['a' => 'b', 'b' => 'c', 'c' => 'a']);
    }

    public function testFromDefinitionsWithItselfAsParent(): void
    {
        $this->expectException(CyclicEnvironmentException::class);
        EnvironmentsFactory::fromDefinitions(['a' => 'a']);
    }

    public function testValidateAccess(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default', 'testing' => 'default']);

        $this->assertSame(
            ['validation' => ['ROLE_ADMIN', 'ROLE_VALIDATOR']],
            EnvironmentsFactory::validateAccess(
                $environments,
                ['validation' => ['foo' => 'ROLE_ADMIN', 'bar' => 'ROLE_VALIDATOR']],
            ),
        );

        $this->assertSame([], EnvironmentsFactory::validateAccess($environments, []));
    }

    public function testValidateAccessWithNonStringName(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default']);

        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::validateAccess($environments, [0 => ['ROLE_ADMIN']]);
    }

    public function testValidateAccessWithNonArrayRoles(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default']);

        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::validateAccess($environments, ['validation' => 'ROLE_ADMIN']);
    }

    public function testValidateAccessWithNonStringRole(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default']);

        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::validateAccess($environments, ['validation' => ['ROLE_ADMIN', 123]]);
    }

    public function testValidateAccessWithEmptyRole(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default']);

        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::validateAccess($environments, ['validation' => ['']]);
    }

    public function testValidateAccessOnDefault(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default']);

        $this->expectException(InvalidEnvironmentNameException::class);
        EnvironmentsFactory::validateAccess($environments, ['default' => ['ROLE_ADMIN']]);
    }

    public function testValidateAccessOnUndefinedEnvironment(): void
    {
        $environments = EnvironmentsFactory::fromDefinitions(['validation' => 'default']);

        $this->expectException(UnknownParentEnvironmentException::class);
        EnvironmentsFactory::validateAccess($environments, ['testing' => ['ROLE_ADMIN']]);
    }
}
