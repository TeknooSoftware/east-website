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

namespace Teknoo\Tests\East\Website\Tools\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Config\Credentials;

use function ob_get_clean;
use function ob_start;
use function print_r;
use function var_dump;
use function var_export;

/**
 * Tests of the credentials of the configuration file: a login is possible only with a username and an API key, the
 * JWT is reused while it is valid, and the secrets are never dumped
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Credentials::class)]
class CredentialsTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    public function testEmptyCredentials(): void
    {
        $credentials = new Credentials();

        self::assertNull($credentials->username);
        self::assertNull($credentials->apiKey());
        self::assertNull($credentials->token);
        self::assertNull($credentials->expiresAt);
        self::assertFalse($credentials->canLogin());
        self::assertFalse($credentials->hasToken());
        self::assertFalse($credentials->isValidAt(self::NOW));
        self::assertNull($credentials->expirationDate());
    }

    public function testProperties(): void
    {
        $credentials = new Credentials('key:me@site.test', 'secret', 'jwt', 1_800_003_600);

        self::assertSame('key:me@site.test', $credentials->username);
        self::assertSame('secret', $credentials->apiKey());
        self::assertSame('jwt', $credentials->token);
        self::assertSame(1_800_003_600, $credentials->expiresAt);
    }

    public function testCanLoginWithAUsernameAndAnApiKey(): void
    {
        $credentials = new Credentials('key:me@site.test', 'secret');

        self::assertTrue($credentials->canLogin());
        self::assertSame('key:me@site.test', $credentials->username);
        self::assertSame('secret', $credentials->apiKey());
    }

    public function testCanNotLoginWithoutOneOfThem(): void
    {
        self::assertFalse((new Credentials('key:me@site.test'))->canLogin());
        self::assertFalse((new Credentials(null, 'secret'))->canLogin());
        self::assertFalse((new Credentials('', 'secret'))->canLogin());
        self::assertFalse((new Credentials('key:me@site.test', ''))->canLogin());
        self::assertFalse((new Credentials(token: 'jwt'))->canLogin());
    }

    public function testHasToken(): void
    {
        self::assertTrue((new Credentials(token: 'jwt'))->hasToken());
        self::assertFalse((new Credentials(token: ''))->hasToken());
        self::assertFalse((new Credentials('key:me@site.test', 'secret'))->hasToken());
    }

    public function testWithTokenKeepsTheAccountAndReplacesTheJwt(): void
    {
        $credentials = new Credentials('key:me@site.test', 'secret', 'old-jwt', 1_700_000_000);

        $copy = $credentials->withToken('new-jwt', 1_800_003_600);

        self::assertNotSame($credentials, $copy);
        self::assertSame('key:me@site.test', $copy->username);
        self::assertSame('secret', $copy->apiKey());
        self::assertSame('new-jwt', $copy->token);
        self::assertSame(1_800_003_600, $copy->expiresAt);
        self::assertSame('old-jwt', $credentials->token);
        self::assertSame(1_700_000_000, $credentials->expiresAt);
    }

    public function testWithTokenWithoutExpiration(): void
    {
        $copy = (new Credentials('key:me@site.test', 'secret', 'old-jwt', 1_700_000_000))->withToken('new-jwt', null);

        self::assertNull($copy->expiresAt);
        self::assertTrue($copy->isValidAt(self::NOW));
    }

    public function testValidityKeepsALeewayOf30Seconds(): void
    {
        self::assertSame(30, Credentials::LEEWAY);
        self::assertTrue((new Credentials(token: 'jwt', expiresAt: self::NOW + 31))->isValidAt(self::NOW));
        self::assertFalse((new Credentials(token: 'jwt', expiresAt: self::NOW + 30))->isValidAt(self::NOW));
        self::assertFalse((new Credentials(token: 'jwt', expiresAt: self::NOW + 1))->isValidAt(self::NOW));
        self::assertFalse((new Credentials(token: 'jwt', expiresAt: self::NOW - 1))->isValidAt(self::NOW));
    }

    public function testJwtWithoutExpirationIsValid(): void
    {
        self::assertTrue((new Credentials(token: 'jwt'))->isValidAt(self::NOW));
    }

    public function testNoJwtIsNeverValid(): void
    {
        self::assertFalse((new Credentials('key:me@site.test', 'secret', null, self::NOW + 3600))->isValidAt(self::NOW));
        self::assertFalse((new Credentials(token: '', expiresAt: self::NOW + 3600))->isValidAt(self::NOW));
    }

    public function testExpirationDate(): void
    {
        self::assertSame('2027-01-15T08:00:00+00:00', (new Credentials(expiresAt: 1_800_000_000))->expirationDate());
        self::assertNull((new Credentials(token: 'jwt'))->expirationDate());
    }

    public function testDebugInfoHidesTheSecrets(): void
    {
        $credentials = new Credentials('key:me@site.test', 'super-secret-key', 'super-secret-jwt', 1_800_003_600);

        ob_start();
        var_dump($credentials);
        $dump = (string) ob_get_clean();

        self::assertStringContainsString('key:me@site.test', $dump);
        self::assertStringNotContainsString('super-secret-key', $dump);
        self::assertStringNotContainsString('super-secret-jwt', $dump);
        self::assertStringContainsString('***', $dump);
        self::assertStringNotContainsString('super-secret-key', print_r($credentials, true));
    }

    public function testDebugInfoWithoutSecrets(): void
    {
        self::assertSame(
            ['username' => null, 'apiKey' => null, 'token' => null, 'expiresAt' => null],
            (new Credentials())->__debugInfo(),
        );
    }

    public function testExportedValueDoesNotLeakTheApiKeyInDebugInfo(): void
    {
        $info = (new Credentials('a:b', 'secret', 'jwt', 1_800_003_600))->__debugInfo();

        self::assertStringNotContainsString('secret', var_export($info, true));
        self::assertSame(['username' => 'a:b', 'apiKey' => '***', 'token' => '***', 'expiresAt' => 1_800_003_600], $info);
    }
}
