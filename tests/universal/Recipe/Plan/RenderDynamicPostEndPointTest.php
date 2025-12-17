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
use PHPUnit\Framework\MockObject\Stub;
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

    private (RecipeInterface&Stub)|(RecipeInterface&MockObject)|null $recipe = null;

    private (LoadPost&Stub)|(LoadPost&MockObject)|null $loadPost = null;

    private (ListTags&Stub)|(ListTags&MockObject)|null $listTags = null;

    private ?Render $render = null;

    private (RenderError&Stub)|(RenderError&MockObject)|null $renderError = null;

    public function getRecipe(bool $stub = false): (RecipeInterface&Stub)|(RecipeInterface&MockObject)
    {
        if (!$this->recipe instanceof RecipeInterface) {
            if ($stub) {
                $this->recipe = $this->createStub(RecipeInterface::class);
            } else {
                $this->recipe = $this->createMock(RecipeInterface::class);
            }
        }

        return $this->recipe;
    }

    public function getLoadPost(bool $stub = false): (LoadPost&Stub)|(LoadPost&MockObject)
    {
        if (!$this->loadPost instanceof LoadPost) {
            if ($stub) {
                $this->loadPost = $this->createStub(LoadPost::class);
            } else {
                $this->loadPost = $this->createMock(LoadPost::class);
            }
        }

        return $this->loadPost;
    }

    public function getListTags(bool $stub = false): (ListTags&Stub)|(ListTags&MockObject)
    {
        if (!$this->listTags instanceof ListTags) {
            if ($stub) {
                $this->listTags = $this->createStub(ListTags::class);
            } else {
                $this->listTags = $this->createMock(ListTags::class);
            }
        }

        return $this->listTags;
    }

    public function getRender(): Render
    {
        if (null === $this->renderError) {
            $this->render = $this->createStub(Render::class);
        }

        return $this->render;
    }

    public function getRenderError(bool $stub = false): (RenderError&Stub)|(RenderError&MockObject)
    {
        if (!$this->renderError instanceof RenderError) {
            if ($stub) {
                $this->renderError = $this->createStub(RenderError::class);
            } else {
                $this->renderError = $this->createMock(RenderError::class);
            }
        }

        return $this->renderError;
    }

    public function buildPlan(): RenderDynamicPostEndPoint
    {
        return new RenderDynamicPostEndPoint(
            $this->getRecipe(true),
            $this->getLoadPost(true),
            $this->getListTags(true),
            $this->createStub(LoadTranslationsInterface::class),
            $this->getRender(true),
            $this->getRenderError(true)
        );
    }
}
