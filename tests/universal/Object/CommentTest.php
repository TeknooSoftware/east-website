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

namespace Teknoo\Tests\East\Website\Object;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\Comment;
use Teknoo\East\Website\Object\Comment\Moderated;
use Teknoo\East\Website\Object\Comment\Published;
use Teknoo\East\Website\Object\Post;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Comment::class)]
#[CoversClass(Moderated::class)]
#[CoversClass(Published::class)]
class CommentTest extends TestCase
{
    use PopulateObjectTrait;

    public function buildObject(): \Teknoo\East\Website\Object\Comment
    {
        return new Comment(
            post: $this->createMock(Post::class),
            author: 'authorName',
            remoteIp: '127.0.0.1',
            title: 'commentTitle',
            content: 'commentContent',
            postAt: new DateTimeImmutable('2025-03-19 01:02:03'),
        );
    }

    protected function buildModerateObject(): \Teknoo\East\Website\Object\Comment
    {
        return new Comment(
            post: $this->createMock(Post::class),
            author: 'authorName',
            remoteIp: '127.0.0.1',
            title: 'commentTitle',
            content: 'commentContent',
            postAt: new DateTimeImmutable('2025-03-19 01:02:03'),
            moderatedAt: new DateTimeImmutable('2025-03-19 01:02:03'),
            moderatedAuthor: 'moderatedAuthor',
            moderatedTitle: 'moderatedTitle',
            moderatedContent: 'moderatedContent',
        );
    }

    public function testStatesListDeclaration(): void
    {
        $this->assertIsArray(Comment::statesListDeclaration());
    }

    public function testGetPost(): void
    {
        $this->assertInstanceOf(Post::class, $this->buildObject()->getPost());
    }

    public function testGetAuthor(): void
    {
        $this->assertEquals('authorName', $this->buildObject()->getAuthor());
    }

    public function testGetRemoteIp(): void
    {
        $this->assertEquals('127.0.0.1', $this->buildObject()->getRemoteIp());
    }

    public function testGetTitle(): void
    {
        $this->assertEquals('commentTitle', $this->buildObject()->getTitle());
    }

    public function testGetContent(): void
    {
        $this->assertEquals('commentContent', $this->buildObject()->getContent());
    }

    public function testGetPostAt(): void
    {
        $this->assertInstanceOf(DateTimeImmutable::class, $this->buildObject()->getPostAt());
    }

    public function testGetModeratedAt(): void
    {
        $this->assertNotInstanceOf(\DateTimeInterface::class, $this->buildObject()->getModeratedAt());

        $this->assertInstanceOf(DateTimeImmutable::class, $this->buildModerateObject()->getModeratedAt());
    }

    public function testGetModeratedAuthor(): void
    {
        $this->assertNull($this->buildObject()->getModeratedAuthor());

        $this->assertEquals('moderatedAuthor', $this->buildModerateObject()->getModeratedAuthor());
    }

    public function testGetModeratedTitle(): void
    {
        $this->assertNull($this->buildObject()->getModeratedTitle());

        $this->assertEquals('moderatedTitle', $this->buildModerateObject()->getModeratedTitle());
    }

    public function testGetModeratedContent(): void
    {
        $this->assertNull($this->buildObject()->getModeratedContent());

        $this->assertEquals('moderatedContent', $this->buildModerateObject()->getModeratedContent());
    }

    public function testIsModerated(): void
    {
        $this->assertFalse($this->buildObject()->isModerated());
        $this->assertTrue($this->buildModerateObject()->isModerated());
    }

    public function testGetPublicAuthor(): void
    {
        $this->assertEquals('authorName', $this->buildObject()->getPublicAuthor());

        $this->assertEquals('moderatedAuthor', $this->buildModerateObject()->getPublicAuthor());
    }

    public function testGetPublicTitle(): void
    {
        $this->assertEquals('commentTitle', $this->buildObject()->getPublicTitle());

        $this->assertEquals('moderatedTitle', $this->buildModerateObject()->getPublicTitle());
    }

    public function testGetPublicContent(): void
    {
        $this->assertEquals('commentContent', $this->buildObject()->getPublicContent());

        $this->assertEquals('moderatedContent', $this->buildModerateObject()->getPublicContent());
    }

    public function testModerate(): void
    {
        $comment = $this->buildObject();
        $this->assertFalse($comment->isModerated());

        $this->assertInstanceOf(Comment::class, $comment->moderate(
            date: new DateTimeImmutable('2025-03-19 02:02:03'),
            author: 'moderatedAuthor',
            title: 'moderatedTitle',
            content: 'moderatedContent',
        ));

        $this->assertTrue($comment->isModerated());
        $this->assertEquals('authorName', $this->buildObject()->getAuthor());

        $this->assertEquals('127.0.0.1', $this->buildObject()->getRemoteIp());

        $this->assertEquals('commentTitle', $this->buildObject()->getTitle());

        $this->assertEquals('commentContent', $this->buildObject()->getContent());

        $this->assertEquals('moderatedContent', $this->buildModerateObject()->getPublicContent());

        $this->assertEquals('moderatedTitle', $this->buildModerateObject()->getPublicTitle());

        $this->assertEquals('moderatedAuthor', $this->buildModerateObject()->getPublicAuthor());
    }
}
