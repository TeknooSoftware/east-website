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

namespace Teknoo\East\Website\Recipe\Step;

use Teknoo\East\Website\Loader\LoaderInterface;
use Teknoo\East\Website\Contracts\ObjectInterface;
use Teknoo\East\Website\Object\SluggableInterface;
use Teknoo\East\Website\Service\FindSlugService;

/**
 * Recipe step to prepare a persisted and sluggable object to generate a new uniq slug (if needed) and inject it into
 * the object before save in a next step.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SlugPreparation
{
    public function __construct(
        private FindSlugService $findSlugService,
    ) {
    }

    public function __invoke(LoaderInterface $loader, ObjectInterface $object, ?string $slugField = null): self
    {
        if (!$object instanceof SluggableInterface || null === $slugField) {
            return $this;
        }

        $object->prepareSlugNear(
            $loader,
            $this->findSlugService,
            $slugField
        );

        return $this;
    }
}
