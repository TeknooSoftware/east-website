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
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Post;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step to load a post from it's id. Post can be not published. And inject it into the workplan and prepare the
 * parameter to compute the redirection route. Previous routes's parameters are conserved
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoadPostFromRequest
{
    public function __construct(
        private PostLoader $postLoader,
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

        $this->postLoader->load(
            $id,
            new Promise(
                static fn (Post $post) => $manager->updateWorkPlan([
                    'post' => $post,
                    'parameters' => $parameters,
                ]),
                static fn (Throwable $error) => $manager->error($error),
            ),
        );

        return $this;
    }
}
