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
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Common\Recipe\Step\SlugPreparation;
use Teknoo\East\Website\Recipe\Plan\ModerateCommentOfPostEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;
use Teknoo\Recipe\EditablePlanInterface;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ModerateCommentOfPostEndPoint::class)]
class ModerateCommentOfPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&MockObject)|null $recipe = null;

    private (LoadPostFromRequest&MockObject)|null $loadPostFromRequest = null;

    private (PrepareCriteriaFromPost&MockObject)|null $prepareCriteriaFromPost = null;

    private (LoadObject&MockObject)|null $loadObject = null;

    private (ObjectAccessControlInterface&MockObject)|null $objectAccessControl = null;

    private (FormHandlingInterface&MockObject)|null $formHandling = null;

    private (FormProcessingInterface&MockObject)|null $formProcessing = null;

    private (SlugPreparation&MockObject)|null $slugPreparation = null;

    private (SaveObject&MockObject)|null $saveObject = null;

    private (RenderFormInterface&MockObject)|null $renderForm = null;

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

    public function getFormHandling(): FormHandlingInterface&MockObject
    {
        if (null === $this->formHandling) {
            $this->formHandling = $this->createMock(FormHandlingInterface::class);
        }

        return $this->formHandling;
    }

    public function getFormProcessing(): FormProcessingInterface&MockObject
    {
        if (null === $this->formProcessing) {
            $this->formProcessing = $this->createMock(FormProcessingInterface::class);
        }

        return $this->formProcessing;
    }

    public function getSlugPreparation(): SlugPreparation&MockObject
    {
        if (null === $this->slugPreparation) {
            $this->slugPreparation = $this->createMock(SlugPreparation::class);
        }

        return $this->slugPreparation;
    }

    public function getSaveObject(): SaveObject&MockObject
    {
        if (null === $this->saveObject) {
            $this->saveObject = $this->createMock(SaveObject::class);
        }

        return $this->saveObject;
    }

    public function getRenderForm(): RenderFormInterface&MockObject
    {
        if (null === $this->renderForm) {
            $this->renderForm = $this->createMock(RenderFormInterface::class);
        }

        return $this->renderForm;
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
        return new ModerateCommentOfPostEndPoint(
            $this->getRecipe(),
            $this->getLoadPostFromRequest(),
            $this->getPrepareCriteriaFromPost(),
            $this->getLoadObject(),
            $this->getFormHandling(),
            $this->getFormProcessing(),
            $this->getSaveObject(),
            $this->getRenderForm(),
            $this->getRenderError(),
            $this->getObjectAccessControl()
        );
    }
}
