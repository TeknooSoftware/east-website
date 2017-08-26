<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website;

use Doctrine\Common\Persistence\ObjectManager;
use Teknoo\East\Website\Loader\CategoryLoader;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\MediaLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\Loader\UserLoader;
use Teknoo\East\Website\Object\Category;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Media;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Object\User;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Website\Writer\CategoryWriter;
use Teknoo\East\Website\Writer\ContentWriter;
use Teknoo\East\Website\Writer\MediaWriter;
use Teknoo\East\Website\Writer\TypeWriter;
use Teknoo\East\Website\Writer\UserWriter;

return [
    CategoryLoader::class => function (ObjectManager $manager) {
        return new CategoryLoader($manager->getRepository(Category::class));
    },
    ContentLoader::class => function (ObjectManager $manager) {
        return new ContentLoader($manager->getRepository(Content::class));
    },
    MediaLoader::class => function (ObjectManager $manager) {
        return new MediaLoader($manager->getRepository(Media::class));
    },
    TypeLoader::class => function (ObjectManager $manager) {
        return new TypeLoader($manager->getRepository(Type::class));
    },
    UserLoader::class => function (ObjectManager $manager) {
        return new UserLoader($manager->getRepository(User::class));
    },

    CategoryWriter::class => function (ObjectManager $manager) {
        return new CategoryWriter($manager);
    },
    ContentWriter::class => function (ObjectManager $manager) {
        return new ContentWriter($manager);
    },
    MediaWriter::class => function (ObjectManager $manager) {
        return new MediaWriter($manager);
    },
    TypeWriter::class => function (ObjectManager $manager) {
        return new TypeWriter($manager);
    },
    UserWriter::class => function (ObjectManager $manager) {
        return new UserWriter($manager);
    },

    MenuGenerator::class => function (CategoryLoader $loader) {
        return new MenuGenerator($loader);
    },
];