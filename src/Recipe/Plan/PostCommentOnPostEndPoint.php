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

namespace Teknoo\East\Website\Recipe\Plan;

use Psr\Http\Message\ServerRequestInterface;
use Stringable;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Website\Contracts\Recipe\Plan\PostCommentOnPostEndPointInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\East\Website\Recipe\Step\LoadPost;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\Plan\EditablePlanTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * HTTP EndPoint Recipe able to create a new comment and persist it for a specific post
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PostCommentOnPostEndPoint implements PostCommentOnPostEndPointInterface
{
    use EditablePlanTrait;

    public function __construct(
        RecipeInterface $recipe,
        private readonly LoadPost $loadPost,
        private readonly ListTags $listTags,
        private readonly ?LoadTranslationsInterface $loadTranslationsInterface,
        private readonly CreateObject $createObject,
        private readonly FormHandlingInterface $formHandling,
        private readonly FormProcessingInterface $formProcessing,
        private readonly SaveObject $saveObject,
        private readonly RedirectClientInterface $redirectClient,
        private readonly RenderFormInterface $renderForm,
        private readonly RenderError $renderError,
        private readonly string|Stringable|null $defaultErrorTemplate = null,
    ) {
        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));
        $recipe = $recipe->require(new Ingredient(requiredType: WriterInterface::class, name: 'writer'));
        $recipe = $recipe->require(new Ingredient(requiredType: 'string', name: 'objectClass'));
        $recipe = $recipe->require(new Ingredient(requiredType: 'string', name: 'formClass'));
        $recipe = $recipe->require(new Ingredient(requiredType: 'string', name: 'route'));

        $recipe = $recipe->cook($this->loadPost, LoadPost::class, [], 10);

        $recipe = $recipe->cook($this->listTags, ListTags::class, [], 10);

        if (null !== $this->loadTranslationsInterface) {
            $recipe = $recipe->cook($this->loadTranslationsInterface, LoadTranslationsInterface::class, [], 15);
        }

        $recipe = $recipe->cook(
            $this->createObject,
            CreateObject::class,
            [
                'constructorArguments' => Post::class
            ],
            20,
        );

        $recipe = $recipe->cook($this->formHandling, FormHandlingInterface::class, [], 30);

        $recipe = $recipe->cook($this->formProcessing, FormProcessingInterface::class, [], 40);

        $recipe = $recipe->cook($this->saveObject, SaveObject::class, [], 50);

        $recipe = $recipe->cook($this->redirectClient, RedirectClientInterface::class, [], 60);

        $recipe = $recipe->cook($this->renderForm, RenderFormInterface::class, [], 70);

        $recipe = $recipe->onError(new Bowl($this->renderError, []));

        $this->addToWorkplan('nextStep', RenderFormInterface::class);

        if (null !== $this->defaultErrorTemplate) {
            $this->addToWorkplan('errorTemplate', (string) $this->defaultErrorTemplate);
        }

        return $recipe->onError(new Bowl($this->renderError, []));
    }
}
