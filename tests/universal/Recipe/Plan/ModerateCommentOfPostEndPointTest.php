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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ModerateCommentOfPostEndPoint::class)]
class ModerateCommentOfPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&Stub)|(RecipeInterface&MockObject)|null $recipe = null;

    private (LoadPostFromRequest&Stub)|(LoadPostFromRequest&MockObject)|null $loadPostFromRequest = null;

    private (PrepareCriteriaFromPost&Stub)|(PrepareCriteriaFromPost&MockObject)|null $prepareCriteriaFromPost = null;

    private (LoadObject&Stub)|(LoadObject&MockObject)|null $loadObject = null;

    private (ObjectAccessControlInterface&Stub)|(ObjectAccessControlInterface&MockObject)|null $objectAccessControl = null;

    private (FormHandlingInterface&Stub)|(FormHandlingInterface&MockObject)|null $formHandling = null;

    private (FormProcessingInterface&Stub)|(FormProcessingInterface&MockObject)|null $formProcessing = null;

    private (SlugPreparation&Stub)|(SlugPreparation&MockObject)|null $slugPreparation = null;

    private (SaveObject&Stub)|(SaveObject&MockObject)|null $saveObject = null;

    private (RenderFormInterface&Stub)|(RenderFormInterface&MockObject)|null $renderForm = null;

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

    public function getFormHandling(bool $stub = false): (FormHandlingInterface&Stub)|(FormHandlingInterface&MockObject)
    {
        if (!$this->formHandling instanceof FormHandlingInterface) {
            if ($stub) {
                $this->formHandling = $this->createStub(FormHandlingInterface::class);
            } else {
                $this->formHandling = $this->createMock(FormHandlingInterface::class);
            }
        }

        return $this->formHandling;
    }

    public function getFormProcessing(bool $stub = false): (FormProcessingInterface&Stub)|(FormProcessingInterface&MockObject)
    {
        if (!$this->formProcessing instanceof FormProcessingInterface) {
            if ($stub) {
                $this->formProcessing = $this->createStub(FormProcessingInterface::class);
            } else {
                $this->formProcessing = $this->createMock(FormProcessingInterface::class);
            }
        }

        return $this->formProcessing;
    }

    public function getSlugPreparation(bool $stub = false): (SlugPreparation&Stub)|(SlugPreparation&MockObject)
    {
        if (!$this->slugPreparation instanceof SlugPreparation) {
            if ($stub) {
                $this->slugPreparation = $this->createStub(SlugPreparation::class);
            } else {
                $this->slugPreparation = $this->createMock(SlugPreparation::class);
            }
        }

        return $this->slugPreparation;
    }

    public function getSaveObject(bool $stub = false): (SaveObject&Stub)|(SaveObject&MockObject)
    {
        if (!$this->saveObject instanceof SaveObject) {
            if ($stub) {
                $this->saveObject = $this->createStub(SaveObject::class);
            } else {
                $this->saveObject = $this->createMock(SaveObject::class);
            }
        }

        return $this->saveObject;
    }

    public function getRenderForm(bool $stub = false): (RenderFormInterface&Stub)|(RenderFormInterface&MockObject)
    {
        if (!$this->renderForm instanceof RenderFormInterface) {
            if ($stub) {
                $this->renderForm = $this->createStub(RenderFormInterface::class);
            } else {
                $this->renderForm = $this->createMock(RenderFormInterface::class);
            }
        }

        return $this->renderForm;
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
        return new ModerateCommentOfPostEndPoint(
            $this->getRecipe(true),
            $this->getLoadPostFromRequest(true),
            $this->getPrepareCriteriaFromPost(true),
            $this->getLoadObject(true),
            $this->getFormHandling(true),
            $this->getFormProcessing(true),
            $this->getSaveObject(true),
            $this->getRenderForm(true),
            $this->getRenderError(true),
            $this->getObjectAccessControl(true)
        );
    }
}
