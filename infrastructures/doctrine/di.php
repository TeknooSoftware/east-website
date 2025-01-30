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

namespace Teknoo\East\Website\Doctrine;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Doctrine\DBSource\Common\ContentRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\ItemRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\TypeRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\ContentRepository as OdmContentRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\ItemRepository as OdmItemRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\TypeRepository as OdmTypeRepository;
use Teknoo\East\Website\Doctrine\Exception\NotSupportedException;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Website\Object\Type;

return [
    ContentRepositoryInterface::class => static function (ContainerInterface $container): ContentRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Content::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmContentRepository($repository);
        }

        $repository = $container->get(ObjectManager::class)->getRepository(Content::class);
        if ($repository instanceof ObjectRepository) {
            return new ContentRepository($repository);
        }

        throw new NotSupportedException(sprintf(
            "Error, repository of class %s are not currently managed",
            $repository::class
        ));
    },

    ItemRepositoryInterface::class => static function (ContainerInterface $container): ItemRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Item::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmItemRepository($repository);
        }

        if ($repository instanceof ObjectRepository) {
            return new ItemRepository($repository);
        }

        throw new NotSupportedException(sprintf(
            "Error, repository of class %s are not currently managed",
            $repository::class
        ));
    },

    TypeRepositoryInterface::class => static function (ContainerInterface $container): TypeRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Type::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmTypeRepository($repository);
        }

        if ($repository instanceof ObjectRepository) {
            return new TypeRepository($repository);
        }

        throw new NotSupportedException(sprintf(
            "Error, repository of class %s are not currently managed",
            $repository::class
        ));
    },
];
