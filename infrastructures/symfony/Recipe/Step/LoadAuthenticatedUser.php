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

namespace Teknoo\East\WebsiteBundle\Recipe\Step;

use Teknoo\East\Common\Contracts\User\UserInterface;
use Teknoo\East\CommonBundle\Recipe\Step\LoadCurrentUser;
use Teknoo\East\CommonBundle\Security\Exception\WrongUserException;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadAuthenticatedUserInterface;

/**
 * Step to put into the workplan, under the key `UserInterface::class`, the East Common user wrapped by the Symfony
 * user of the current security token. Nothing is done when a user is already in the workplan, when there is no
 * token or when the user is not an East Common user (anonymous visitors, other providers).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoadAuthenticatedUser extends LoadCurrentUser implements LoadAuthenticatedUserInterface
{
    public function __invoke(
        ManagerInterface $manager,
        ?UserInterface $currentUser = null,
    ): self {
        if (null !== $currentUser) {
            return $this;
        }

        try {
            parent::__invoke($manager);
        } catch (WrongUserException) {
        }

        return $this;
    }
}
