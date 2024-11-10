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
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website;

use Psr\Container\ContainerInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Service\DeletingService;
use Teknoo\East\Foundation\Recipe\PlanInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Translation\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Plan\RenderDynamicContentEndPointInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\Middleware\MenuMiddleware;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicContentEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Website\Writer\ContentWriter;
use Teknoo\East\Website\Writer\ItemWriter;
use Teknoo\East\Website\Writer\TypeWriter;
use Teknoo\Recipe\Recipe;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

use function DI\create;
use function DI\decorate;
use function DI\get;

return [
    //Loaders
    ItemLoader::class => create(ItemLoader::class)
        ->constructor(get(ItemRepositoryInterface::class)),
    ContentLoader::class => create(ContentLoader::class)
        ->constructor(get(ContentRepositoryInterface::class)),
    TypeLoader::class => create(TypeLoader::class)
        ->constructor(get(TypeRepositoryInterface::class)),

    //Writer
    ItemWriter::class => create(ItemWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    ContentWriter::class => create(ContentWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    TypeWriter::class => create(TypeWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),

    //Deleting
    'teknoo.east.website.deleting.item' => create(DeletingService::class)
        ->constructor(get(ItemWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.content' => create(DeletingService::class)
        ->constructor(get(ContentWriter::class), get(DatesService::class)),
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

        $proxyDetector = null;
        if ($container->has(ProxyDetectorInterface::class)) {
            $proxyDetector = $container->get(ProxyDetectorInterface::class);
        }

        return new MenuGenerator(
            itemLoader: $container->get(ItemLoader::class),
            contentLoader: $container->get(ContentLoader::class),
            proxyDetector: $proxyDetector,
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
    LoadContent::class => create()
        ->constructor(
            get(ContentLoader::class)
        ),

    //Base recipe
    OriginalRecipeInterface::class => get(Recipe::class),
    Recipe::class => create(),

    //Plan
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
            renderError: $container->get(RenderError::class)
        );
    },
];
