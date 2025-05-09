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
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Recipe\Step;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Recipe\Step\ListPosts;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListPosts::class)]
class ListPostsTest extends TestCase
{
    private (PostLoader&MockObject)|null $postLoader = null;

    private (DatesService&MockObject)|null $datesService = null;

    private function getPostLoader(): PostLoader&MockObject
    {
        if (!$this->postLoader instanceof PostLoader) {
            $this->postLoader = $this->createMock(PostLoader::class);
        }

        return $this->postLoader;
    }

    private function getDatesService(): DatesService&MockObject
    {
        if (!$this->datesService instanceof DatesService) {
            $this->datesService = $this->createMock(DatesService::class);
        }

        return $this->datesService;
    }

    private function buildStep(): ListPosts
    {
        return new ListPosts(
            $this->getPostLoader(),
            $this->getDatesService(),
        );
    }

    public function testInvokeWithoutTag()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->getDatesService()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function ($callable) {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getpostLoader()
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) {
                    $promise->success(new \ArrayObject([]));

                    return $this->getPostLoader();
                }
            );

        self::assertInstanceOf(
            ListPosts::class,
            $this->buildStep()(
                $manager,
                0,
                1,
                $this->createMock(ParametersBag::class),
            )
        );
    }

    public function testInvokeWithTag()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->getDatesService()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function ($callable) {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getpostLoader()
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) {
                    $promise->success(new \ArrayObject([]));

                    return $this->getPostLoader();
                }
            );

        self::assertInstanceOf(
            ListPosts::class,
            $this->buildStep()(
                $manager,
                0,
                1,
                $this->createMock(ParametersBag::class),
                $this->createMock(Tag::class),
            )
        );
    }
}
