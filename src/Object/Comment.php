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

use DateTimeInterface;
use Teknoo\East\Common\Contracts\Object\DeletableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Website\Object\Comment\Moderated;
use Teknoo\East\Website\Object\Comment\Published;
use Teknoo\States\Attributes\Assertion\Property;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\Property\IsInstanceOf;
use Teknoo\States\Automated\Assertion\Property\IsNotInstanceOf;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Comment represent a comment posted by a visitor on a Post. A Comment has a post date, an author, title and content.
 * But a Comment can be moderated. Original values can not be replaced, only moderated value. Original value still
 * present in the object, but are not publicly accessible according to the Comment's state (Published or Moderated)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[StateClass(Published::class)]
#[StateClass(Moderated::class)]
#[Property(Published::class, ['moderatedAt', IsNotInstanceOf::class, DateTimeInterface::class])]
#[Property(Moderated::class, ['moderatedAt', IsInstanceOf::class, DateTimeInterface::class])]
class Comment implements
    IdentifiedObjectInterface,
    AutomatedInterface,
    DeletableInterface,
    TimestampableInterface
{
    use PublishableTrait;
    use AutomatedTrait;
    use ProxyTrait;

    protected Post $post;

    protected ?string $moderatedContent = null;

    protected ?string $moderatedTitle = null;

    protected ?string $moderatedAuthor = null;

    protected ?DateTimeInterface $moderatedAt = null;

    protected DateTimeInterface $postAt;

    protected string $content;

    protected string $title;

    protected string $remoteIp;

    protected string $author;

    public function __construct(
        Post $post,
        string $author,
        string $remoteIp,
        string $title,
        string $content,
        DateTimeInterface $postAt,
        ?DateTimeInterface $moderatedAt = null,
        ?string $moderatedAuthor = null,
        ?string $moderatedTitle = null,
        ?string $moderatedContent = null,
    ) {
        $this->author = $author;
        $this->remoteIp = $remoteIp;
        $this->title = $title;
        $this->content = $content;
        $this->postAt = $postAt;
        $this->moderatedAt = $moderatedAt;
        $this->moderatedAuthor = $moderatedAuthor;
        $this->moderatedTitle = $moderatedTitle;
        $this->moderatedContent = $moderatedContent;
        $this->post = $post;

        $this->initializeStateProxy();
        $this->updateStates();
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getPostAt(): DateTimeInterface
    {
        return $this->postAt;
    }

    public function getModeratedAt(): ?DateTimeInterface
    {
        return $this->moderatedAt;
    }

    public function getModeratedTitle(): ?string
    {
        return $this->moderatedTitle;
    }

    public function getModeratedAuthor(): ?string
    {
        return $this->moderatedAuthor;
    }

    public function getModeratedContent(): ?string
    {
        return $this->moderatedContent;
    }

    public function moderate(DateTimeInterface $date, string $author, string $title, string $content): self
    {
        $this->moderatedAt = $date;
        $this->moderatedAuthor = $author;
        $this->moderatedTitle = $title;
        $this->moderatedContent = $content;

        $this->updateStates();
        return $this;
    }
}
