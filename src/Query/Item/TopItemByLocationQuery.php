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

namespace Teknoo\East\Website\Query\Item;

use Teknoo\East\Common\Query\Enum\Direction;
use Teknoo\East\Common\Query\Expr\In;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Item;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function array_values;
use function is_array;

/**
 * Class implementing query to load first level items about a menu, ordered by their position (ascendant order)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements QueryCollectionInterface<Item>
 */
class TopItemByLocationQuery implements QueryCollectionInterface, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param string|array<string> $location
     */
    public function __construct(
        private readonly string|array $location,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function execute(
        LoaderInterface $loader,
        RepositoryInterface $repository,
        PromiseInterface $promise
    ): QueryCollectionInterface {
        $locations = $this->location;
        if (is_array($locations)) {
            $locations = new In(array_values($locations));
        }

        $repository->findBy(
            criteria: [
                'location' => $locations,
            ],
            promise: $promise,
            orderBy: [
                'parent' => Direction::Asc,
                'position' => Direction::Asc,
            ],
            hydrate: ['content'],
        );

        return $this;
    }
}
