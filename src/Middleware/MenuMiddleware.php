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

namespace Teknoo\East\Website\Middleware;

use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Common\View\ParametersBag;

/**
 * Middleware injected into the main East Foundation's recipe, as middleware, to inject into the view parameter bag
 * the instance of the menu generator service, to be used into the template engine to show menus.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
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
