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

namespace Teknoo\Tests\East\WebsiteBundle\Form\Mapper;

use ArrayIterator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Object\Comment;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\WebsiteBundle\Form\DataMapper\CommentMapper;

use function ucfirst;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CommentMapper::class)]
class CommentMapperTest extends TestCase
{
    private (DatesService&MockObject)|null $datesService = null;

    private function getDatesService(): DatesService&MockObject
    {
        if (!$this->datesService instanceof DatesService) {
            $this->datesService = $this->createMock(DatesService::class);
        }

        return $this->datesService;

    }

    private function buildMapper(): CommentMapper
    {
        return new CommentMapper($this->getDatesService());
    }

    public function testMapDataToFormsWithNotCommentObject()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())->method('setData');

        $this->buildMapper()->mapDataToForms(new stdClass(), new ArrayIterator([$form]));
    }

    public function testMapDataToFormsWithCommentObject()
    {
        $comment = new Comment(
            post: $post = $this->createMock(Post::class),
            author: 'author',
            remoteIp: 'ip',
            title: 'title',
            content: 'content',
            postAt: $date = new DateTimeImmutable('2025-03-20 12:00:00'),
            moderatedAt: null,
            moderatedAuthor: null,
            moderatedTitle: null,
            moderatedContent: null
        );

        $formsList = [];
        $keysList = [
            'author',
            'remoteIp',
            'title',
            'content',
            'postAt',
            'moderatedAt',
            'moderatedAuthor',
            'moderatedTitle',
            'moderatedContent',
        ];

        foreach ($keysList as $key) {
            $form = $this->createMock(FormInterface::class);
            $form->expects($this->once())->method('getName')->willReturn($key);
            $form->expects($this->once())->method('setData')->with(
                match ($key) {
                    'postAt' => $date,
                    default => $comment->{'get' . ucfirst($key)}(),
                }
            );

            $formsList[] = $form;
        }

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getName')->willReturn('other');
        $form->expects($this->never())->method('setData');

        $formsList[] = $form;

        $forms = new ArrayIterator($formsList);

        $this->buildMapper()->mapDataToForms($comment, $forms);
    }

    public function testMapFormsToDataWithNotCommentObject()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())->method('setData');

        $o = new stdClass();
        $this->buildMapper()->mapFormsToData(new ArrayIterator([$form]), $o);
    }

    public function testMapFormsToDataWithCommentObjectNotModerated()
    {
        $comment = new Comment(
            post: $post = $this->createMock(Post::class),
            author: 'author',
            remoteIp: 'ip',
            title: 'title',
            content: 'content',
            postAt: $date = new DateTimeImmutable('2025-03-20 12:00:00'),
            moderatedAt: null,
            moderatedAuthor: null,
            moderatedTitle: null,
            moderatedContent: null
        );

        $formsList = [];
        $keysList = [
            'author' => 'author2',
            'remoteIp' => 'ip2',
            'title' => 'title2',
            'content' => 'content2',
            'postAt' => $date,
            'moderatedAuthor' => null,
            'moderatedTitle' => null,
            'moderatedContent' => null,
        ];

        foreach ($keysList as $key => $value) {
            $form = $this->createMock(FormInterface::class);
            $form->expects($this->any())->method('getName')->willReturn($key);
            $form->expects($this->any())->method('getData')->willReturn($value);

            $formsList[] = $form;
        }

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getName')->willReturn('other');
        $form->expects($this->never())->method('getData');

        $formsList[] = $form;

        $this->getDatesService()
            ->expects($this->never())
            ->method('passMeTheDate');

        $this->buildMapper()->mapFormsToData(new ArrayIterator($formsList), $comment);

        self::assertEquals('author', $comment->getAuthor());
        self::assertEquals('ip', $comment->getRemoteIp());
        self::assertEquals('title', $comment->getTitle());
        self::assertEquals('content', $comment->getContent());
        self::assertNull($comment->getModeratedAt());
        self::assertNull($comment->getModeratedAuthor());
        self::assertNull($comment->getModeratedTitle());
        self::assertNull($comment->getModeratedContent());
    }

    public function testMapFormsToDataWithCommentObjectModerated()
    {
        $comment = new Comment(
            post: $post = $this->createMock(Post::class),
            author: 'author',
            remoteIp: 'ip',
            title: 'title',
            content: 'content',
            postAt: $date = new DateTimeImmutable('2025-03-15 12:00:00'),
            moderatedAt: null,
            moderatedAuthor: null,
            moderatedTitle: null,
            moderatedContent: null
        );

        $formsList = [];
        $keysList = [
            'author' => 'author2',
            'remoteIp' => 'ip2',
            'title' => 'title2',
            'content' => 'content2',
            'postAt' => $date,
            'moderatedAuthor' => 'moderatedAuthor',
            'moderatedTitle' => 'moderatedTitle',
            'moderatedContent' => 'moderatedContent',
        ];

        foreach ($keysList as $key => $value) {
            $form = $this->createMock(FormInterface::class);
            $form->expects($this->any())->method('getName')->willReturn($key);
            $form->expects($this->any())->method('getData')->willReturn($value);

            $formsList[] = $form;
        }

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getName')->willReturn('other');
        $form->expects($this->never())->method('getData');

        $formsList[] = $form;

        $moderatedAt = new DateTimeImmutable('2025-03-20 12:00:00');
        $this->getDatesService()
            ->expects($this->once())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callback) use ($moderatedAt) {
                    $callback($moderatedAt);

                    return $this->getDatesService();
                }
            );

        $this->buildMapper()->mapFormsToData(new ArrayIterator($formsList), $comment);

        self::assertEquals('author', $comment->getAuthor());
        self::assertEquals('ip', $comment->getRemoteIp());
        self::assertEquals('title', $comment->getTitle());
        self::assertEquals('content', $comment->getContent());
        self::assertEquals($moderatedAt, $comment->getModeratedAt());
        self::assertEquals('moderatedAuthor', $comment->getModeratedAuthor());
        self::assertEquals('moderatedTitle', $comment->getModeratedTitle());
        self::assertEquals( 'moderatedContent', $comment->getModeratedContent());
    }

    public function testMapFormsToDataWithCommentObjectAlreadyModerated()
    {
        $comment = new Comment(
            post: $post = $this->createMock(Post::class),
            author: 'author',
            remoteIp: 'ip',
            title: 'title',
            content: 'content',
            postAt: $date = new DateTimeImmutable('2025-03-15 12:00:00'),
            moderatedAt: $moderatedAt = new DateTimeImmutable('2025-03-20 12:00:00'),
            moderatedAuthor: 'moderatedAuthor',
            moderatedTitle: 'moderatedTitle',
            moderatedContent: 'moderatedContent',
        );

        $formsList = [];
        $keysList = [
            'author' => 'author2',
            'remoteIp' => 'ip2',
            'title' => 'title2',
            'content' => 'content2',
            'postAt' => $date,
            'moderatedAuthor' => 'moderatedAuthor',
            'moderatedTitle' => 'moderatedTitle',
            'moderatedContent' => 'moderatedContent',
        ];

        foreach ($keysList as $key => $value) {
            $form = $this->createMock(FormInterface::class);
            $form->expects($this->any())->method('getName')->willReturn($key);
            $form->expects($this->any())->method('getData')->willReturn($value);

            $formsList[] = $form;
        }

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getName')->willReturn('other');
        $form->expects($this->never())->method('getData');

        $formsList[] = $form;
        $this->getDatesService()
            ->expects($this->never())
            ->method('passMeTheDate');

        $this->buildMapper()->mapFormsToData(new ArrayIterator($formsList), $comment);

        self::assertEquals('author', $comment->getAuthor());
        self::assertEquals('ip', $comment->getRemoteIp());
        self::assertEquals('title', $comment->getTitle());
        self::assertEquals('content', $comment->getContent());
        self::assertEquals($moderatedAt, $comment->getModeratedAt());
        self::assertEquals('moderatedAuthor', $comment->getModeratedAuthor());
        self::assertEquals('moderatedTitle', $comment->getModeratedTitle());
        self::assertEquals( 'moderatedContent', $comment->getModeratedContent());
    }
}
