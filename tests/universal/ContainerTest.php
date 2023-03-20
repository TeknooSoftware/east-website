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

namespace Teknoo\Tests\East\Website;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\East\Common\Recipe\Step\ExtractSlug;
use Teknoo\East\Common\Recipe\Step\Render;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Service\DeletingService;
use Teknoo\East\Foundation\Manager\Manager;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Router\RouterInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Cookbook\RenderDynamicContentEndPointInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface as DbManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\Middleware\MenuMiddleware;
use Teknoo\East\Website\Recipe\Cookbook\RenderDynamicContentEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadContent;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\East\Website\Writer\ContentWriter;
use Teknoo\East\Website\Writer\ItemWriter;
use Teknoo\East\Website\Writer\TypeWriter;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

/**
 * Class DefinitionProviderTest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
        $containerDefinition->addDefinitions(__DIR__.'/../../vendor/teknoo/east-foundation/src/di.php');
        $containerDefinition->addDefinitions(__DIR__ . '/../../src/di.php');

        return $containerDefinition->build();
    }

    private function generateTestForLoader(string $className, string $repositoryInterface)
    {
        $container = $this->buildContainer();
        $repository = $this->createMock($repositoryInterface);

        $container->set($repositoryInterface, $repository);
        $loader = $container->get($className);

        self::assertInstanceOf(
            $className,
            $loader
        );
    }

    public function testItemLoader()
    {
        $this->generateTestForLoader(ItemLoader::class, ItemRepositoryInterface::class);
    }

    public function testContentLoader()
    {
        $this->generateTestForLoader(ContentLoader::class, ContentRepositoryInterface::class);
    }

    public function testTypeLoader()
    {
        $this->generateTestForLoader(TypeLoader::class, TypeRepositoryInterface::class);
    }

    private function generateTestForWriter(string $className)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(DbManagerInterface::class);

        $container->set(DbManagerInterface::class, $objectManager);
        $loader = $container->get($className);

        self::assertInstanceOf(
            $className,
            $loader
        );
    }

    public function testItemWriter()
    {
        $this->generateTestForWriter(ItemWriter::class);
    }

    public function testContentWriter()
    {
        $this->generateTestForWriter(ContentWriter::class);
    }

    public function testTypeWriter()
    {
        $this->generateTestForWriter(TypeWriter::class);
    }

    private function generateTestForDelete(string $key)
    {
        $container = $this->buildContainer();
        $objectManager = $this->createMock(DbManagerInterface::class);

        $container->set(DbManagerInterface::class, $objectManager);
        $loader = $container->get($key);

        self::assertInstanceOf(
            DeletingService::class,
            $loader
        );
    }

    public function testItemDelete()
    {
        $this->generateTestForDelete('teknoo.east.website.deleting.item');
    }

    public function testContentDelete()
    {
        $this->generateTestForDelete('teknoo.east.website.deleting.content');
    }

    public function testTypeDelete()
    {
        $this->generateTestForDelete('teknoo.east.website.deleting.type');
    }

    public function testMenuGenerator()
    {
        $container = $this->buildContainer();
        $container->set(ItemRepositoryInterface::class, $this->createMock(ItemRepositoryInterface::class));
        $container->set(ContentRepositoryInterface::class, $this->createMock(ContentRepositoryInterface::class));
        $container->set(TranslationManagerInterface::class, $this->createMock(TranslationManagerInterface::class));
        $container->set(ProxyDetectorInterface::class, $this->createMock(ProxyDetectorInterface::class));
        $container->set('teknoo.east.website.menu_generator.default_locations', ['foo']);
        $loader = $container->get(MenuGenerator::class);

        self::assertInstanceOf(
            MenuGenerator::class,
            $loader
        );
    }

    public function testMenuMiddleware()
    {
        $container = $this->buildContainer();
        $container->set(ItemRepositoryInterface::class, $this->createMock(ItemRepositoryInterface::class));
        $container->set(ContentRepositoryInterface::class, $this->createMock(ContentRepositoryInterface::class));
        $loader = $container->get(MenuMiddleware::class);

        self::assertInstanceOf(
            MenuMiddleware::class,
            $loader
        );
    }

    public function testEastManagerMiddlewareInjection()
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../vendor/teknoo/east-foundation/src/di.php');
        $containerDefinition->addDefinitions(__DIR__ . '/../../src/di.php');

        $container = $containerDefinition->build();

        $container->set(LoggerInterface::class, $this->createMock(LoggerInterface::class));
        $container->set(RouterInterface::class, $this->createMock(RouterInterface::class));
        $container->set(ItemRepositoryInterface::class, $this->createMock(ItemRepositoryInterface::class));
        $container->set(ContentRepositoryInterface::class, $this->createMock(ContentRepositoryInterface::class));

        $manager1 = $container->get(Manager::class);
        $manager2 = $container->get(ManagerInterface::class);

        self::assertInstanceOf(
            Manager::class,
            $manager1
        );

        self::assertInstanceOf(
            Manager::class,
            $manager2
        );

        self::assertSame($manager1, $manager2);
    }

    public function testLoadContent()
    {
        $container = $this->buildContainer();
        $container->set(ContentRepositoryInterface::class, $this->createMock(ContentRepositoryInterface::class));

        self::assertInstanceOf(
            LoadContent::class,
            $container->get(LoadContent::class)
        );
    }

    public function testRenderDynamicContentEndPoint()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(ExtractSlug::class, $this->createMock(ExtractSlug::class));
        $container->set(LoadContent::class, $this->createMock(LoadContent::class));
        $container->set(Render::class, $this->createMock(Render::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        self::assertInstanceOf(
            RenderDynamicContentEndPoint::class,
            $container->get(RenderDynamicContentEndPoint::class)
        );

        self::assertInstanceOf(
            RenderDynamicContentEndPointInterface::class,
            $container->get(RenderDynamicContentEndPointInterface::class)
        );
    }
}
