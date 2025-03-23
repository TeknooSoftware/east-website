<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Doctrine\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\East\Website\Doctrine\Object\Comment;
use Teknoo\East\Website\Object\Comment as CommentOriginal;
use Teknoo\East\Website\Object\Comment\Moderated;
use Teknoo\East\Website\Object\Comment\Published;
use Teknoo\East\Website\Object\Post;
use Teknoo\Tests\East\Website\Object\CommentTest as OriginalTest;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(Published::class)]
#[CoversClass(Moderated::class)]
#[CoversClass(CommentOriginal::class)]
#[CoversClass(Comment::class)]
class CommentTest extends OriginalTest
{
    public function buildObject(): Comment
    {
        return new Comment(
            post: $this->createMock(Post::class),
            author: 'authorName',
            remoteIp: '127.0.0.1',
            title: 'commentTitle',
            content: 'commentContent',
            postAt: new \DateTimeImmutable('2025-03-19 01:02:03'),
        );
    }
}
