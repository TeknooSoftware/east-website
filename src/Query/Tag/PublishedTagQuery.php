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

namespace Teknoo\East\Website\Query\Tag;

use DateTimeInterface;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\East\Common\Query\Enum\Direction;
use Teknoo\East\Common\Query\Expr\In;
use Teknoo\East\Common\Query\Expr\Lower;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Object\Tag;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Class implementing query to list all non deleted tags used at least into a published post
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements QueryCollectionInterface<Tag>
 */
class PublishedTagQuery implements QueryCollectionInterface, ImmutableInterface
{
    use ImmutableTrait;

    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly DateTimeInterface $now,
    ) {
        $this->uniqueConstructorCheck();
    }

    /**
     * @return QueryCollectionInterface<Tag>
     */
    public function execute(
        LoaderInterface $loader,
        RepositoryInterface $repository,
        PromiseInterface $promise
    ): QueryCollectionInterface {
        /** @var Promise<array<Tag>, mixed, mixed> $distinctPromise */
        $distinctPromise = new Promise(
            function (array $tags) use ($repository, $promise): void {
                /** @var array<int, string> $tags */
                if (empty($tags)) {
                    $promise->success([]);

                    return;
                }

                $repository->findBy(
                    criteria: [
                        'id' => new In($tags)
                    ],
                    promise: $promise,
                    orderBy: [
                        'name' => Direction::Asc,
                    ],
                );
            },
            $promise->fail(...),
        );

        $this->postRepository->distinctBy(
            'tags.id',
            [
                'publishedAt' => new Lower($this->now),
            ],
            $distinctPromise,
        );

        return $this;
    }
}
