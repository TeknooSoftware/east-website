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

use DomainException;
use Teknoo\East\Common\Query\Expr\ObjectReference;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Object\Post;

/**
 * Step to prepare criteria to use in a ListObjectEndPoint plan to filter comment, or other Post's components
 * or Post's relationship from the Post present in the Workplan. If there are no Plan, an 404 error is thrown.
 * Previous value of criteria are conserved
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PrepareCriteriaFromPost
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function __invoke(
        ManagerInterface $manager,
        ?Post $post = null,
        array $criteria = [],
    ): self {
        if (!$post) {
            $manager->error(
                new DomainException(
                    message: 'Post is not loaded',
                    code: 404
                )
            );

            return $this;
        }

        $criteria['post'] = new ObjectReference($post);

        $manager->updateWorkPlan([
            'criteria' => $criteria,
        ]);

        return $this;
    }
}
