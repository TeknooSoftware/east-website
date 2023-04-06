<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
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

namespace Teknoo\Tests\East\WebsiteBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Teknoo\East\WebsiteBundle\DependencyInjection\TeknooEastWebsiteExtension;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers      \Teknoo\East\WebsiteBundle\DependencyInjection\TeknooEastWebsiteExtension
 */
class TeknooEastWebsiteExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    private function getContainerBuilderMock(): \ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
    {
        if (!$this->container instanceof ContainerBuilder) {
            $this->container = $this->createMock(ContainerBuilder::class);
        }

        return $this->container;
    }

    /**
     * @return TeknooEastWebsiteExtension
     */
    private function buildExtension(): TeknooEastWebsiteExtension
    {
        return new TeknooEastWebsiteExtension();
    }

    private function getExtensionClass(): string
    {
        return TeknooEastWebsiteExtension::class;
    }

    public function testLoad()
    {
        self::assertInstanceOf(
            $this->getExtensionClass(),
            $this->buildExtension()->load([], $this->getContainerBuilderMock())
        );
    }

    public function testLoadErrorContainer()
    {
        $this->expectException(\TypeError::class);
        $this->buildExtension()->load([], new \stdClass());
    }

    public function testLoadErrorConfig()
    {
        $this->expectException(\TypeError::class);
        $this->buildExtension()->load(new \stdClass(), $this->getContainerBuilderMock());
    }
}
