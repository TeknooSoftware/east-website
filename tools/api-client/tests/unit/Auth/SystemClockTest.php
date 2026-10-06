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

namespace Teknoo\Tests\East\Website\Tools\Auth;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Teknoo\East\Website\Tools\Auth\SystemClock;

use function abs;
use function time;

/**
 * Tests of the PSR-20 clock of the CLI
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SystemClock::class)]
class SystemClockTest extends TestCase
{
    public function testItIsAPsrClock(): void
    {
        self::assertInstanceOf(ClockInterface::class, new SystemClock());
    }

    public function testNowIsTheCurrentTime(): void
    {
        $now = (new SystemClock())->now();

        self::assertInstanceOf(DateTimeImmutable::class, $now);
        self::assertLessThanOrEqual(5, abs(time() - $now->getTimestamp()));
    }

    public function testNowDoesNotGoBack(): void
    {
        $clock = new SystemClock();
        $first = $clock->now();
        $second = $clock->now();

        self::assertGreaterThanOrEqual($first->getTimestamp(), $second->getTimestamp());
    }
}
