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
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Loader;

use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Common\Loader\LoaderTrait;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Object\Type;

/**
 * Object loader in charge of object `Teknoo\East\Website\Object\Type`.
 * Must provide an implementation of `TypeRepositoryInterface` to be able work.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements LoaderInterface<Type>
 */
class TypeLoader implements LoaderInterface
{
    /**
     * @use LoaderTrait<Type>
     */
    use LoaderTrait;

    public function __construct(TypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
