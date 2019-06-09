<?php

declare(strict_types=1);

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2019 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website\Object;

use Gedmo\Translatable\Translatable;
use Teknoo\East\Website\Object\Item\Hidden;
use Teknoo\East\Website\Object\Item\Available;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Property\IsEqual;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\UniversalPackage\States\Document\StandardTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Item implements ObjectInterface, ProxyInterface, AutomatedInterface, Translatable, DeletableInterface
{
    use StandardTrait,
        AutomatedTrait,
        ObjectTrait{
        AutomatedTrait::updateStates insteadof StandardTrait;
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var Content
     */
    private $content;
    
    /**
     * @var int
     */
    private $position;

    /**
     * @var string
     */
    private $location;

    /**
     * @var bool
     */
    private $hidden=false;

    /**
     * @var Item|null
     */
    private $parent;

    /**
     * @var Item[]
     */
    private $children;

    /**
     * @var string
     */
    private $localeField;

    /**
     * Item constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->children = [];
        $this->initializeProxy();
        $this->updateStates();
    }

    /**
     * {@inheritdoc}
     */
    public static function statesListDeclaration(): array
    {
        return [
            Hidden::class,
            Available::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function listAssertions(): array
    {
        return [
            (new Property([Hidden::class]))->with('hidden', new IsEqual(true)),
            (new Property([Available::class]))->with('hidden', new IsEqual(false)),
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): Item
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return (string) $this->slug;
    }

    /**
     * @param string $slug
     * @return self
     */
    public function setSlug(string $slug = null): Item
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Content
     */
    public function getContent(): ?Content
    {
        return $this->content;
    }

    /**
     * @param Content $content
     * @return self
     */
    public function setContent(Content $content = null): Item
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return (string) $this->location;
    }

    /**
     * @param string $location
     * @return self
     */
    public function setLocation(string $location): Item
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return (int) $this->position;
    }

    /**
     * @param int $position
     * @return self
     */
    public function setPosition(int $position): Item
    {
        $this->position = $position;

        return $this;
    }


    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return !empty($this->hidden);
    }

    /**
     * @param bool $isHidden
     * @return self
     */
    public function setHidden(bool $isHidden): Item
    {
        $this->hidden = $isHidden;

        return $this;
    }

    /**
     * @return null|Item
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param null|Item $parent
     * @return self
     */
    public function setParent(Item $parent = null): Item
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Item[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Item[] $children
     * @return self
     */
    public function setChildren($children): Item
    {
        if (!\is_array($children) && !$children instanceof \Traversable) {
            throw new \RuntimeException('Bad argument type for $children');
        }
        
        $this->children = $children;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocaleField(): string
    {
        return (string) $this->localeField;
    }

    /**
     * @param string $localeField
     *
     * @return self
     */
    public function setLocaleField(string $localeField): Item
    {
        $this->localeField = $localeField;

        return $this;
    }

    /**
     * Sets translatable locale
     *
     * @param string $locale
     *
     * @return self
     */
    public function setTranslatableLocale($locale)
    {
        $this->localeField = $locale;

        return $this;
    }

    /**
     * Sets translatable locale
     *
     * @return string
     */
    public function getTranslatableLocale(): string
    {
        return (string) $this->localeField;
    }
}
