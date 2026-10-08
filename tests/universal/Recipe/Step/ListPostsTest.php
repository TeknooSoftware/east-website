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
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Recipe\Step\ListPosts;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Query\Post\PublishedPostsListInTagQuery;
use Teknoo\East\Website\Query\Post\PublishedPostsListQuery;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListPosts::class)]
class ListPostsTest extends TestCase
{
    private (PostLoader&Stub)|(PostLoader&MockObject)|null $postLoader = null;

    private (DatesService&Stub)|(DatesService&MockObject)|null $datesService = null;

    private function getPostLoader(bool $stub = false): (PostLoader&Stub)|(PostLoader&MockObject)
    {
        if (!$this->postLoader instanceof PostLoader) {
            if ($stub) {
                $this->postLoader = $this->createStub(PostLoader::class);
            } else {
                $this->postLoader = $this->createMock(PostLoader::class);
            }
        }

        return $this->postLoader;
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

    private function buildStep(): ListPosts
    {
        return new ListPosts(
            $this->getPostLoader(true),
            $this->getDatesService(true),
        );
    }

    public function testInvokeWithoutTag(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getpostLoader(true)
            ->method('query')
            ->willReturnCallback(
                function (QueryCollectionInterface $query, PromiseInterface $promise): PostLoader&Stub {
                    $promise->success(new ArrayObject([]));

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(ListPosts::class, $this->buildStep()(
            $manager,
            0,
            1,
            $this->createStub(ParametersBag::class),
        ));
    }

    public function testInvokeInjectPaginationIntoTheBag(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan')->with(
            [
                'postsCollection' => $posts = new ArrayObject([1, 2, 3]),
                'pageCount' => 2,
            ]
        );

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getpostLoader(true)
            ->method('query')
            ->willReturnCallback(
                function (QueryCollectionInterface $query, PromiseInterface $promise) use ($posts): PostLoader&Stub {
                    $promise->success($posts);

                    return $this->getPostLoader();
                }
            );

        $bag = new ParametersBag();

        $this->assertInstanceOf(ListPosts::class, $this->buildStep()(
            $manager,
            2,
            2,
            $bag,
        ));

        $this->assertEquals(
            [
                'postsCollection' => $posts,
                'page' => 2,
                'pageCount' => 2,
            ],
            $bag->transform(),
        );
    }

    public function testInvokeWithTag(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getpostLoader(true)
            ->method('query')
            ->willReturnCallback(
                function (QueryCollectionInterface $query, PromiseInterface $promise): PostLoader&Stub {
                    $promise->success(new ArrayObject([]));

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(ListPosts::class, $this->buildStep()(
            $manager,
            0,
            1,
            $this->createStub(ParametersBag::class),
            $this->createStub(Tag::class),
        ));
    }
    public function testInvokeWithEnvironment(): void
    {
        $validation = Environment::define('validation', Environment::default());

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader()
            ->expects($this->once())
            ->method('query')
            ->with(new PublishedPostsListQuery(new DateTimeImmutable('2025-03-24'), 10, 10, $validation))
            ->willReturnCallback(
                function (QueryCollectionInterface $query, PromiseInterface $promise): PostLoader {
                    $promise->success(new ArrayObject([]));

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(ListPosts::class, $this->buildStep()(
            $manager,
            10,
            2,
            $this->createStub(ParametersBag::class),
            null,
            $validation,
        ));

        Environment::reset();
    }

    public function testInvokeWithTagAndEnvironment(): void
    {
        $validation = Environment::define('validation', Environment::default());
        $tag = new Tag();

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan');

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader()
            ->expects($this->once())
            ->method('query')
            ->with(new PublishedPostsListInTagQuery($tag, new DateTimeImmutable('2025-03-24'), 10, 0, $validation))
            ->willReturnCallback(
                function (QueryCollectionInterface $query, PromiseInterface $promise): PostLoader {
                    $promise->success(new ArrayObject([]));

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(ListPosts::class, $this->buildStep()(
            $manager,
            10,
            1,
            $this->createStub(ParametersBag::class),
            $tag,
            $validation,
        ));

        Environment::reset();
    }
}
