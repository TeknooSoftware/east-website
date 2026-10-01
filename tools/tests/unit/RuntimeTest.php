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

namespace Teknoo\Tests\East\Website\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Config\ConnectionFactory;
use Teknoo\East\Website\Tools\Http\ApiClient;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Renderer;
use Teknoo\East\Website\Tools\Output\Warnings;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Runtime;
use Teknoo\Tests\East\Website\Tools\Support\FixedClock;

/**
 * Tests of the services shared by the commands
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Runtime::class)]
class RuntimeTest extends TestCase
{
    public function testItHoldsTheSharedServices(): void
    {
        $clock = new FixedClock();
        $warnings = new Warnings();
        $transport = new Transport(new MockHttpClient());
        $authenticator = new Authenticator($transport, $clock, $warnings);
        $client = new ApiClient($transport, $authenticator);
        $connections = new ConnectionFactory([]);
        $renderer = new Renderer();
        $registry = new Registry();

        $runtime = new Runtime($client, $authenticator, $connections, $renderer, $warnings, $registry, $clock);

        self::assertSame($client, $runtime->client);
        self::assertSame($authenticator, $runtime->authenticator);
        self::assertSame($connections, $runtime->connections);
        self::assertSame($renderer, $runtime->renderer);
        self::assertSame($warnings, $runtime->warnings);
        self::assertSame($registry, $runtime->registry);
        self::assertSame($clock, $runtime->clock);
    }
}
