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

namespace Teknoo\Tests\East\Website\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DeleteCommentOfPostEndPoint::class)]
class DeleteCommentOfPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&MockObject)|null $recipe = null;

    private (LoadPostFromRequest&MockObject)|null $loadPostFromRequest = null;

    private (PrepareCriteriaFromPost&MockObject)|null $prepareCriteriaFromPost = null;

    private (LoadObject&MockObject)|null $loadObject = null;

    private (ObjectAccessControlInterface&MockObject)|null $objectAccessControl = null;

    private (JumpIf&MockObject)|null $jumpIf = null;

    private (DeleteObject&MockObject)|null $deleteObject = null;

    private (RedirectClientInterface&MockObject)|null $redirectClient = null;

    private (Render&MockObject)|null $render = null;

    private (RenderError&MockObject)|null $renderError = null;

    public function getRecipe(): RecipeInterface&MockObject
    {
        if (null === $this->recipe) {
            $this->recipe = $this->createMock(RecipeInterface::class);
        }

        return $this->recipe;
    }

    public function getLoadPostFromRequest(): LoadPostFromRequest&MockObject
    {
        if (null === $this->loadPostFromRequest) {
            $this->loadPostFromRequest = $this->createMock(LoadPostFromRequest::class);
        }

        return $this->loadPostFromRequest;
    }

    public function getPrepareCriteriaFromPost(): PrepareCriteriaFromPost&MockObject
    {
        if (null === $this->prepareCriteriaFromPost) {
            $this->prepareCriteriaFromPost = $this->createMock(PrepareCriteriaFromPost::class);
        }

        return $this->prepareCriteriaFromPost;
    }

    public function getLoadObject(): LoadObject&MockObject
    {
        if (null === $this->loadObject) {
            $this->loadObject = $this->createMock(LoadObject::class);
        }

        return $this->loadObject;
    }

    public function getJumpIf(): JumpIf&MockObject
    {
        if (null === $this->jumpIf) {
            $this->jumpIf = $this->createMock(JumpIf::class);
        }

        return $this->jumpIf;
    }

    public function getDeleteObject(): DeleteObject&MockObject
    {
        if (null === $this->deleteObject) {
            $this->deleteObject = $this->createMock(DeleteObject::class);
        }

        return $this->deleteObject;
    }

    public function getRedirectClient(): RedirectClientInterface&MockObject
    {
        if (null === $this->redirectClient) {
            $this->redirectClient = $this->createMock(RedirectClientInterface::class);
        }

        return $this->redirectClient;
    }

    public function getRender(): Render&MockObject
    {
        if (null === $this->render) {
            $this->render = $this->createMock(Render::class);
        }

        return $this->render;
    }

    public function getRenderError(): RenderError&MockObject
    {
        if (null === $this->renderError) {
            $this->renderError = $this->createMock(RenderError::class);
        }

        return $this->renderError;
    }

    public function getObjectAccessControl(): ObjectAccessControlInterface&MockObject
    {
        if (null === $this->objectAccessControl) {
            $this->objectAccessControl = $this->createMock(ObjectAccessControlInterface::class);
        }

        return $this->objectAccessControl;
    }


    public function buildPlan(): EditablePlanInterface
    {
        return new DeleteCommentOfPostEndPoint(
            $this->getRecipe(),
            $this->getLoadPostFromRequest(),
            $this->getPrepareCriteriaFromPost(),
            $this->getLoadObject(),
            $this->getDeleteObject(),
            $this->getJumpIf(),
            $this->getRedirectClient(),
            $this->getRender(),
            $this->getRenderError(),
            $this->getObjectAccessControl(),
        );
    }
}
