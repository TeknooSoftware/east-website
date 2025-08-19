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

namespace Teknoo\East\Website\Recipe\Step;

use Countable;
use DateTimeInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Query\Post\PublishedPostsListInTagQuery;
use Teknoo\East\Website\Query\Post\PublishedPostsListQuery;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Website\Loader\PostLoader;
use Throwable;

use function ceil;

/**
 * Step to list all published post at the current date returned by the `DatesService`. If a Tag instance is passed, the
 * list of posts will be filtered on it. A pagination is also available, item per page and the page can be defined via
 * `$itemsPerPage` and `page`.
 * This step use the `PostLoader` and the query `PublishedPostsListQuery` or `PublishedPostsListInTagQuery` if a Tag
 * instance is passed.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ListPosts
{
    public function __construct(
        private readonly PostLoader $postLoader,
        private readonly DatesService $datesService,
    ) {
    }

    public function __invoke(
        ManagerInterface $manager,
        int $itemsPerPage,
        int $page,
        ParametersBag $bag,
        ?Tag $tag = null,
    ): self {
        if ($itemsPerPage < 1) {
            $itemsPerPage = 1;
        }

        /** @var Promise<iterable<Post>, mixed, mixed> $promise */
        $promise = new Promise(
            static function (iterable $posts) use ($itemsPerPage, $manager, $bag): void {
                $pageCount = 1;
                if ($posts instanceof Countable) {
                    $pageCount = (int) ceil($posts->count() / $itemsPerPage);
                }

                $manager->updateWorkPlan(
                    [
                        'postsCollection' => $posts,
                        'pageCount' => $pageCount,
                    ],
                );

                $bag->set('postsCollection', $posts);
                $bag->set('pageCount', $pageCount);
            },
            static fn (Throwable $throwable): ChefInterface => $manager->error($throwable),
        );

        $this->datesService->passMeTheDate(
            function (DateTimeInterface $now) use ($tag, $promise, $itemsPerPage, $page): void {
                if (null === $tag) {
                    $query = new PublishedPostsListQuery(
                        $now,
                        $itemsPerPage,
                        ($page - 1) * $itemsPerPage,
                    );
                } else {
                    $query = new PublishedPostsListInTagQuery(
                        $tag,
                        $now,
                        $itemsPerPage,
                        ($page - 1) * $itemsPerPage,
                    );
                }

                $this->datesService->passMeTheDate(
                    fn (DateTimeInterface $date): LoaderInterface => $this->postLoader->query($query, $promise),
                );
            }
        );

        return $this;
    }
}
