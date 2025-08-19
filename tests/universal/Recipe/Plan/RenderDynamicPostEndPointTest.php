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
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicPostEndPoint;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\East\Website\Recipe\Step\LoadPost;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RenderDynamicPostEndPoint::class)]
class RenderDynamicPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private ?RecipeInterface $recipe = null;

    private ?LoadPost $loadPost = null;

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
     * @return LoadPost|MockObject
     */
    public function getLoadPost(): LoadPost
    {
        if (null === $this->loadPost) {
            $this->loadPost = $this->createMock(LoadPost::class);
        }

        return $this->loadPost;
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
        if (null === $this->renderError) {
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

    public function buildPlan(): RenderDynamicPostEndPoint
    {
        return new RenderDynamicPostEndPoint(
            $this->getRecipe(),
            $this->getLoadPost(),
            $this->getListTags(),
            $this->createMock(LoadTranslationsInterface::class),
            $this->getRender(),
            $this->getRenderError()
        );
    }
}
