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

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Http\Endpoints;

/**
 * Tests of the paths of the API
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Endpoints::class)]
class EndpointsTest extends TestCase
{
    public function testDefaults(): void
    {
        $endpoints = new Endpoints();

        self::assertSame('/api/v1', $endpoints->apiPrefix());
        self::assertSame('/api/v1/admin', $endpoints->adminPrefix());
        self::assertSame('/api/v1/login', $endpoints->login());
        self::assertSame('/api/v1/jwt/create-token', $endpoints->renew());
    }

    public function testPrefixesAreNormalized(): void
    {
        $endpoints = new Endpoints('api/v2/', '/admin//', 'auth/login');

        self::assertSame('/api/v2', $endpoints->apiPrefix());
        self::assertSame('/admin', $endpoints->adminPrefix());
        self::assertSame('/auth/login', $endpoints->login());
        self::assertSame('/api/v2/jwt/create-token', $endpoints->renew());
    }

    public function testRootPrefixIsEmpty(): void
    {
        $endpoints = new Endpoints('/', '/');

        self::assertSame('', $endpoints->apiPrefix());
        self::assertSame('/tag', $endpoints->admin('tag'));
        self::assertSame('/posts', $endpoints->api('posts'));
    }

    public function testAdminPath(): void
    {
        self::assertSame('/api/v1/admin/tags', (new Endpoints())->admin('tags'));
        self::assertSame('/api/v1/admin/tags', (new Endpoints())->admin('/tags'));
    }

    public function testApiPath(): void
    {
        self::assertSame('/api/v1/posts/by/php', (new Endpoints())->api('posts/by/{tag}', ['tag' => 'php']));
    }

    public function testPlaceholdersAreUrlEncoded(): void
    {
        self::assertSame(
            '/api/v1/admin/post/a%2Fb%20c/comment/x%3Fy',
            (new Endpoints())->admin('post/{post-id}/comment/{id}', ['post-id' => 'a/b c', 'id' => 'x?y']),
        );
    }

    public function testMissingPlaceholderIsAProgrammingError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing path parameter "id"');

        (new Endpoints())->admin('tag/{id}', []);
    }
}
