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
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListTags::class)]
class ListTagsTest extends TestCase
{
    private (TagLoader&MockObject)|null $tagLoader = null;
    private (PostRepositoryInterface&MockObject)|null $postRepository = null;
    private (DatesService&MockObject)|null $datesService = null;

    private function getTagLoader(): TagLoader&MockObject
    {
        if (!$this->tagLoader instanceof TagLoader) {
            $this->tagLoader = $this->createMock(TagLoader::class);
        }

        return $this->tagLoader;
    }

    private function getPostRepository(): PostRepositoryInterface&MockObject
    {
        if (!$this->postRepository instanceof PostRepositoryInterface) {
            $this->postRepository = $this->createMock(PostRepositoryInterface::class);
        }

        return $this->postRepository;
    }

    private function getDatesService(): DatesService&MockObject
    {
        if (!$this->datesService instanceof DatesService) {
            $this->datesService = $this->createMock(DatesService::class);
        }

        return $this->datesService;
    }

    private function buildStep(): ListTags
    {
        return new ListTags(
            $this->getTagLoader(),
            $this->getPostRepository(),
            $this->getDatesService(),
        );
    }

    public function testInvokeWithoutTag()
    {
        $this->getDatesService()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function ($callable) {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->gettagLoader()
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) {
                    $promise->success(new \ArrayObject([]));

                    return $this->getTagLoader();
                }
            );

        self::assertInstanceOf(
            ListTags::class,
            $this->buildStep()(
                $manager,
                $this->createMock(ParametersBag::class),
            )
        );
    }
}
