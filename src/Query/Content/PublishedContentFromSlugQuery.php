<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Query\Content;

use DateTimeInterface;
use DomainException;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Common\Contracts\Object\PublishableInterface;
use Teknoo\East\Common\Contracts\Query\QueryElementInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Class implementing query to load a non soft-deleted Content instance from its slug, and pass result to the
 * passed promise.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements QueryElementInterface<Content>
 */
class PublishedContentFromSlugQuery implements QueryElementInterface, ImmutableInterface
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $slug,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function fetch(
        LoaderInterface $loader,
        RepositoryInterface $repository,
        PromiseInterface $promise
    ): QueryElementInterface {
        /** @var Promise<Content, mixed, Content> $fetchingPromise */
        $fetchingPromise = new Promise(
            onSuccess: static function ($object, PromiseInterface $next): void {
                if (
                    $object instanceof PublishableInterface
                    && $object->getPublishedAt() instanceof DateTimeInterface
                ) {
                    $next->success($object);
                } else {
                    $next->fail(new DomainException('Content not found', 404));
                }
            },
            allowNext: true
        );

        $repository->findOneBy(
            [
                'slug' => $this->slug,
                'deletedAt' => null,
            ],
            $fetchingPromise->next(
                promise: $promise,
                autoCall: true,
            ),
        );

        return $this;
    }
}
