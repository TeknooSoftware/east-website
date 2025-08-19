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

namespace Teknoo\Tests\East\Website\Writer;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\Comment;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Writer\CommentWriter;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CommentWriter::class)]
class CommentWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(bool $preferRealDateOnUpdate = false): WriterInterface
    {
        return new CommentWriter($this->getObjectManager(), $this->getDatesServiceMock(), $preferRealDateOnUpdate);
    }

    public function getObject(): \Teknoo\East\Website\Object\Comment
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
}
