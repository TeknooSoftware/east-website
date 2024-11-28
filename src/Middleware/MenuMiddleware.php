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

namespace Teknoo\East\Website\Middleware;

use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Common\View\ParametersBag;

/**
 * Middleware injected into the main East Foundation's recipe, as middleware, to inject into the view parameter bag
 * the instance of the menu generator service, to be used into the template engine to show menus.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class MenuMiddleware
{
    final public const MIDDLEWARE_PRIORITY = 7;

    public function __construct(
        private readonly MenuGenerator $menuGenerator,
    ) {
    }

    public function execute(
        ParametersBag $bag,
    ): self {
        $bag->set('menuGenerator', $this->menuGenerator);

        return $this;
    }
}
