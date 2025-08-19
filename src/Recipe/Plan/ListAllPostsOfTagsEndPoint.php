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
use Teknoo\East\Common\Recipe\Step\ExtractPage;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\ListAllPostsOfTagsEndPointInterface;
use Teknoo\East\Website\Recipe\Step\ExtractTag;
use Teknoo\East\Website\Recipe\Step\ListPosts;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\Plan\EditablePlanTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * HTTP EndPoint Recipe able to list and load all `Teknoo\East\Website\Object\Post` instances with  a specific tag
 * via a pagination system.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ListAllPostsOfTagsEndPoint implements ListAllPostsOfTagsEndPointInterface
{
    use EditablePlanTrait;

    public function __construct(
        RecipeInterface $recipe,
        private readonly ExtractPage $extractPage,
        private readonly ExtractTag $extractTag,
        private readonly ListPosts $listPosts,
        private readonly ListTags $listTags,
        private readonly ?LoadTranslationsInterface $loadTranslationsInterface,
        private readonly Render $render,
        private readonly RenderError $renderError
    ) {
        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));
        $recipe = $recipe->require(new Ingredient('string', 'tag'));
        $recipe = $recipe->require(new Ingredient('int', 'itemsPerPage'));

        $recipe = $recipe->cook($this->extractPage, ExtractPage::class, [], 00);

        $recipe = $recipe->cook($this->extractTag, ExtractTag::class, [], 10);

        $recipe = $recipe->cook($this->listPosts, ListPosts::class, [], 20);

        $recipe = $recipe->cook($this->listTags, ListTags::class, [], 30);

        if (null !== $this->loadTranslationsInterface) {
            $recipe = $recipe->cook($this->loadTranslationsInterface, LoadTranslationsInterface::class, [], 40);
        }

        $recipe = $recipe->cook($this->render, Render::class, [], 50);

        return $recipe->onError(new Bowl($this->renderError, []));
    }
}
