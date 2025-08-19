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
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Recipe\Plan\PostCommentOnPostEndPoint;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\East\Website\Recipe\Step\LoadPost;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PostCommentOnPostEndPoint::class)]
class PostCommentOnPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&MockObject)|null $recipe = null;

    private (LoadPost&MockObject)|null $loadPost = null;

    private (ListTags&MockObject)|null $listTags = null;

    private (LoadTranslationsInterface&MockObject)|null $loadTranslations = null;

    private (CreateObject&MockObject)|null $createObject = null;

    private (FormHandlingInterface&MockObject)|null $formHandling = null;

    private (FormProcessingInterface&MockObject)|null $formProcessing = null;

    private (SaveObject&MockObject)|null $saveObject = null;

    private (RedirectClientInterface&MockObject)|null $redirectClient = null;

    private (RenderFormInterface&MockObject)|null $renderForm = null;

    private (RenderError&MockObject)|null $renderError = null;

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

    public function getLoadPost(): LoadPost&MockObject
    {
        if (null === $this->loadPost) {
            $this->loadPost = $this->createMock(LoadPost::class);
        }

        return $this->loadPost;
    }

    public function getListTags(): ListTags&MockObject
    {
        if (null === $this->listTags) {
            $this->listTags = $this->createMock(ListTags::class);
        }

        return $this->listTags;
    }

    public function getLoadTranslations(): LoadTranslationsInterface&MockObject
    {
        if (null === $this->loadTranslations) {
            $this->loadTranslations = $this->createMock(LoadTranslationsInterface::class);
        }

        return $this->loadTranslations;
    }

    public function getCreateObject(): CreateObject&MockObject
    {
        if (null === $this->createObject) {
            $this->createObject = $this->createMock(CreateObject::class);
        }

        return $this->createObject;
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

    public function getSaveObject(): SaveObject&MockObject
    {
        if (null === $this->saveObject) {
            $this->saveObject = $this->createMock(SaveObject::class);
        }

        return $this->saveObject;
    }

    public function getRedirectClient(): RedirectClientInterface&MockObject
    {
        if (null === $this->redirectClient) {
            $this->redirectClient = $this->createMock(RedirectClientInterface::class);
        }

        return $this->redirectClient;
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

    public function buildPlan(): PostCommentOnPostEndPoint
    {
        return new PostCommentOnPostEndPoint(
            $this->getRecipe(),
            $this->getLoadPost(),
            $this->getListTags(),
            $this->getLoadTranslations(),
            $this->getCreateObject(),
            $this->getFormHandling(),
            $this->getFormProcessing(),
            $this->getSaveObject(),
            $this->getRedirectClient(),
            $this->getRenderForm(),
            $this->getRenderError(),
            'foo',
        );
    }
}
