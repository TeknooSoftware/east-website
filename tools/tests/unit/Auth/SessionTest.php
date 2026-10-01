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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Auth\Session;

/**
 * Tests of the session: the JWT and the information needed to reuse it
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    private function session(?int $expiresAt = 1_800_003_600): Session
    {
        return new Session('https://site.test', 'key:me@site.test', 'jwt', $expiresAt);
    }

    /**
     * @return iterable<string, array{string, string|null, bool}>
     */
    public static function matchingCases(): iterable
    {
        yield 'same site and user' => ['https://site.test', 'key:me@site.test', true];
        yield 'same site, user not given' => ['https://site.test', null, true];
        yield 'same site, empty user' => ['https://site.test', '', true];
        yield 'same site, another user' => ['https://site.test', 'other:me@site.test', false];
        yield 'another site' => ['https://other.test', 'key:me@site.test', false];
        yield 'another site, user not given' => ['https://other.test', null, false];
        yield 'same host, another path' => ['https://site.test/sub', null, false];
    }

    #[DataProvider('matchingCases')]
    public function testMatches(string $baseUrl, ?string $username, bool $expected): void
    {
        self::assertSame($expected, $this->session()->matches($baseUrl, $username));
    }

    public function testValidityKeepsALeewayOf30Seconds(): void
    {
        self::assertSame(30, Session::LEEWAY);
        self::assertTrue($this->session(self::NOW + 31)->isValidAt(self::NOW));
        self::assertFalse($this->session(self::NOW + 30)->isValidAt(self::NOW));
        self::assertFalse($this->session(self::NOW + 1)->isValidAt(self::NOW));
        self::assertFalse($this->session(self::NOW - 1)->isValidAt(self::NOW));
    }

    public function testSessionWithoutExpirationIsValid(): void
    {
        self::assertTrue($this->session(null)->isValidAt(self::NOW));
    }

    public function testExpirationDate(): void
    {
        self::assertSame('2027-01-15T08:00:00+00:00', $this->session(1_800_000_000)->expirationDate());
        self::assertNull($this->session(null)->expirationDate());
    }

    public function testProperties(): void
    {
        $session = $this->session();

        self::assertSame('https://site.test', $session->baseUrl);
        self::assertSame('key:me@site.test', $session->username);
        self::assertSame('jwt', $session->token);
        self::assertSame(1_800_003_600, $session->expiresAt);
    }
}
