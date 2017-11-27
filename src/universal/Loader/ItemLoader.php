<?php

declare(strict_types=1);

/**
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
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website\Loader;

use Doctrine\Common\Persistence\ObjectRepository;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\Object\Item;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ItemLoader implements LoaderInterface
{
    use CollectionLoaderTrait;

    /**
     * ItemLoader constructor.
     * @param ObjectRepository $repository
     */
    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $criteria
     * @param PromiseInterface $promise
     * @return LoaderInterface
     */
    public function load(array $criteria, PromiseInterface $promise): LoaderInterface
    {
        $criteria['deletedAt'] = null;
        $entity = $this->repository->findOneBy($criteria);

        if ($entity instanceof Item) {
            $promise->success($entity);
        } else {
            $promise->fail(new \DomainException('Item not found'));
        }

        return $this;
    }

    /**
     * @param string $location
     * @param PromiseInterface $promise
     * @return ItemLoader|LoaderInterface
     */
    public function topByLocation(string $location, PromiseInterface $promise): ItemLoader
    {
        return $this->loadCollection(
            [
                'location' => $location,
            ],
            $promise,
            [
                'position' => 'ASC'
            ]
        );
    }
}