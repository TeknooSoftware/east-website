<?php

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
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website\Object;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;
use Teknoo\East\Website\Object\Category\Hidden;
use Teknoo\East\Website\Object\Category\Available;
use Teknoo\States\LifeCycle\StatedClass\Automated\Assertion\Assertion;
use Teknoo\States\LifeCycle\StatedClass\Automated\AutomatedInterface;
use Teknoo\States\LifeCycle\StatedClass\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

class Category implements ProxyInterface, AutomatedInterface, Translatable
{
    use ProxyTrait,
        AutomatedTrait,
        ObjectTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $slug;
    
    /**
     * @var int
     */
    private $position;

    /**
     * @var bool
     */
    private $hidden=false;

    /**
     * @var Category|null
     */
    private $parent;

    /**
     * @var Category[]
     */
    private $children;

    /**
     * @var string
     */
    private $localeField;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
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
    public function getStatesAssertions(): array
    {
        return [
            (new Assertion([Hidden::class]))->with('hidden', new IsFalse()),
            (new Assertion([Available::class]))->with('hidden', new IsTrue()),
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): Category
    {
        $this->name = $name;

        return $this;
    }


    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return self
     */
    public function setSlug(string $slug): Category
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return self
     */
    public function setPosition(int $position): Category
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
    public function setHidden(bool $isHidden): Category
    {
        $this->hidden = $isHidden;

        return $this;
    }

    /**
     * @return null|Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param null|Category $parent
     * @return self
     */
    public function setParent(Category $parent=null): Category
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param Category[] $children
     * @return self
     */
    public function setChildren(array $children): Category
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocaleField(): string
    {
        return $this->localeField;
    }

    /**
     * @param string $localeField
     *
     * @return self
     */
    public function setLocaleField(string $localeField): Category
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
}