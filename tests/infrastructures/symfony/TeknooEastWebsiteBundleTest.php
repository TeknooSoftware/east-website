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

namespace Teknoo\Tests\East\WebsiteBundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Teknoo\East\Website\Object\Environments;
use Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TeknooEastWebsiteBundle::class)]
class TeknooEastWebsiteBundleTest extends TestCase
{
    public function testBootWithoutContainer(): void
    {
        $bundle = new TeknooEastWebsiteBundle();
        $bundle->boot();

        $this->assertTrue(true);
    }

    public function testBootWithoutEnvironmentsService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('teknoo.east.website.environments')
            ->willReturn(false);
        $container->expects($this->never())->method('get');

        $bundle = new TeknooEastWebsiteBundle();
        $bundle->setContainer($container);
        $bundle->boot();
    }

    public function testBootLoadsTheEnvironments(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('teknoo.east.website.environments')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('teknoo.east.website.environments')
            ->willReturn(new Environments());

        $bundle = new TeknooEastWebsiteBundle();
        $bundle->setContainer($container);
        $bundle->boot();
    }
}
