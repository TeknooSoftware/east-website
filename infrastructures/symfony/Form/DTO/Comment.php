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

namespace Teknoo\East\WebsiteBundle\Form\DTO;

use DateTimeInterface;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Object\Comment as CommentObject;
use Teknoo\East\Website\Object\Post;

/**
 * DTO to represent a comment filled by an user to persist. The DTO own a method to create a persisted Comment Object
 * and pass it to the dedicated comment writer.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Comment implements ObjectInterface
{
    public function __construct(
        public Post $post,
        public ?string $author = '',
        public ?string $title = '',
        public ?string $content = '',
    ) {
    }

    /**
     * @param class-string<CommentObject> $commentClass
     */
    public function persistInto(
        ManagerInterface $manager,
        string $commentClass,
        string $remoteIp,
        DateTimeInterface $postAt
    ): self {
        $comment = new $commentClass(
            post: $this->post,
            author: (string) $this->author,
            remoteIp: $remoteIp,
            title: (string) $this->title,
            content: (string) $this->content,
            postAt: $postAt,
        );

        $manager->updateWorkPlan([
            CommentObject::class => $comment,
            ObjectInterface::class => $comment,
            'parameters' => [
                'slug' => $this->post->getSlug(),
            ],
        ]);

        return $this;
    }
}
