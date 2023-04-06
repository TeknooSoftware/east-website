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

namespace Teknoo\East\Website\Doctrine\Translatable\Wrapper;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Teknoo\East\Website\Contracts\Object\TranslatableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

/**
 * Implementation to define factory able to create the good wrapper instance to wrap the Translatable object passed in
 * argument with its metadata.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface FactoryInterface
{
    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    public function __invoke(TranslatableInterface $object, ClassMetadata $metadata): WrapperInterface;
}
