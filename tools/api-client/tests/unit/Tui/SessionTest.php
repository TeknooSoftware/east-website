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

namespace Teknoo\Tests\East\Website\Tools\Tui;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Tui\Navigator;
use Teknoo\East\Website\Tools\Tui\Session;
use Teknoo\Tests\East\Website\Tools\Support\ApiStub;

/**
 * Tests of the services shared by the screens of the interactive mode
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    public function testItHoldsTheServicesOfTheScreens(): void
    {
        $api = new ApiStub();
        $registry = new Registry();
        $builder = new PayloadBuilder();
        $navigator = new Navigator(new Tui(terminal: new VirtualTerminal(40, 10)));

        $session = new Session($api->connection, $api->gateway, $registry, $builder, $navigator);

        self::assertSame($api->connection, $session->connection);
        self::assertSame($api->gateway, $session->gateway);
        self::assertSame($registry, $session->registry);
        self::assertSame($builder, $session->builder);
        self::assertSame($navigator, $session->navigator);
    }
}
