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

namespace Teknoo\East\Website;

use Psr\Container\ContainerInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ListObjectsAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\SearchFormLoaderInterface;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\DeleteObject;
use Teknoo\East\Common\Recipe\Step\ExtractOrder;
use Teknoo\East\Common\Recipe\Step\ExtractPage;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Common\Recipe\Step\JumpIf;
use Teknoo\East\Common\Recipe\Step\LoadListObjects;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\RenderList;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Common\Service\DeletingService;
use Teknoo\East\Foundation\Recipe\PlanInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Contracts\DBSource\Repository\CommentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TagRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Translation\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\DeleteCommentOfPostEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\ListAllPostsEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\ListAllPostsOfTagsEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\ListCommentsOfPostEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\ModerateCommentOfPostEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\PostCommentOnPostEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\RenderDynamicContentEndPointInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\RenderDynamicPostEndPointInterface;
use Teknoo\East\Website\Loader\CommentLoader;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\Middleware\MenuMiddleware;
use Teknoo\East\Website\Recipe\Plan\DeleteCommentOfPostEndPoint;
use Teknoo\East\Website\Recipe\Plan\ListAllPostsEndPoint;
use Teknoo\East\Website\Recipe\Plan\ListAllPostsOfTagsEndPoint;
use Teknoo\East\Website\Recipe\Plan\ListCommentsOfPostEndPoint;
use Teknoo\East\Website\Recipe\Plan\ModerateCommentOfPostEndPoint;
use Teknoo\East\Website\Recipe\Plan\PostCommentOnPostEndPoint;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicContentEndPoint;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicPostEndPoint;
use Teknoo\East\Website\Recipe\Step\ExtractTag;
use Teknoo\East\Website\Recipe\Step\ListPosts;
use Teknoo\East\Website\Recipe\Step\ListTags;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\East\Website\Recipe\Step\LoadPost;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Website\Writer\CommentWriter;
use Teknoo\East\Website\Writer\ContentWriter;
use Teknoo\East\Website\Writer\ItemWriter;
use Teknoo\East\Website\Writer\PostWriter;
use Teknoo\East\Website\Writer\TagWriter;
use Teknoo\East\Website\Writer\TypeWriter;
use Teknoo\Recipe\Recipe;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

use function DI\create;
use function DI\decorate;
use function DI\get;
use function DI\value;

return [
    //Loaders
    CommentLoader::class => create(CommentLoader::class)
        ->constructor(get(CommentRepositoryInterface::class)),
    ItemLoader::class => create(ItemLoader::class)
        ->constructor(get(ItemRepositoryInterface::class)),
    ContentLoader::class => create(ContentLoader::class)
        ->constructor(get(ContentRepositoryInterface::class)),
    PostLoader::class => create(PostLoader::class)
        ->constructor(get(PostRepositoryInterface::class)),
    TagLoader::class => create(TagLoader::class)
        ->constructor(get(TagRepositoryInterface::class)),
    TypeLoader::class => create(TypeLoader::class)
        ->constructor(get(TypeRepositoryInterface::class)),

    //Writer
    CommentWriter::class => create(CommentWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    ItemWriter::class => create(ItemWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    ContentWriter::class => create(ContentWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    PostWriter::class => create(PostWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    TagWriter::class => create(TagWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    TypeWriter::class => create(TypeWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),

    //Deleting
    'teknoo.east.website.deleting.comment' => create(DeletingService::class)
        ->constructor(get(CommentWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.item' => create(DeletingService::class)
        ->constructor(get(ItemWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.content' => create(DeletingService::class)
        ->constructor(get(ContentWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.post' => create(DeletingService::class)
        ->constructor(get(PostWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.tag' => create(DeletingService::class)
        ->constructor(get(TagWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.type' => create(DeletingService::class)
        ->constructor(get(TypeWriter::class), get(DatesService::class)),

    //Menu
    MenuGenerator::class => static function (ContainerInterface $container): MenuGenerator {
        $defaultMenuLocations = [];
        if ($container->has('teknoo.east.website.menu_generator.default_locations')) {
            $defaultMenuLocations = $container->get('teknoo.east.website.menu_generator.default_locations');
        }

        $translationManager = null;
        if ($container->has(TranslationManagerInterface::class)) {
            $translationManager = $container->get(TranslationManagerInterface::class);
        }

        return new MenuGenerator(
            itemLoader: $container->get(ItemLoader::class),
            preloadItemsLocations: $defaultMenuLocations,
            translationManager: $translationManager,
        );
    },

    MenuMiddleware::class => create(MenuMiddleware::class)
        ->constructor(get(MenuGenerator::class)),

    //Middleware
    PlanInterface::class => decorate(static function (PlanInterface $previous, ContainerInterface $container) {
        /** @var MenuMiddleware $menuMiddleware */
        $previous = $previous->add(
            $container->get(MenuMiddleware::class)->execute(...),
            MenuMiddleware::MIDDLEWARE_PRIORITY
        );
        return $previous;
    }),

    //Steps
    ExtractTag::class => create()
        ->constructor(
            get(TagLoader::class),
        ),
    ListPosts::class => create()
        ->constructor(
            get(PostLoader::class),
            get(DatesService::class),
        ),
    ListTags::class => create()
        ->constructor(
            get(TagLoader::class),
            get(PostRepositoryInterface::class),
            get(DatesService::class),
        ),
    LoadContent::class => create()
        ->constructor(
            get(ContentLoader::class),
            get(DatesService::class),
        ),
    LoadPost::class => create()
        ->constructor(
            get(PostLoader::class),
            get(DatesService::class),
        ),
    LoadPostFromRequest::class => create()
        ->constructor(
            get(PostLoader::class),
        ),
    PrepareCriteriaFromPost::class => create(),

    //Base recipe
    OriginalRecipeInterface::class => get(Recipe::class),
    Recipe::class => create(),

    //Plan
    ListAllPostsOfTagsEndPointInterface::class => get(ListAllPostsOfTagsEndPoint::class),
    ListAllPostsOfTagsEndPoint::class => static function (
        ContainerInterface $container,
    ): ListAllPostsOfTagsEndPoint {
        $loadTranslations = null;
        if ($container->has(LoadTranslationsInterface::class)) {
            $loadTranslations = $container->get(LoadTranslationsInterface::class);
        }

        return new ListAllPostsOfTagsEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class),
            extractPage: $container->get(ExtractPage::class),
            extractTag: $container->get(ExtractTag::class),
            listPosts: $container->get(ListPosts::class),
            listTags: $container->get(ListTags::class),
            loadTranslationsInterface: $loadTranslations,
            render: $container->get(Render::class),
            renderError: $container->get(RenderError::class)
        );
    },

    ListAllPostsEndPointInterface::class => get(ListAllPostsEndPoint::class),
    ListAllPostsEndPoint::class => static function (
        ContainerInterface $container,
    ): ListAllPostsEndPoint {
        $loadTranslations = null;
        if ($container->has(LoadTranslationsInterface::class)) {
            $loadTranslations = $container->get(LoadTranslationsInterface::class);
        }

        return new ListAllPostsEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class),
            extractPage: $container->get(ExtractPage::class),
            listPosts: $container->get(ListPosts::class),
            listTags: $container->get(ListTags::class),
            loadTranslationsInterface: $loadTranslations,
            render: $container->get(Render::class),
            renderError: $container->get(RenderError::class)
        );
    },

    ListCommentsOfPostEndPointInterface::class => get(ListCommentsOfPostEndPoint::class),
    ListCommentsOfPostEndPoint::class => static function (
        ContainerInterface $container,
    ): ListCommentsOfPostEndPoint {
        $formLoader = null;
        if ($container->has(SearchFormLoaderInterface::class)) {
            $formLoader = $container->get(SearchFormLoaderInterface::class);
        }

        $accessControl = null;
        if ($container->has(ListObjectsAccessControlInterface::class)) {
            $accessControl = $container->get(ListObjectsAccessControlInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new ListCommentsOfPostEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class . ':CRUD'),
            extractPage: $container->get(ExtractPage::class),
            extractOrder: $container->get(ExtractOrder::class),
            loadAccountFromRequest: $container->get(LoadPostFromRequest::class),
            prepareCriteriaFromPost: $container->get(PrepareCriteriaFromPost::class),
            loadListObjects: $container->get(LoadListObjects::class),
            renderList: $container->get(RenderList::class),
            renderError: $container->get(RenderError::class),
            searchFormLoader: $formLoader,
            listObjectsAccessControl: $accessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
        );
    },

    ModerateCommentOfPostEndPointInterface::class => get(ModerateCommentOfPostEndPoint::class),
    ModerateCommentOfPostEndPoint::class => static function (
        ContainerInterface $container
    ): ModerateCommentOfPostEndPoint {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new ModerateCommentOfPostEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class . ':CRUD'),
            loadAccountFromRequest: $container->get(LoadPostFromRequest::class),
            prepareCriteriaFromPost: $container->get(PrepareCriteriaFromPost::class),
            loadObject: $container->get(LoadObject::class),
            formHandling: $container->get(FormHandlingInterface::class),
            formProcessing: $container->get(FormProcessingInterface::class),
            saveObject: $container->get(SaveObject::class),
            renderForm: $container->get(RenderFormInterface::class),
            renderError: $container->get(RenderError::class),
            objectAccessControl: $accessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
        );
    },
    DeleteCommentOfPostEndPointInterface::class => get(DeleteCommentOfPostEndPoint::class),
    DeleteCommentOfPostEndPoint::class => static function (
        ContainerInterface $container,
    ): DeleteCommentOfPostEndPoint {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new DeleteCommentOfPostEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class . ':CRUD'),
            loadAccountFromRequest: $container->get(LoadPostFromRequest::class),
            prepareCriteriaFromPost: $container->get(PrepareCriteriaFromPost::class),
            loadObject: $container->get(LoadObject::class),
            deleteObject: $container->get(DeleteObject::class),
            jumpIf: $container->get(JumpIf::class),
            redirectClient: $container->get(RedirectClientInterface::class),
            render: $container->get(Render::class),
            renderError: $container->get(RenderError::class),
            objectAccessControl: $accessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
        );
    },

    PostCommentOnPostEndPointInterface::class => get(PostCommentOnPostEndPoint::class),
    PostCommentOnPostEndPoint::class => static function (
        ContainerInterface $container,
    ): PostCommentOnPostEndPoint {
        $loadTranslations = null;
        if ($container->has(LoadTranslationsInterface::class)) {
            $loadTranslations = $container->get(LoadTranslationsInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new PostCommentOnPostEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class),
            loadPost: $container->get(LoadPost::class),
            listTags: $container->get(ListTags::class),
            loadTranslationsInterface: $loadTranslations,
            createObject: $container->get(CreateObject::class),
            formHandling: $container->get(FormHandlingInterface::class),
            formProcessing: $container->get(FormProcessingInterface::class),
            saveObject: $container->get(SaveObject::class),
            redirectClient: $container->get(RedirectClientInterface::class),
            renderForm: $container->get(RenderFormInterface::class),
            renderError: $container->get(RenderError::class),
            defaultErrorTemplate: $defaultErrorTemplate,
        );
    },

    RenderDynamicContentEndPointInterface::class => get(RenderDynamicContentEndPoint::class),
    RenderDynamicContentEndPoint::class => static function (
        ContainerInterface $container
    ): RenderDynamicContentEndPoint {
        $loadTranslations = null;
        if ($container->has(LoadTranslationsInterface::class)) {
            $loadTranslations = $container->get(LoadTranslationsInterface::class);
        }

        return new RenderDynamicContentEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class),
            extractSlug: $container->get(ExtractSlug::class),
            loadContent: $container->get(LoadContent::class),
            loadTranslationsInterface: $loadTranslations,
            render: $container->get(Render::class),
            renderError: $container->get(RenderError::class),
        );
    },
    RenderDynamicPostEndPointInterface::class => get(RenderDynamicPostEndPoint::class),
    RenderDynamicPostEndPoint::class => static function (
        ContainerInterface $container
    ): RenderDynamicPostEndPoint {
        $loadTranslations = null;
        if ($container->has(LoadTranslationsInterface::class)) {
            $loadTranslations = $container->get(LoadTranslationsInterface::class);
        }

        return new RenderDynamicPostEndPoint(
            recipe: $container->get(OriginalRecipeInterface::class),
            loadPost: $container->get(LoadPost::class),
            listTags: $container->get(ListTags::class),
            loadTranslationsInterface: $loadTranslations,
            render: $container->get(Render::class),
            renderError: $container->get(RenderError::class),
        );
    },
];
