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

use function print_r;
use function var_export;

/**
 * Tests of the credentials: a login is possible only with a username and an API key, and the secrets are never dumped
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Credentials::class)]
class CredentialsTest extends TestCase
{
    public function testEmptyCredentials(): void
    {
        $credentials = new Credentials();

        self::assertNull($credentials->username);
        self::assertNull($credentials->apiKey());
        self::assertNull($credentials->token);
        self::assertFalse($credentials->canLogin());
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

    public function testWithUsernameKeepsTheOtherSecrets(): void
    {
        $credentials = new Credentials('old', 'secret', 'jwt');

        $copy = $credentials->withUsername('key:new@site.test');

        self::assertNotSame($credentials, $copy);
        self::assertSame('key:new@site.test', $copy->username);
        self::assertSame('secret', $copy->apiKey());
        self::assertSame('jwt', $copy->token);
        self::assertSame('old', $credentials->username);
    }

    public function testDebugInfoHidesTheSecrets(): void
    {
        $credentials = new Credentials('key:me@site.test', 'super-secret-key', 'super-secret-jwt');

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
            ['username' => null, 'apiKey' => null, 'token' => null],
            (new Credentials())->__debugInfo(),
        );
    }

    public function testExportedValueDoesNotLeakTheApiKeyInDebugInfo(): void
    {
        $info = (new Credentials('a:b', 'secret', 'jwt'))->__debugInfo();

        self::assertStringNotContainsString('secret', var_export($info, true));
        self::assertSame(['username' => 'a:b', 'apiKey' => '***', 'token' => '***'], $info);
    }
}
