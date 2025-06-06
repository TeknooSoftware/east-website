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

namespace Teknoo\East\Website\Object;

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
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Property\IsEqual;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Stated class representing a menu item in the website. They can be linked to a Content instance and is a child of
 * another menu item instance.
 * Object of this class can be translated.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements SluggableInterface<IdentifiedObjectInterface>
 */
class Item implements
    IdentifiedObjectInterface,
    TranslatableInterface,
    AutomatedInterface,
    DeletableInterface,
    TimestampableInterface,
    SluggableInterface,
    Stringable
{
    use AutomatedTrait;
    use ObjectTrait;
    use ProxyTrait;

    protected string $name = '';

    protected ?string $slug = null;

    protected ?Content $content = null;

    protected ?int $position = null;

    protected string $location = '';

    protected bool $hidden = false;

    protected ?Item $parent = null;

    /**
     * @var iterable<Item>
     */
    protected iterable $children = [];

    protected ?string $localeField = null;

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Hidden::class,
            Available::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property([Hidden::class]))->with('hidden', new IsEqual(true)),
            (new Property([Available::class]))->with('hidden', new IsEqual(false)),
        ];
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
