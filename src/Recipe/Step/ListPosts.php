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

namespace Teknoo\East\Website\Recipe\Step;

use Countable;
use DateTimeInterface;
use DomainException;
use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Common\Query\Exception\LoadingException;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Query\Post\PublishedPostsListInTagQuery;
use Teknoo\East\Website\Query\Post\PublishedPostsListQuery;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Query\Post\PublishedPostFromSlugQuery;
use Throwable;

use function ceil;

/**
 * Step recipe to load a published Post instance, from its slug, thank to the Post's loader and put it into the
 * workplan at Post::class key, and `objectInstance`. The template file to use with the fetched post is also
 * injected to the template.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
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
        ?Tag $tag = null,
    ): self {
        $promise = new Promise(
            static function (iterable $posts) use ($itemsPerPage, $manager): void {
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
                    fn (DateTimeInterface $date) => $this->postLoader->query($query, $promise),
                );
            }
        );

        return $this;
    }
}
