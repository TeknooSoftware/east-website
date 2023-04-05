<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Recipe\Cookbook;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Website\Contracts\Recipe\Cookbook\RenderDynamicContentEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Cookbook\BaseCookbookTrait;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * HTTP EndPoint Recipe able to load a `Teknoo\East\Website\Object\Content` instance and render a
 * page via a template engine and send it to the client.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class RenderDynamicContentEndPoint implements RenderDynamicContentEndPointInterface
{
    use BaseCookbookTrait;

    public function __construct(
        RecipeInterface $recipe,
        private readonly ExtractSlug $extractSlug,
        private readonly LoadContent $loadContent,
        private readonly ?LoadTranslationsInterface $loadTranslationsInterface,
        private readonly Render $render,
        private readonly RenderError $renderError
    ) {
        $this->fill($recipe);
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $recipe = $recipe->cook($this->extractSlug, ExtractSlug::class, [], 10);

        $recipe = $recipe->cook($this->loadContent, LoadContent::class, [], 20);

        if (null !== $this->loadTranslationsInterface) {
            $recipe = $recipe->cook($this->loadTranslationsInterface, LoadTranslationsInterface::class, [], 25);
        }

        $recipe = $recipe->cook($this->render, Render::class, [], 30);

        return $recipe->onError(new Bowl($this->renderError, []));
    }
}
