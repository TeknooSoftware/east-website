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

namespace Teknoo\Tests\East\Website\Query\Content;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryElementInterface;
use Teknoo\East\Common\Query\Expr\Lower;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Query\Content\PublishedContentFromSlugQuery;
use Teknoo\Tests\East\Website\Query\QueryElementTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PublishedContentFromSlugQuery::class)]
class PublishedContentFromSlugQueryTest extends TestCase
{
    use QueryElementTestTrait;

    /**
     * @inheritDoc
     */
    public function buildQuery(): QueryElementInterface
    {
        return new PublishedContentFromSlugQuery('fooBar', new DateTimeImmutable('2025-03-24'));
    }

    public function testFetch(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['slug' => 'fooBar', 'publishedAt' => new Lower(new DateTimeImmutable('2025-03-24')), ], $this->callback(fn ($pr): bool => $pr instanceof PromiseInterface));

        $this->assertInstanceOf(PublishedContentFromSlugQuery::class, $this->buildQuery()->fetch($loader, $repository, $promise));
    }

    public function testFetchFailing(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository): \PHPUnit\Framework\MockObject\MockObject {
                $promise->fail(new \RuntimeException());

                return $repository;
            });

        $this->assertInstanceOf(PublishedContentFromSlugQuery::class, $this->buildQuery()->fetch($loader, $repository, $promise));
    }

    public function testFetchNotPublishable(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository): \PHPUnit\Framework\MockObject\MockObject {
                $promise->success(new \stdClass());

                return $repository;
            });

        $this->assertInstanceOf(PublishedContentFromSlugQuery::class, $this->buildQuery()->fetch($loader, $repository, $promise));
    }

    public function testFetchNotPublished(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository): \PHPUnit\Framework\MockObject\MockObject {
                $object = $this->createMock(Content::class);
                $object->method('getPublishedAt')->willReturn(
                    null
                );
                $promise->success($object);

                return $repository;
            });

        $this->assertInstanceOf(PublishedContentFromSlugQuery::class, $this->buildQuery()->fetch($loader, $repository, $promise));
    }

    public function testFetchPublished(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($value): bool => $value instanceof Content));
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository): \PHPUnit\Framework\MockObject\MockObject {
                $content = $this->createMock(Content::class);
                $content->method('getPublishedAt')->willReturn(new \DateTime('2018-07-01'));
                $promise->success($content);

                return $repository;
            });

        $this->assertInstanceOf(PublishedContentFromSlugQuery::class, $this->buildQuery()->fetch($loader, $repository, $promise));
    }
}
