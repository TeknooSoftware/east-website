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

namespace Teknoo\Tests\East\Website\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Recipe\Plan\ListAllPostsEndPoint;
use Teknoo\East\Common\Recipe\Step\ExtractPage;
use Teknoo\East\Website\Recipe\Step\ListPosts;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListAllPostsEndPoint::class)]
class ListAllPostsEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private ?RecipeInterface $recipe = null;

    private ?ExtractPage $extractPage = null;

    private ?ListPosts $listPosts = null;

    private ?ListTags $listTags = null;

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
     * @return ExtractPage|MockObject
     */
    public function getExtractPage(): ExtractPage
    {
        if (null === $this->extractPage) {
            $this->extractPage = $this->createMock(ExtractPage::class);
        }

        return $this->extractPage;
    }

    /**
     * @return ListPosts|MockObject
     */
    public function getListPosts(): ListPosts
    {
        if (null === $this->listPosts) {
            $this->listPosts = $this->createMock(ListPosts::class);
        }

        return $this->listPosts;
    }

    /**
     * @return ListTags|MockObject
     */
    public function getListTags(): ListTags
    {
        if (null === $this->listTags) {
            $this->listTags = $this->createMock(ListTags::class);
        }

        return $this->listTags;
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

    public function buildPlan(): ListAllPostsEndPoint
    {
        return new ListAllPostsEndPoint(
            $this->getRecipe(),
            $this->getExtractPage(),
            $this->getListPosts(),
            $this->getListTags(),
            $this->createMock(LoadTranslationsInterface::class),
            $this->getRender(),
            $this->getRenderError()
        );
    }
}
