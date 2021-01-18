<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Recipe\Step;

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Website\Loader\LoaderInterface;
use Teknoo\East\Website\Query\PaginationQuery;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class LoadListObjects
{
    public function __invoke(
        LoaderInterface $loader,
        ManagerInterface $manager,
        array $order,
        int $itemsPerPage,
        int $page
    ): self {
        $loader->query(
            new PaginationQuery([], $order, $itemsPerPage, ($page - 1) * $itemsPerPage),
            new Promise(
                static function ($objects) use ($itemsPerPage, $manager) {
                    $pageCount = 1;
                    if ($objects instanceof \Countable) {
                        $pageCount = (int) \ceil($objects->count() / $itemsPerPage);
                    }

                    $manager->updateWorkPlan(
                        [
                            'objectsCollection' => $objects,
                            'pageCount' => $pageCount
                        ]
                    );
                },
                [$manager, 'error']
            )
        );

        return $this;
    }
}
