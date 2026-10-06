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

namespace Teknoo\Tests\East\Website\Tools\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Http\ErrorKind;

/**
 * Tests of the mapping of the failures to the exit codes
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ErrorKind::class)]
class ErrorKindTest extends TestCase
{
    public function testExitCodes(): void
    {
        self::assertSame(1, ErrorKind::Server->exitCode());
        self::assertSame(1, ErrorKind::Transport->exitCode());
        self::assertSame(2, ErrorKind::Usage->exitCode());
        self::assertSame(2, ErrorKind::Validation->exitCode());
        self::assertSame(3, ErrorKind::Auth->exitCode());
        self::assertSame(4, ErrorKind::NotFound->exitCode());
    }

    /**
     * @return iterable<string, array{int, ErrorKind}>
     */
    public static function statuses(): iterable
    {
        yield 'bad request' => [400, ErrorKind::Validation];
        yield 'unprocessable' => [422, ErrorKind::Validation];
        yield 'unauthorized' => [401, ErrorKind::Auth];
        yield 'forbidden' => [403, ErrorKind::Auth];
        yield 'not found' => [404, ErrorKind::NotFound];
        yield 'server error' => [500, ErrorKind::Server];
        yield 'conflict' => [409, ErrorKind::Server];
    }

    #[DataProvider('statuses')]
    public function testFromStatus(int $status, ErrorKind $expected): void
    {
        self::assertSame($expected, ErrorKind::fromStatus($status));
    }
}
