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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Comment::class)]
#[CoversClass(Moderated::class)]
#[CoversClass(Published::class)]
class CommentTest extends TestCase
{
    use PopulateObjectTrait;

    public function buildObject()
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

    protected function buildModerateObject()
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

    public function testStatesListDeclaration()
    {
        self::assertIsArray(Comment::statesListDeclaration());
    }

    public function testGetPost()
    {
        self::assertInstanceOf(
            Post::class,
            $this->buildObject()->getPost(),
        );
    }

    public function testGetAuthor()
    {
        self::assertEquals(
            'authorName',
            $this->buildObject()->getAuthor(),
        );
    }

    public function testGetRemoteIp()
    {
        self::assertEquals(
            '127.0.0.1',
            $this->buildObject()->getRemoteIp(),
        );
    }

    public function testGetTitle()
    {
        self::assertEquals(
            'commentTitle',
            $this->buildObject()->getTitle(),
        );
    }

    public function testGetContent()
    {
        self::assertEquals(
            'commentContent',
            $this->buildObject()->getContent(),
        );
    }

    public function testGetPostAt()
    {
        self::assertInstanceOf(
            DateTimeImmutable::class,
            $this->buildObject()->getPostAt(),
        );
    }

    public function testGetModeratedAt()
    {
        self::assertNull(
            $this->buildObject()->getModeratedAt(),
        );

        self::assertInstanceOf(
            DateTimeImmutable::class,
            $this->buildModerateObject()->getModeratedAt(),
        );
    }

    public function testGetModeratedAuthor()
    {
        self::assertNull(
            $this->buildObject()->getModeratedAuthor(),
        );

        self::assertEquals(
            'moderatedAuthor',
            $this->buildModerateObject()->getModeratedAuthor(),
        );
    }

    public function testGetModeratedTitle()
    {
        self::assertNull(
            $this->buildObject()->getModeratedTitle(),
        );

        self::assertEquals(
            'moderatedTitle',
            $this->buildModerateObject()->getModeratedTitle(),
        );
    }

    public function testGetModeratedContent()
    {
        self::assertNull(
            $this->buildObject()->getModeratedContent(),
        );

        self::assertEquals(
            'moderatedContent',
            $this->buildModerateObject()->getModeratedContent(),
        );
    }
    
    public function testIsModerated()
    {
        self::assertFalse($this->buildObject()->isModerated());
        self::assertTrue($this->buildModerateObject()->isModerated());
    }
    
    public function testGetPublicAuthor()
    {
        self::assertEquals(
            'authorName',
            $this->buildObject()->getPublicAuthor(),
        );
        
        self::assertEquals(
            'moderatedAuthor',
            $this->buildModerateObject()->getPublicAuthor(),
        );
    }
    
    public function testGetPublicTitle()
    {
        self::assertEquals(
            'commentTitle',
            $this->buildObject()->getPublicTitle(),
        );
        
        self::assertEquals(
            'moderatedTitle',
            $this->buildModerateObject()->getPublicTitle(),
        );
    }
    
    public function testGetPublicContent()
    {
        self::assertEquals(
            'commentContent',
            $this->buildObject()->getPublicContent(),
        );
        
        self::assertEquals(
            'moderatedContent',
            $this->buildModerateObject()->getPublicContent(),
        );
    }

    public function testModerate()
    {
        $comment = $this->buildObject();
        self::assertFalse($comment->isModerated());

        self::assertInstanceOf(
            Comment::class,
            $comment->moderate(
                date: new DateTimeImmutable('2025-03-19 02:02:03'),
                author: 'moderatedAuthor',
                title: 'moderatedTitle',
                content: 'moderatedContent',
            )
        );

        self::assertTrue($comment->isModerated());
        self::assertEquals(
            'authorName',
            $this->buildObject()->getAuthor(),
        );

        self::assertEquals(
            '127.0.0.1',
            $this->buildObject()->getRemoteIp(),
        );

        self::assertEquals(
            'commentTitle',
            $this->buildObject()->getTitle(),
        );

        self::assertEquals(
            'commentContent',
            $this->buildObject()->getContent(),
        );

        self::assertEquals(
            'moderatedContent',
            $this->buildModerateObject()->getPublicContent(),
        );

        self::assertEquals(
            'moderatedTitle',
            $this->buildModerateObject()->getPublicTitle(),
        );

        self::assertEquals(
            'moderatedAuthor',
            $this->buildModerateObject()->getPublicAuthor(),
        );
    }
}
