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

namespace Teknoo\Tests\East\Website\Doctrine;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Translation\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Translation\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Translation\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Translation\Doctrine\Translatable\TranslationManager;
use Teknoo\East\Translation\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Common\Middleware\LocaleMiddleware;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

/**
 * Class DefinitionProviderTest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(
            __DIR__.'/../../../vendor/teknoo/east-common/infrastructures/doctrine/di.php'
        );
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/doctrine/di.php');

        return $containerDefinition->build();
    }

    public function testManager()
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);

        $container->set(ObjectManager::class, $objectManager);
        self::assertInstanceOf(ManagerInterface::class, $container->get(ManagerInterface::class));
    }

    private function generateTestForRepository(string $objectClass, string $repositoryClass, string $repositoryType)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock($repositoryType)
        );

        $container->set(ObjectManager::class, $objectManager);
        $repository = $container->get($repositoryClass);

        self::assertInstanceOf(
            $repositoryClass,
            $repository
        );
    }

    private function generateTestForRepositoryWithUnsupportedRepository(string $objectClass, string $repositoryClass)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())->method('getRepository')->with($objectClass)->willReturn(
            $this->createMock(\DateTime::class)
        );

        $container->set(ObjectManager::class, $objectManager);
        $container->get($repositoryClass);
    }

    public function testItemRepositoryWithObjectRepository()
    {
        $this->generateTestForRepository(Item::class, ItemRepositoryInterface::class, ObjectRepository::class);
    }

    public function testContentRepositoryWithObjectRepository()
    {
        $this->generateTestForRepository(Content::class, ContentRepositoryInterface::class, ObjectRepository::class);
    }

    public function testTypeRepositoryWithObjectRepository()
    {
        $this->generateTestForRepository(Type::class, TypeRepositoryInterface::class, ObjectRepository::class);
    }

    public function testItemRepositoryWithDocumentRepository()
    {
        $this->generateTestForRepository(Item::class, ItemRepositoryInterface::class, DocumentRepository::class);
    }

    public function testContentRepositoryWithDocumentRepository()
    {
        $this->generateTestForRepository(Content::class, ContentRepositoryInterface::class, DocumentRepository::class);
    }

    public function testTypeRepositoryWithDocumentRepository()
    {
        $this->generateTestForRepository(Type::class, TypeRepositoryInterface::class, DocumentRepository::class);
    }

    public function testItemRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Item::class, ItemRepositoryInterface::class);
    }

    public function testContentRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Content::class, ContentRepositoryInterface::class);
    }

    public function testTypeRepositoryWithUnsupportedRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->generateTestForRepositoryWithUnsupportedRepository(Type::class, TypeRepositoryInterface::class);
    }

    public function testProxyDetectorInterface()
    {
        $container = $this->buildContainer();
        $proxyDetector = $container->get(ProxyDetectorInterface::class);

        $p1 = $this->createMock(PromiseInterface::class);
        $p1->expects($this->never())->method('success');
        $p1->expects($this->once())->method('fail');

        self::assertInstanceOf(
            ProxyDetectorInterface::class,
            $proxyDetector->checkIfInstanceBehindProxy(new \stdClass(), $p1)
        );

        $p2 = $this->createMock(PromiseInterface::class);
        $p2->expects($this->never())->method('success');
        $p2->expects($this->once())->method('fail');

        self::assertInstanceOf(
            ProxyDetectorInterface::class,
            $proxyDetector->checkIfInstanceBehindProxy(new class implements GhostObjectInterface {
                public function setProxyInitializer(?\Closure $initializer = null): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function getProxyInitializer(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function initializeProxy(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function isProxyInitialized(): bool
                {
                    return true;
                }
            }, $p2)
        );

        $p3 = $this->createMock(PromiseInterface::class);
        $p3->expects($this->once())->method('success');
        $p3->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ProxyDetectorInterface::class,
            $proxyDetector->checkIfInstanceBehindProxy(new class implements GhostObjectInterface {
                public function setProxyInitializer(?\Closure $initializer = null): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function getProxyInitializer(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function initializeProxy(): never
                {
                    throw new \RuntimeException('Must not be called');
                }

                public function isProxyInitialized(): bool
                {
                    return false;
                }
            }, $p3)
        );
    }

    public function testOriginalRecipeInterfaceStatic()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':Static', $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        self::assertInstanceOf(
            OriginalRecipeInterface::class,
            $container->get(OriginalRecipeInterface::class . ':Static')
        );
    }
}
