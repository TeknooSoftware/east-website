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

namespace Teknoo\Tests\East\Website\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Middleware\MenuMiddleware;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Common\View\ParametersBag;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(MenuMiddleware::class)]
class MenuMiddlewareTest extends TestCase
{
    private (MenuGenerator&Stub)|(MenuGenerator&MockObject)|null $menuGenerator = null;

    public function getMenuGenerator(bool $stub = false): (MenuGenerator&Stub)|(MenuGenerator&MockObject)
    {
        if (!$this->menuGenerator instanceof MenuGenerator) {
            if ($stub) {
                $this->menuGenerator = $this->createStub(MenuGenerator::class);
            } else {
                $this->menuGenerator = $this->createMock(MenuGenerator::class);
            }
        }

        return $this->menuGenerator;
    }

    public function buildMiddleware(): MenuMiddleware
    {
        return new MenuMiddleware($this->getMenuGenerator(true));
    }

    public function testExecute(): void
    {
        $bag = $this->createStub(ParametersBag::class);

        $this->assertInstanceOf(MenuMiddleware::class, $this->buildMiddleware()->execute($bag));
    }
}
