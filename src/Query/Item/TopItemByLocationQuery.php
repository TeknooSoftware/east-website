<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

use function is_array;

/**
 * Class implementing query to load first level items about a menu, ordered by their position (ascendant order)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
            $locations = new In($locations);
        }

        $repository->findBy(
            criteria: [
                'location' => $locations,
                'deletedAt' => null,
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
