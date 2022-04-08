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

namespace Teknoo\East\WebsiteBundle\Resources\config;

use Psr\Container\ContainerInterface;
use Teknoo\East\Foundation\Recipe\RecipeInterface;
use Teknoo\East\WebsiteBundle\Middleware\LocaleMiddleware;

use function DI\decorate;

return [
    //Middleware
    LocaleMiddleware::class => static function (ContainerInterface $container): LocaleMiddleware {
        return new LocaleMiddleware($container->get('translator'));
    },

    RecipeInterface::class => decorate(static function ($previous, ContainerInterface $container) {
        if ($previous instanceof RecipeInterface) {
            $previous = $previous->cook(
                [$container->get(LocaleMiddleware::class), 'execute'],
                LocaleMiddleware::class,
                [],
                LocaleMiddleware::MIDDLEWARE_PRIORITY
            );
        }

        return $previous;
    }),
];
