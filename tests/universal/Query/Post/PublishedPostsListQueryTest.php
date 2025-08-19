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

namespace Teknoo\Tests\East\Website\Query\Post;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\East\Common\Query\Expr\Lower;
use Teknoo\East\Website\Query\Post\PublishedPostsListQuery;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\Tests\East\Website\Query\QueryCollectionTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PublishedPostsListQuery::class)]
class PublishedPostsListQueryTest extends TestCase
{
    use QueryCollectionTestTrait;

    /**
     * @inheritDoc
     */
    public function buildQuery(): QueryCollectionInterface
    {
        return new PublishedPostsListQuery(new DateTimeImmutable('2025-03-24'), 10, 3);
    }

    public function testExecute(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->once())
            ->method('success')
            ->with(
                self::callback(
                    fn ($r): bool => $r instanceof \Countable
                        && $r instanceof \IteratorAggregate
                        && 20 === $r->count()
                        && $r->getIterator() instanceof \Iterator
                )
            );
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('count')
            ->with(
                ['publishedAt' => new Lower(new DateTimeImmutable('2025-03-24')),],
                self::callback(
                    fn ($p): bool => $p instanceof PromiseInterface
                )
            )->willReturnCallback(
                function (array $criteria, PromiseInterface $promise) use ($repository): \PHPUnit\Framework\MockObject\MockObject {
                    $promise->success(20);

                    return $repository;
                }
            );

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['publishedAt' => new Lower(new DateTimeImmutable('2025-03-24'))], )
            ->willReturnCallback(
                function (array $criteria, PromiseInterface $promise) use ($repository): \PHPUnit\Framework\MockObject\MockObject {
                    $promise->success($this->createMock(\Iterator::class));

                    return $repository;
                }
            );
        ;

        $this->assertInstanceOf(PublishedPostsListQuery::class, $this->buildQuery()->execute($loader, $repository, $promise));
    }
}
