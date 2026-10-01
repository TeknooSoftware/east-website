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
use Stringable;
use Teknoo\East\Common\Contracts\Object\DeletableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\SluggableInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Translation\Contracts\Object\TranslatableInterface;
use Teknoo\East\Website\Object\Item\Available;
use Teknoo\East\Website\Object\Item\Hidden;
use Teknoo\East\Common\Service\FindSlugService;
use Teknoo\East\Foundation\Normalizer\Object\AutoTrait;
use Teknoo\East\Foundation\Normalizer\Object\ClassGroup;
use Teknoo\East\Foundation\Normalizer\Object\Normalize;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\States\Attributes\Assertion\Property;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\Property\IsEqual;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Stated class representing a menu item in the website. They can be linked to a Content instance and is a child of
 * another menu item instance.
 * Object of this class can be translated.
 * Object of this class are normalizable, only the id, the title and the slug of the linked content, and only the id
 * and the name of the parent are exported.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements SluggableInterface<IdentifiedObjectInterface>
 */
#[StateClass(Hidden::class)]
#[StateClass(Available::class)]
#[Property(Hidden::class, ['hidden', IsEqual::class, true])]
#[Property(Available::class, ['hidden', IsEqual::class, false])]
#[ClassGroup('default', 'api', 'crud', 'digest')]
class Item implements
    IdentifiedObjectInterface,
    TranslatableInterface,
    AutomatedInterface,
    DeletableInterface,
    TimestampableInterface,
    SluggableInterface,
    Stringable,
    NormalizableInterface
{
    use AutomatedTrait;
    use ObjectTrait;
    use ProxyTrait;
    use AutoTrait;

    #[Normalize(['default', 'api', 'crud', 'digest'])]
    protected ?string $id = null;

    #[Normalize(['crud'])]
    protected ?DateTimeInterface $createdAt = null;

    #[Normalize(['crud'])]
    protected ?DateTimeInterface $updatedAt = null;

    #[Normalize(['default', 'api', 'crud', 'digest'])]
    protected string $name = '';

    #[Normalize(['api', 'crud', 'digest'])]
    protected ?string $slug = null;

    #[Normalize(['api', 'crud'], loader: 'exportContent')]
    protected ?Content $content = null;

    #[Normalize(['api', 'crud'])]
    protected ?int $position = null;

    #[Normalize(['api', 'crud'])]
    protected string $location = '';

    #[Normalize(['api', 'crud'])]
    protected bool $hidden = false;

    #[Normalize(['api', 'crud'], loader: 'exportParent')]
    protected ?Item $parent = null;

    /**
     * @var iterable<Item>
     */
    protected iterable $children = [];

    protected ?string $localeField = null;

    /*
     * Loaders are cached by the AutoTrait for all instances, they must only use the instance passed as argument
     */

    /**
     * @return array{id: string, title: string, slug: string|null}|null
     */
    protected static function exportContent(self $item): ?array
    {
        if (null === $item->content) {
            return null;
        }

        return [
            'id' => $item->content->getId(),
            'title' => $item->content->getTitle(),
            'slug' => $item->content->getSlug(),
        ];
    }

    /**
     * @return array{id: string, name: string}|null
     */
    protected static function exportParent(self $item): ?array
    {
        if (null === $item->parent) {
            return null;
        }

        return [
            'id' => $item->parent->getId(),
            'name' => $item->parent->getName(),
        ];
    }

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function prepareSlugNear(
        LoaderInterface $loader,
        FindSlugService $findSlugService,
        string $slugField
    ): SluggableInterface {
        $slugValue = $this->getSlug();
        if (empty($slugValue)) {
            $slugValue = $this->getName();
        }

        $findSlugService->process(
            $loader,
            $slugField,
            $this,
            [
                $slugValue
            ]
        );

        return $this;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = (string) $location;

        return $this;
    }

    public function getPosition(): int
    {
        return (int) $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }


    public function isHidden(): bool
    {
        return !empty($this->hidden);
    }

    public function setHidden(bool $isHidden): self
    {
        $this->hidden = $isHidden;

        return $this;
    }

    public function getParent(): ?Item
    {
        return $this->parent;
    }

    public function setParent(?Item $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return iterable<Item>
     */
    public function getChildren(): iterable
    {
        return $this->children;
    }

    /**
     * @param iterable<Item> $children
     */
    public function setChildren(iterable $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function getLocaleField(): ?string
    {
        return $this->localeField;
    }

    public function setLocaleField(?string $localeField): TranslatableInterface
    {
        $this->localeField = $localeField;

        return $this;
    }
}
