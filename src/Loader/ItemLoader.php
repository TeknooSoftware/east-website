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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Loader;

use Teknoo\East\Website\DBSource\Repository\ItemRepositoryInterface;

/**
 * Object loader in charge of object `Teknoo\East\Website\Object\Item`.
 * Must provide an implementation of `ItemRepositoryInterface` to be able work.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ItemLoader implements LoaderInterface
{
    use LoaderTrait;

    public function __construct(ItemRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
