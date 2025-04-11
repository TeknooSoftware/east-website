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

namespace Teknoo\Tests\East\Website\Query\Tag;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\East\Common\Query\Expr\In;
use Teknoo\East\Common\Query\Expr\Lower;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Query\Tag\PublishedTagQuery;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\Tests\East\Website\Query\QueryCollectionTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PublishedTagQuery::class)]
class PublishedTagQueryTest extends TestCase
{
    use QueryCollectionTestTrait;

    private (PostRepositoryInterface&MockObject)|null $postRepository = null;

    private function getPostRepository(): PostRepositoryInterface&MockObject
    {
        if (!$this->postRepository instanceof PostRepositoryInterface) {
            $this->postRepository = $this->createMock(PostRepositoryInterface::class);
        }

        return $this->postRepository;
    }

    /**
     * @inheritDoc
     */
    public function buildQuery(): QueryCollectionInterface
    {
        return new PublishedTagQuery(
            $this->getPostRepository(),
            new DateTimeImmutable('2025-03-24')
        );
    }

    public function testExecute()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'id' => new In(['foo', 'bar'])
                ],
                $promise,
            );

        $this->getPostRepository()
            ->expects($this->once())
            ->method('distinctBy')->willReturnCallback(
                function ($field, $criteria, $aPromise) {
                    self::assertEquals('tags.id', $field);
                    self::assertEquals(
                        [
                            'publishedAt' => new Lower(new DateTimeImmutable('2025-03-24')),
                        ],
                        $criteria
                    );

                    $aPromise->success(['foo', 'bar']);

                    return $this->getPostRepository();
                }
            );

        self::assertInstanceOf(
            PublishedTagQuery::class,
            $this->buildQuery()->execute($loader, $repository, $promise)
        );
    }

    public function testExecuteWithNoTags()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->never())
            ->method('findBy');

        $this->getPostRepository()
            ->expects($this->once())
            ->method('distinctBy')->willReturnCallback(
                function ($field, $criteria, $aPromise) {
                    self::assertEquals('tags.id', $field);
                    self::assertEquals(
                        [
                            'publishedAt' => new Lower(new DateTimeImmutable('2025-03-24')),
                        ],
                        $criteria
                    );

                    $aPromise->success([]);

                    return $this->getPostRepository();
                }
            );

        self::assertInstanceOf(
            PublishedTagQuery::class,
            $this->buildQuery()->execute($loader, $repository, $promise)
        );
    }
}
