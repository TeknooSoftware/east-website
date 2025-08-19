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

namespace Teknoo\East\Website\Object;

/**
 * Post is a special content who can be commented by used. Posts can be listed in list, optionaly filtered by a tag.
 * Content represent a static page of a website, a post represent an article in a blog
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Post extends Content
{
    /**
     * @var iterable<Comment>
     */
    protected iterable $comments = [];

    /**
     * @return iterable<Comment>
     */
    public function getComments(): iterable
    {
        return $this->comments;
    }

    /**
     * @param iterable<Comment> $comments
     */
    public function setComments(iterable $comments): self
    {
        $this->comments = $comments;

        return $this;
    }
}
