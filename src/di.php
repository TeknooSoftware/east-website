<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Common\Recipe\Step\InitParametersBag;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\Common\Service\DeletingService;
use Teknoo\East\Foundation\Recipe\RecipeInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\MediaRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Contracts\Recipe\Cookbook\RenderDynamicContentEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Cookbook\RenderMediaEndPointInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\GetStreamFromMediaInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\MediaLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\Middleware\LocaleMiddleware;
use Teknoo\East\Website\Middleware\MenuMiddleware;
use Teknoo\East\Website\Recipe\Cookbook\RenderDynamicContentEndPoint;
use Teknoo\East\Website\Recipe\Cookbook\RenderMediaEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\East\Website\Recipe\Step\LoadMedia;
use Teknoo\East\Website\Recipe\Step\SendMedia;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\East\Website\Writer\ContentWriter;
use Teknoo\East\Website\Writer\ItemWriter;
use Teknoo\East\Website\Writer\MediaWriter;
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
    MediaLoader::class => create(MediaLoader::class)
        ->constructor(get(MediaRepositoryInterface::class)),
    TypeLoader::class => create(TypeLoader::class)
        ->constructor(get(TypeRepositoryInterface::class)),

    //Writer
    ItemWriter::class => create(ItemWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    ContentWriter::class => create(ContentWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    MediaWriter::class => create(MediaWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    TypeWriter::class => create(TypeWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),

    //Deleting
    'teknoo.east.website.deleting.item' => create(DeletingService::class)
        ->constructor(get(ItemWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.content' => create(DeletingService::class)
        ->constructor(get(ContentWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.media' => create(DeletingService::class)
        ->constructor(get(MediaWriter::class), get(DatesService::class)),
    'teknoo.east.website.deleting.type' => create(DeletingService::class)
        ->constructor(get(TypeWriter::class), get(DatesService::class)),

    //Menu
    MenuGenerator::class => static function (ContainerInterface $container): MenuGenerator {
        return new MenuGenerator(
            $container->get(ItemLoader::class),
            $container->get(ContentLoader::class),
            $container->has(ProxyDetectorInterface::class) ? $container->get(ProxyDetectorInterface::class) : null
        );
    },

    MenuMiddleware::class => create(MenuMiddleware::class)
        ->constructor(get(MenuGenerator::class)),

    //Middleware
    RecipeInterface::class => decorate(static function ($previous, ContainerInterface $container) {
        if ($previous instanceof RecipeInterface) {
            $previous = $previous->cook(
                $container->get(InitParametersBag::class),
                InitParametersBag::class,
                [],
                4
            );

            if ($container->has(LocaleMiddleware::class)) {
                $previous = $previous->cook(
                    [$container->get(LocaleMiddleware::class), 'execute'],
                    LocaleMiddleware::class,
                    [],
                    LocaleMiddleware::MIDDLEWARE_PRIORITY
                );
            }

            $previous = $previous->cook(
                [$container->get(MenuMiddleware::class), 'execute'],
                MenuMiddleware::class,
                [],
                MenuMiddleware::MIDDLEWARE_PRIORITY
            );
        }

        return $previous;
    }),

    //Steps
    LoadContent::class => create()
        ->constructor(
            get(ContentLoader::class)
        ),
    LoadMedia::class => create()
        ->constructor(
            get(MediaLoader::class)
        ),
    SendMedia::class => create()
        ->constructor(
            get(ResponseFactoryInterface::class)
        ),

    //Base recipe
    OriginalRecipeInterface::class => get(Recipe::class),
    Recipe::class => create(),

    //Cookbook
    RenderDynamicContentEndPointInterface::class => get(RenderDynamicContentEndPoint::class),
    RenderDynamicContentEndPoint::class => create()
        ->constructor(
            get(OriginalRecipeInterface::class),
            get(ExtractSlug::class),
            get(LoadContent::class),
            get(Render::class),
            get(RenderError::class)
        ),
    RenderMediaEndPointInterface::class => get(RenderMediaEndPoint::class),
    RenderMediaEndPoint::class => create()
        ->constructor(
            get(OriginalRecipeInterface::class),
            get(LoadMedia::class),
            get(GetStreamFromMediaInterface::class),
            get(SendMedia::class),
            get(RenderError::class)
        ),
];
