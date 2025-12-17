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

namespace Teknoo\Tests\East\Website\Recipe\Step;

use ArrayObject;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListTags::class)]
class ListTagsTest extends TestCase
{
    private (TagLoader&Stub)|(TagLoader&MockObject)|null $tagLoader = null;

    private (PostRepositoryInterface&Stub)|(PostRepositoryInterface&MockObject)|null $postRepository = null;

    private (DatesService&Stub)|(DatesService&MockObject)|null $datesService = null;

    private function getTagLoader(bool $stub = false): (TagLoader&Stub)|(TagLoader&MockObject)
    {
        if (!$this->tagLoader instanceof TagLoader) {
            if ($stub) {
                $this->tagLoader = $this->createStub(TagLoader::class);
            } else {
                $this->tagLoader = $this->createMock(TagLoader::class);
            }
        }

        return $this->tagLoader;
    }

    private function getPostRepository(bool $stub = false): (PostRepositoryInterface&Stub)|(PostRepositoryInterface&MockObject)
    {
        if (!$this->postRepository instanceof PostRepositoryInterface) {
            if ($stub) {
                $this->postRepository = $this->createStub(PostRepositoryInterface::class);
            } else {
                $this->postRepository = $this->createMock(PostRepositoryInterface::class);
            }
        }

        return $this->postRepository;
    }

    private function getDatesService(bool $stub = false): (DatesService&Stub)|(DatesService&MockObject)
    {
        if (!$this->datesService instanceof DatesService) {
            if ($stub) {
                $this->datesService = $this->createStub(DatesService::class);
            } else {
                $this->datesService = $this->createMock(DatesService::class);
            }
        }

        return $this->datesService;
    }

    private function buildStep(): ListTags
    {
        return new ListTags(
            $this->getTagLoader(true),
            $this->getPostRepository(true),
            $this->getDatesService(true),
        );
    }

    public function testInvokeWithoutTag(): void
    {
        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->gettagLoader(true)
            ->method('query')
            ->willReturnCallback(
                function (QueryCollectionInterface $query, PromiseInterface $promise): TagLoader&Stub {
                    $promise->success(new ArrayObject([]));

                    return $this->getTagLoader();
                }
            );

        $this->assertInstanceOf(ListTags::class, $this->buildStep()(
            $manager,
            $this->createStub(ParametersBag::class),
        ));
    }
}
