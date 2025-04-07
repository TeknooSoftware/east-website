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

namespace Teknoo\East\WebsiteBundle\Form\DTO;

use DateTimeInterface;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Object\Comment as CommentObject;
use Teknoo\East\Website\Object\Post;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

class Comment implements ObjectInterface
{
    public function __construct(
        public Post $post,
        public string $author = '',
        public string $title = '',
        public string $content = '',
    ) {
    }

    /**
     * @param WriterInterface<\Teknoo\East\Website\Object\Comment> $writer
     * @param class-string<CommentObject> $commentClass
     */
    public function persistInto(
        ManagerInterface $manager,
        WriterInterface $writer,
        string $commentClass,
        string $remoteIp,
        DateTimeInterface $postAt
    ): self {
        $writer->save(
            new $commentClass(
                post: $this->post,
                author: $this->author,
                remoteIp: $remoteIp,
                title: $this->title,
                content: $this->content,
                postAt: $postAt,
            ),
            new Promise(
                onSuccess: static function (CommentObject $comment) use ($manager): void {
                    $manager->updateWorkPlan([
                        CommentObject::class => $comment,
                        ObjectInterface::class => $comment,
                    ]);
                },
                onFail: fn (Throwable $throwable) => throw $throwable,
            ),
        );

        return $this;
    }
}
