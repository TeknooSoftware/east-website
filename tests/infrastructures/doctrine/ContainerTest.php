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

namespace Teknoo\Tests\East\Website\Doctrine;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Contracts\DBSource\Repository\CommentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\PostRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TagRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Website\Doctrine\Object\Post;
use Teknoo\East\Website\Doctrine\Object\Comment;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Object\Type;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

/**
 * Class DefinitionProviderTest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws \Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(
            __DIR__.'/../../../vendor/teknoo/east-common/infrastructures/doctrine/di.php'
        );
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/doctrine/di.php');

        return $containerDefinition->build();
    }

    public function testManager(): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);

        $container->set(ObjectManager::class, $objectManager);
        $this->assertInstanceOf(ManagerInterface::class, $container->get(ManagerInterface::class));
    }

    private function generateTestForRepository(string $objectClass, string $repositoryClass, string $repositoryType): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock($repositoryType)
        );

        $container->set(ObjectManager::class, $objectManager);
        $repository = $container->get($repositoryClass);

        $this->assertInstanceOf($repositoryClass, $repository);
    }

    public function testItemRepositoryWithObjectRepository(): void
    {
        $this->generateTestForRepository(Item::class, ItemRepositoryInterface::class, ObjectRepository::class);
    }

    public function testContentRepositoryWithObjectRepository(): void
    {
        $this->generateTestForRepository(Content::class, ContentRepositoryInterface::class, ObjectRepository::class);
    }

    public function testTagRepositoryWithObjectRepository(): void
    {
        $this->generateTestForRepository(Tag::class, TagRepositoryInterface::class, ObjectRepository::class);
    }

    public function testPostRepositoryWithObjectRepository(): void
    {
        $this->generateTestForRepository(Post::class, PostRepositoryInterface::class, ObjectRepository::class);
    }

    public function testCommentRepositoryWithObjectRepository(): void
    {
        $this->generateTestForRepository(Comment::class, CommentRepositoryInterface::class, ObjectRepository::class);
    }

    public function testTypeRepositoryWithObjectRepository(): void
    {
        $this->generateTestForRepository(Type::class, TypeRepositoryInterface::class, ObjectRepository::class);
    }

    public function testItemRepositoryWithDocumentRepository(): void
    {
        $this->generateTestForRepository(Item::class, ItemRepositoryInterface::class, DocumentRepository::class);
    }

    public function testContentRepositoryWithDocumentRepository(): void
    {
        $this->generateTestForRepository(Content::class, ContentRepositoryInterface::class, DocumentRepository::class);
    }

    public function testTagRepositoryWithDocumentRepository(): void
    {
        $this->generateTestForRepository(Tag::class, TagRepositoryInterface::class, DocumentRepository::class);
    }

    public function testPostRepositoryWithDocumentRepository(): void
    {
        $this->generateTestForRepository(Post::class, PostRepositoryInterface::class, DocumentRepository::class);
    }

    public function testCommentRepositoryWithDocumentRepository(): void
    {
        $this->generateTestForRepository(Comment::class, CommentRepositoryInterface::class, DocumentRepository::class);
    }

    public function testTypeRepositoryWithDocumentRepository(): void
    {
        $this->generateTestForRepository(Type::class, TypeRepositoryInterface::class, DocumentRepository::class);
    }

    public function testOriginalRecipeInterfaceStatic(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':Static', $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        $this->assertInstanceOf(OriginalRecipeInterface::class, $container->get(OriginalRecipeInterface::class . ':Static'));
    }
}
