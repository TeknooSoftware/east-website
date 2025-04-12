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

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Query\Tag\TagFromSlugQuery;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step to load, from the slug a tag thanks to the `TagLoader` and inject it into the Workplan under the key Tag::class
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ExtractTag
{
    public function __construct(
        private readonly TagLoader $loader,
    ) {
    }

    public function __invoke(ManagerInterface $manager, string $tag): self
    {
        $this->loader->fetch(
            new TagFromSlugQuery($tag),
            new Promise(
                fn (Tag $tag) => $manager->updateWorkPlan([
                    Tag::class => $tag,
                    'tag' => $tag,
                ]),
                fn (Throwable $throwable) => $manager->error($throwable,),
            )
        );

        return $this;
    }
}
