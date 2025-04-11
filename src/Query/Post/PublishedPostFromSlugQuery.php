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

use DateTimeInterface;
use DomainException;
use Teknoo\East\Common\Query\Expr\Lower;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Common\Contracts\Object\PublishableInterface;
use Teknoo\East\Common\Contracts\Query\QueryElementInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Class implementing query to load a non soft-deleted Post instance from its slug, and pass result to the
 * passed promise.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements QueryElementInterface<Post>
 */
class PublishedPostFromSlugQuery implements QueryElementInterface, ImmutableInterface
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $slug,
        private readonly DateTimeInterface $now,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function fetch(
        LoaderInterface $loader,
        RepositoryInterface $repository,
        PromiseInterface $promise
    ): QueryElementInterface {
        /** @var Promise<Post, mixed, Post> $fetchingPromise */
        $fetchingPromise = new Promise(
            onSuccess: static function ($object, PromiseInterface $next): void {
                if (
                    $object instanceof PublishableInterface
                    && $object->getPublishedAt() instanceof DateTimeInterface
                ) {
                    $next->success($object);
                } else {
                    $next->fail(new DomainException('Post not found', 404));
                }
            },
            allowNext: true
        );

        $repository->findOneBy(
            criteria: [
                'slug' => $this->slug,
                'publishedAt' => new Lower($this->now),
            ],
            promise: $fetchingPromise->next(
                promise: $promise,
                autoCall: true,
            ),
            hydrate: [
                'tags',
            ]
        );

        return $this;
    }
}
