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

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Post;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step to load a post from it's id. Post can be not published. And inject it into the workplan and prepare the
 * parameter to compute the redirection route. Previous routes's parameters are conserved
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoadPostFromRequest
{
    public function __construct(
        private readonly PostLoader $postLoader,
    ) {
    }

    /**
     * @param array<string, string> $parameters
     */
    public function __invoke(
        ManagerInterface $manager,
        string $id,
        array $parameters = [],
    ): self {
        $parameters['postId'] = $id;

        /** @var Promise<Post, mixed, mixed> $promise */
        $promise = new Promise(
            static fn (Post $post): ChefInterface => $manager->updateWorkPlan([
                'post' => $post,
                'parameters' => $parameters,
            ]),
            static fn (Throwable $error): ChefInterface => $manager->error($error),
        );

        $this->postLoader->load(
            $id,
            $promise,
        );

        return $this;
    }
}
