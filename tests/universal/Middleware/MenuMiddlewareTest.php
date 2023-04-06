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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Middleware;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Middleware\MenuMiddleware;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Common\View\ParametersBag;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers      \Teknoo\East\Website\Middleware\MenuMiddleware
 */
class MenuMiddlewareTest extends TestCase
{
    /**
     * @var MenuGenerator
     */
    private $menuGenerator;

    public function getMenuGenerator(): \MenuGenerator|\PHPUnit\Framework\MockObject\MockObject
    {
        if (!$this->menuGenerator instanceof MenuGenerator) {
            $this->menuGenerator = $this->createMock(MenuGenerator::class);
        }

        return $this->menuGenerator;
    }

    public function buildMiddleware(): MenuMiddleware
    {
        return new MenuMiddleware($this->getMenuGenerator());
    }

    public function testExecute()
    {
        $bag = $this->createMock(ParametersBag::class);

        self::assertInstanceOf(
            MenuMiddleware::class,
            $this->buildMiddleware()->execute($bag)
        );
    }
}
