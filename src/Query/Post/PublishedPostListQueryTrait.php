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

namespace Teknoo\East\Website\Query\Post;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\East\Common\Query\Enum\Direction;
use Teknoo\East\Website\Object\Post;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Traversable;

use function is_array;

/**
 * Trait used by PublishedPostsListInTagQuery and PublishedPostsListQuery to execute a paginated query about a list of
 * published posts
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait PublishedPostListQueryTrait
{
    /**
     * @param array<string, mixed> $criteria
     * @param array<string, Direction> $orderBy
     * @param PromiseInterface<iterable<Post>, mixed> $promise
     * @param RepositoryInterface<Post> $repository
     * @return QueryCollectionInterface<Post>
     */
    private function doQuery(
        array $criteria,
        array $orderBy,
        int $limit,
        int $offset,
        RepositoryInterface $repository,
        PromiseInterface $promise
    ): QueryCollectionInterface {
        /** @var Promise<iterable<Post>, mixed, mixed> $findPromise */
        $findPromise = new Promise(
            static function ($result) use ($criteria, $promise, $repository): void {
                /** @var Promise<int, mixed, mixed> $countPromise */
                $countPromise = new Promise(
                    static function (int $count) use ($promise, $result): void {
                        if (is_array($result)) {
                            $result = new ArrayIterator($result);
                        }

                        $iterator = new class ($count, $result) implements Countable, IteratorAggregate {
                            /**
                             * @param Traversable<Post> $iterator
                             */
                            public function __construct(
                                private readonly int $count,
                                private readonly Traversable $iterator,
                            ) {
                            }

                            /**
                            * @return Traversable<Post>
                            */
                            public function getIterator(): Traversable
                            {
                                return $this->iterator;
                            }

                            public function count(): int
                            {
                                return $this->count;
                            }
                        };

                        $promise->success($iterator);
                    },
                    $promise->fail(...)
                );

                $repository->count(
                    $criteria,
                    $countPromise,
                );
            },
            $promise->fail(...)
        );

        $repository->findBy(
            criteria: $criteria,
            promise: $findPromise,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
            hydrate: ['tags'],
        );

        return $this;
    }
}
