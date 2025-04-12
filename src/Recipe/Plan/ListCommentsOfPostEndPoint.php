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

namespace Teknoo\East\Website\Recipe\Plan;

use Stringable;
use Teknoo\East\Common\Contracts\Recipe\Step\ListObjectsAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\SearchFormLoaderInterface;
use Teknoo\East\Common\Recipe\Plan\ListObjectEndPoint;
use Teknoo\East\Common\Recipe\Step\ExtractOrder;
use Teknoo\East\Common\Recipe\Step\ExtractPage;
use Teknoo\East\Common\Recipe\Step\LoadListObjects;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\RenderList;
use Teknoo\East\Website\Contracts\Recipe\Plan\ListCommentsOfPostEndPointInterface;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\Plan\EditablePlanTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * HTTP EndPoint Recipe able to list and load all `Teknoo\East\Website\Object\Comment` instances for a specific post
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ListCommentsOfPostEndPoint extends ListObjectEndPoint implements ListCommentsOfPostEndPointInterface
{
    use EditablePlanTrait;

    public function __construct(
        RecipeInterface $recipe,
        ExtractPage $extractPage,
        ExtractOrder $extractOrder,
        private LoadPostFromRequest $loadAccountFromRequest,
        private PrepareCriteriaFromPost $prepareCriteriaFromPost,
        LoadListObjects $loadListObjects,
        RenderList $renderList,
        RenderError $renderError,
        SearchFormLoaderInterface $searchFormLoader,
        ?ListObjectsAccessControlInterface $listObjectsAccessControl,
        string|Stringable $defaultErrorTemplate,
        array $loadListObjectsWiths = [],
    ) {
        parent::__construct(
            recipe: $recipe,
            extractPage: $extractPage,
            extractOrder: $extractOrder,
            loadListObjects: $loadListObjects,
            renderList: $renderList,
            renderError: $renderError,
            searchFormLoader: $searchFormLoader,
            listObjectsAccessControl: $listObjectsAccessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
            loadListObjectsWiths: $loadListObjectsWiths
        );
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = parent::populateRecipe($recipe);
        $recipe = $recipe->require(new Ingredient(requiredType: 'string', name: 'postId'));

        $recipe = $recipe->cook($this->loadAccountFromRequest, LoadPostFromRequest::class, ['id' => 'postId'], 24);
        $recipe = $recipe->cook($this->prepareCriteriaFromPost, PrepareCriteriaFromPost::class, [], 25);

        return $recipe;
    }
}
