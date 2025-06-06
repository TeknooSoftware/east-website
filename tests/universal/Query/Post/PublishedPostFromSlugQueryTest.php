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

namespace Teknoo\Tests\East\Website\Query\Post;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryElementInterface;
use Teknoo\East\Common\Query\Expr\Lower;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Query\Post\PublishedPostFromSlugQuery;
use Teknoo\Tests\East\Website\Query\QueryElementTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PublishedPostFromSlugQuery::class)]
class PublishedPostFromSlugQueryTest extends TestCase
{
    use QueryElementTestTrait;

    /**
     * @inheritDoc
     */
    public function buildQuery(): QueryElementInterface
    {
        return new PublishedPostFromSlugQuery('fooBar', new DateTimeImmutable('2025-03-24'));
    }

    public function testFetch()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['slug' => 'fooBar', 'publishedAt' => new Lower(new DateTimeImmutable('2025-03-24')),], $this->callback(fn($pr) => $pr instanceof PromiseInterface));

        self::assertInstanceOf(
            PublishedPostFromSlugQuery::class,
            $this->buildQuery()->fetch($loader, $repository, $promise)
        );
    }

    public function testFetchFailing()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository) {
                $promise->fail(new \RuntimeException());

                return $repository;
            });

        self::assertInstanceOf(
            PublishedPostFromSlugQuery::class,
            $this->buildQuery()->fetch($loader, $repository, $promise)
        );
    }

    public function testFetchNotPublishable()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository) {
                $promise->success(new \stdClass());

                return $repository;
            });

        self::assertInstanceOf(
            PublishedPostFromSlugQuery::class,
            $this->buildQuery()->fetch($loader, $repository, $promise)
        );
    }

    public function testFetchNotPublished()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository) {
                $object = $this->createMock(Post::class);
                $object->expects($this->any())->method('getPublishedAt')->willReturn(
                    null
                );
                $promise->success($object);

                return $repository;
            });

        self::assertInstanceOf(
            PublishedPostFromSlugQuery::class,
            $this->buildQuery()->fetch($loader, $repository, $promise)
        );
    }

    public function testFetchPublished()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn($value) => $value instanceof Post));
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($repository) {
                $post = $this->createMock(Post::class);
                $post->expects($this->any())->method('getPublishedAt')->willReturn(new \DateTime('2018-07-01'));
                $promise->success($post);

                return $repository;
            });

        self::assertInstanceOf(
            PublishedPostFromSlugQuery::class,
            $this->buildQuery()->fetch($loader, $repository, $promise)
        );
    }
}
