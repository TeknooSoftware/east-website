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

use Stringable;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Recipe\Plan\DeleteObjectEndPoint;
use Teknoo\East\Common\Recipe\Step\DeleteObject;
use Teknoo\East\Common\Recipe\Step\JumpIf;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Website\Contracts\Recipe\Plan\DeleteCommentOfPostEndPointInterface;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\Plan\EditablePlanTrait;
use Teknoo\Recipe\RecipeInterface;

/**
 * HTTP EndPoint Recipe able to delete a comment of a specific post and redirect  client to the comment list of the post
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DeleteCommentOfPostEndPoint extends DeleteObjectEndPoint implements DeleteCommentOfPostEndPointInterface
{
    use EditablePlanTrait;

    public function __construct(
        RecipeInterface $recipe,
        private LoadPostFromRequest $loadAccountFromRequest,
        private PrepareCriteriaFromPost $prepareCriteriaFromPost,
        public readonly LoadObject $loadObject,
        public readonly DeleteObject $deleteObject,
        public readonly JumpIf $jumpIf,
        public readonly RedirectClientInterface $redirectClient,
        public readonly Render $render,
        public readonly RenderError $renderError,
        public readonly ?ObjectAccessControlInterface $objectAccessControl,
        public readonly string|Stringable|null $defaultErrorTemplate = null,
        public readonly array $loadObjectWiths = [],
    ) {
        parent::__construct(
            recipe: $recipe,
            loadObject: $loadObject,
            deleteObject: $deleteObject,
            jumpIf: $jumpIf,
            redirectClient: $redirectClient,
            render: $render,
            renderError: $renderError,
            objectAccessControl: $objectAccessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
            loadObjectWiths: $loadObjectWiths,
        );
    }

    #[\Override]
    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = parent::populateRecipe($recipe);
        $recipe = $recipe->require(new Ingredient(requiredType: 'string', name: 'postId'));

        $recipe = $recipe->cook($this->loadAccountFromRequest, LoadPostFromRequest::class, ['id' => 'postId'], 04);
        $recipe = $recipe->cook($this->prepareCriteriaFromPost, PrepareCriteriaFromPost::class, [], 05);

        return $recipe;
    }
}
