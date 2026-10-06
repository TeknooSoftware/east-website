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
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Auth\Jwt;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function base64_encode;
use function json_encode;
use function rtrim;
use function strtr;

/**
 * Tests of the reading of the claims of a JWT, without verification of its signature
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Jwt::class)]
class JwtTest extends TestCase
{
    private function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function token(array $claims): string
    {
        return $this->encode('{"alg":"none"}') . '.' . $this->encode((string) json_encode($claims)) . '.signature';
    }

    public function testPayload(): void
    {
        self::assertSame(['exp' => 1_800_003_600], Jwt::payload(ApiHarness::jwt(1_800_003_600)));
    }

    public function testPayloadWithUrlSafeAlphabetAndMissingPadding(): void
    {
        // Every length of payload, to cover the paddings of 0, 1 and 2 characters, and the characters "-" and "_"
        foreach (['a', 'ab', 'abc', 'abcd', '??>>', '~~~~~'] as $value) {
            $token = $this->token(['exp' => 1_800_000_000, 'v' => $value]);

            self::assertSame(['exp' => 1_800_000_000, 'v' => $value], Jwt::payload($token), $value);
        }
    }

    public function testPayloadOfAMalformedTokenIsEmpty(): void
    {
        self::assertSame([], Jwt::payload(''));
        self::assertSame([], Jwt::payload('not-a-jwt'));
        self::assertSame([], Jwt::payload('a.b'));
        self::assertSame([], Jwt::payload('a.b.c.d'));
    }

    public function testPayloadWithInvalidBase64IsEmpty(): void
    {
        self::assertSame([], Jwt::payload('header.!!!.signature'));
    }

    public function testPayloadWhichIsNotJsonIsEmpty(): void
    {
        self::assertSame([], Jwt::payload('header.' . $this->encode('not json') . '.signature'));
        self::assertSame([], Jwt::payload('header.' . $this->encode('12') . '.signature'));
    }

    public function testExpiresAt(): void
    {
        self::assertSame(1_800_003_600, Jwt::expiresAt(ApiHarness::jwt(1_800_003_600)));
    }

    public function testExpiresAtAcceptsANumericString(): void
    {
        self::assertSame(1_800_003_600, Jwt::expiresAt($this->token(['exp' => '1800003600'])));
    }

    public function testExpiresAtWithoutValidClaim(): void
    {
        self::assertNull(Jwt::expiresAt($this->token(['sub' => 'me'])));
        self::assertNull(Jwt::expiresAt($this->token(['exp' => 'tomorrow'])));
        self::assertNull(Jwt::expiresAt($this->token(['exp' => null])));
        self::assertNull(Jwt::expiresAt('garbage'));
    }
}
