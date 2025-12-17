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
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Recipe\Step\DeleteObject;
use Teknoo\East\Common\Recipe\Step\JumpIf;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Website\Recipe\Plan\DeleteCommentOfPostEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;
use Teknoo\Recipe\EditablePlanInterface;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DeleteCommentOfPostEndPoint::class)]
class DeleteCommentOfPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&Stub)|(RecipeInterface&MockObject)|null $recipe = null;

    private (LoadPostFromRequest&Stub)|(LoadPostFromRequest&MockObject)|null $loadPostFromRequest = null;

    private (PrepareCriteriaFromPost&Stub)|(PrepareCriteriaFromPost&MockObject)|null $prepareCriteriaFromPost = null;

    private (LoadObject&Stub)|(LoadObject&MockObject)|null $loadObject = null;

    private (ObjectAccessControlInterface&Stub)|(ObjectAccessControlInterface&MockObject)|null $objectAccessControl = null;

    private (JumpIf&Stub)|(JumpIf&MockObject)|null $jumpIf = null;

    private (DeleteObject&Stub)|(DeleteObject&MockObject)|null $deleteObject = null;

    private (RedirectClientInterface&Stub)|(RedirectClientInterface&MockObject)|null $redirectClient = null;

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

    public function getLoadPostFromRequest(bool $stub = false): (LoadPostFromRequest&Stub)|(LoadPostFromRequest&MockObject)
    {
        if (!$this->loadPostFromRequest instanceof LoadPostFromRequest) {
            if ($stub) {
                $this->loadPostFromRequest = $this->createStub(LoadPostFromRequest::class);
            } else {
                $this->loadPostFromRequest = $this->createMock(LoadPostFromRequest::class);
            }
        }

        return $this->loadPostFromRequest;
    }

    public function getPrepareCriteriaFromPost(bool $stub = false): (PrepareCriteriaFromPost&Stub)|(PrepareCriteriaFromPost&MockObject)
    {
        if (!$this->prepareCriteriaFromPost instanceof PrepareCriteriaFromPost) {
            if ($stub) {
                $this->prepareCriteriaFromPost = $this->createStub(PrepareCriteriaFromPost::class);
            } else {
                $this->prepareCriteriaFromPost = $this->createMock(PrepareCriteriaFromPost::class);
            }
        }

        return $this->prepareCriteriaFromPost;
    }

    public function getLoadObject(bool $stub = false): (LoadObject&Stub)|(LoadObject&MockObject)
    {
        if (!$this->loadObject instanceof LoadObject) {
            if ($stub) {
                $this->loadObject = $this->createStub(LoadObject::class);
            } else {
                $this->loadObject = $this->createMock(LoadObject::class);
            }
        }

        return $this->loadObject;
    }

    public function getJumpIf(bool $stub = false): (JumpIf&Stub)|(JumpIf&MockObject)
    {
        if (!$this->jumpIf instanceof JumpIf) {
            if ($stub) {
                $this->jumpIf = $this->createStub(JumpIf::class);
            } else {
                $this->jumpIf = $this->createMock(JumpIf::class);
            }
        }

        return $this->jumpIf;
    }

    public function getDeleteObject(bool $stub = false): (DeleteObject&Stub)|(DeleteObject&MockObject)
    {
        if (!$this->deleteObject instanceof DeleteObject) {
            if ($stub) {
                $this->deleteObject = $this->createStub(DeleteObject::class);
            } else {
                $this->deleteObject = $this->createMock(DeleteObject::class);
            }
        }

        return $this->deleteObject;
    }

    public function getRedirectClient(bool $stub = false): (RedirectClientInterface&Stub)|(RedirectClientInterface&MockObject)
    {
        if (!$this->redirectClient instanceof RedirectClientInterface) {
            if ($stub) {
                $this->redirectClient = $this->createStub(RedirectClientInterface::class);
            } else {
                $this->redirectClient = $this->createMock(RedirectClientInterface::class);
            }
        }

        return $this->redirectClient;
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

    public function getObjectAccessControl(bool $stub = false): (ObjectAccessControlInterface&Stub)|(ObjectAccessControlInterface&MockObject)
    {
        if (!$this->objectAccessControl instanceof ObjectAccessControlInterface) {
            if ($stub) {
                $this->objectAccessControl = $this->createStub(ObjectAccessControlInterface::class);
            } else {
                $this->objectAccessControl = $this->createMock(ObjectAccessControlInterface::class);
            }
        }

        return $this->objectAccessControl;
    }


    public function buildPlan(): EditablePlanInterface
    {
        return new DeleteCommentOfPostEndPoint(
            $this->getRecipe(true),
            $this->getLoadPostFromRequest(true),
            $this->getPrepareCriteriaFromPost(true),
            $this->getLoadObject(true),
            $this->getDeleteObject(true),
            $this->getJumpIf(true),
            $this->getRedirectClient(true),
            $this->getRender(true),
            $this->getRenderError(true),
            $this->getObjectAccessControl(true),
        );
    }
}
