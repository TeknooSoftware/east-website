<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Recipe\Cookbook;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Recipe\Cookbook\RenderDynamicContentEndPoint;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Cookbook\BaseCookbookTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Website\Recipe\Cookbook\RenderDynamicContentEndPoint
 */
class RenderDynamicContentEndPointTest extends TestCase
{
    use BaseCookbookTestTrait;

    private ?RecipeInterface $recipe = null;

    private ?ExtractSlug $extractSlug = null;

    private ?LoadContent $loadContent = null;

    private ?Render $render = null;

    private ?RenderError $renderError = null;

    /**
     * @return RecipeInterface|MockObject
     */
    public function getRecipe(): RecipeInterface
    {
        if (null === $this->recipe) {
            $this->recipe = $this->createMock(RecipeInterface::class);
        }

        return $this->recipe;
    }

    /**
     * @return ExtractSlug|MockObject
     */
    public function getExtractSlug(): ExtractSlug
    {
        if (null === $this->extractSlug) {
            $this->extractSlug = $this->createMock(ExtractSlug::class);
        }

        return $this->extractSlug;
    }

    /**
     * @return LoadContent|MockObject
     */
    public function getLoadContent(): LoadContent
    {
        if (null === $this->loadContent) {
            $this->loadContent = $this->createMock(LoadContent::class);
        }

        return $this->loadContent;
    }

    /**
     * @return Render|MockObject
     */
    public function getRender(): Render
    {
        if (null === $this->render) {
            $this->render = $this->createMock(Render::class);
        }

        return $this->render;
    }

    /**
     * @return RenderError|MockObject
     */
    public function getRenderError(): RenderError
    {
        if (null === $this->renderError) {
            $this->renderError = $this->createMock(RenderError::class);
        }

        return $this->renderError;
    }

    public function buildCookbook(): RenderDynamicContentEndPoint
    {
        return new RenderDynamicContentEndPoint(
            $this->getRecipe(),
            $this->getExtractSlug(),
            $this->getLoadContent(),
            $this->createMock(LoadTranslationsInterface::class),
            $this->getRender(),
            $this->getRenderError()
        );
    }
}
