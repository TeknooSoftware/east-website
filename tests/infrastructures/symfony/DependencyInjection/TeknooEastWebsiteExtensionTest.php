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

namespace Teknoo\Tests\East\WebsiteBundle\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Teknoo\East\WebsiteBundle\DependencyInjection\TeknooEastWebsiteExtension;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TeknooEastWebsiteExtension::class)]
class TeknooEastWebsiteExtensionTest extends TestCase
{
    private (ContainerBuilder&MockObject)|(ContainerBuilder&Stub)|null $container = null;

    private function getContainerBuilderMock(bool $stub = false): (ContainerBuilder&Stub)|(ContainerBuilder&MockObject)
    {
        if (!$this->container instanceof ContainerBuilder) {
            if ($stub) {
                $this->container = $this->createStub(ContainerBuilder::class);
            } else {
                $this->container = $this->createMock(ContainerBuilder::class);
            }
        }

        return $this->container;
    }

    private function buildExtension(): TeknooEastWebsiteExtension
    {
        return new TeknooEastWebsiteExtension();
    }

    private function getExtensionClass(): string
    {
        return TeknooEastWebsiteExtension::class;
    }

    public function testLoad(): void
    {
        $this->buildExtension()->load([], $this->getContainerBuilderMock(true));
        $this->assertTrue(true);
    }

    public function testLoadErrorContainer(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildExtension()->load([], new \stdClass());
    }

    public function testLoadErrorConfig(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildExtension()->load(new \stdClass(), $this->getContainerBuilderMock(true));
    }
}
