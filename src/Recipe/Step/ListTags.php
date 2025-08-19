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

use DateTimeInterface;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Query\Tag\PublishedTagQuery;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step to list all tags used at least by a published post at the current date returned by the `DatesService`. This
 * step use the `TagLoader` and the query `PublishedTagQuery`
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ListTags
{
    public function __construct(
        private readonly TagLoader $tagLoader,
        private readonly PostRepositoryInterface $postRepository,
        private readonly DatesService $datesService,
    ) {
    }

    public function __invoke(
        ManagerInterface $manager,
        ParametersBag $bag,
    ): self {
        /** @var Promise<iterable<Tag>, mixed, mixed> $promise */
        $promise = new Promise(
            static function (iterable $tags) use ($manager, $bag): void {
                $manager->updateWorkPlan(['tagsCollection' => $tags]);
                $bag->set('tagsCollection', $tags);
            },
            static fn (Throwable $throwable): ChefInterface => $manager->error($throwable),
        );

        $this->datesService->passMeTheDate(
            function (DateTimeInterface $now) use ($promise): void {
                $this->tagLoader->query(
                    new PublishedTagQuery(
                        $this->postRepository,
                        $now,
                    ),
                    $promise,
                );
            }
        );

        return $this;
    }
}
