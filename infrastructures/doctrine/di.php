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

namespace Teknoo\East\Website\Doctrine;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\CommentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TagRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Doctrine\DBSource\Common\ContentRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\PostRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\CommentRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\ItemRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\TypeRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\TagRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\ContentRepository as OdmContentRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\PostRepository as OdmPostRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\CommentRepository as OdmCommentRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\ItemRepository as OdmItemRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\TypeRepository as OdmTypeRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\TagRepository as OdmTagRepository;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Website\Doctrine\Object\Post;
use Teknoo\East\Website\Doctrine\Object\Comment;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Object\Type;

return [
    ContentRepositoryInterface::class => static function (ContainerInterface $container): ContentRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Content::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmContentRepository($repository);
        }

        return new ContentRepository($repository);
    },

    PostRepositoryInterface::class => static function (ContainerInterface $container): PostRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Post::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmPostRepository($repository);
        }

        return new PostRepository($repository);
    },

    CommentRepositoryInterface::class => static function (ContainerInterface $container): CommentRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Comment::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmCommentRepository($repository);
        }

        return new CommentRepository($repository);
    },

    ItemRepositoryInterface::class => static function (ContainerInterface $container): ItemRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Item::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmItemRepository($repository);
        }

        return new ItemRepository($repository);
    },

    TypeRepositoryInterface::class => static function (ContainerInterface $container): TypeRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Type::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmTypeRepository($repository);
        }

        return new TypeRepository($repository);
    },

    TagRepositoryInterface::class => static function (ContainerInterface $container): TagRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Tag::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmTagRepository($repository);
        }

        return new TagRepository($repository);
    },
];
