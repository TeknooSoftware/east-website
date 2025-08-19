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

namespace Teknoo\Tests\East\WebsiteBundle\DTO;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Doctrine\Object\Comment as DoctrineComment;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\WebsiteBundle\Form\DTO\Comment;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CommentTest extends TestCase
{
    public function testConstructor(): void
    {
        $dto = new Comment(
            $this->createMock(Post::class),
            'foo',
            'bar',
            'boo',
        );

        $this->assertInstanceOf(Post::class, $dto->post);
        $this->assertEquals('foo', $dto->author);
        $this->assertEquals('bar', $dto->title);
        $this->assertEquals('boo', $dto->content);
    }

    public function testPersistInto(): void
    {
        $now = new \DateTimeImmutable('2025-01-01 00:00:00');
        $dto = new Comment(
            $post = $this->createMock(Post::class),
            'foo',
            'bar',
            'boo',
        );

        $post
            ->method('getSlug')
            ->willReturn('fooo');

        $objectComment = new DoctrineComment(
            $post,
            'foo',
            '127.0.0.1',
            'bar',
            'boo',
            $now,
        );

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([
                \Teknoo\East\Website\Object\Comment::class => $objectComment,
                ObjectInterface::class => $objectComment,
                'parameters' => [
                    'slug' => 'fooo',
                ],
            ]);

        $this->assertInstanceOf(Comment::class, $dto->persistInto(
            $manager,
            DoctrineComment::class,
            '127.0.0.1',
            $now,
        ));
    }
}
