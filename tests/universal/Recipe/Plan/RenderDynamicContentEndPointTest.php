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
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicContentEndPoint;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RenderDynamicContentEndPoint::class)]
class RenderDynamicContentEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&Stub)|(RecipeInterface&MockObject)|null $recipe = null;

    private (ExtractSlug&Stub)|(ExtractSlug&MockObject)|null $extractSlug = null;

    private (LoadContent&Stub)|(LoadContent&MockObject)|null $loadContent = null;

    private (Render&Stub)|(Render&MockObject)|null $render = null;

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

    public function getExtractSlug(bool $stub = false): (ExtractSlug&Stub)|(ExtractSlug&MockObject)
    {
        if (!$this->extractSlug instanceof ExtractSlug) {
            if ($stub) {
                $this->extractSlug = $this->createStub(ExtractSlug::class);
            } else {
                $this->extractSlug = $this->createMock(ExtractSlug::class);
            }
        }

        return $this->extractSlug;
    }

    public function getLoadContent(bool $stub = false): (LoadContent&Stub)|(LoadContent&MockObject)
    {
        if (!$this->loadContent instanceof LoadContent) {
            if ($stub) {
                $this->loadContent = $this->createStub(LoadContent::class);
            } else {
                $this->loadContent = $this->createMock(LoadContent::class);
            }
        }

        return $this->loadContent;
    }

    public function getRender(bool $stub = false): (Render&Stub)|(Render&MockObject)
    {
        if (!$this->render instanceof Render) {
            if ($stub) {
                $this->render = $this->createStub(Render::class);
            } else {
                $this->render = $this->createMock(Render::class);
            }
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

    public function buildPlan(): RenderDynamicContentEndPoint
    {
        return new RenderDynamicContentEndPoint(
            $this->getRecipe(true),
            $this->getExtractSlug(true),
            $this->getLoadContent(true),
            $this->createStub(LoadTranslationsInterface::class),
            $this->getRender(true),
            $this->getRenderError(true)
        );
    }
}
