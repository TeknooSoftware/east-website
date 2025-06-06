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

namespace Teknoo\Tests\East\Website;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
use Teknoo\East\Foundation\Manager\Manager;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Router\RouterInterface;
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
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface as DbManagerInterface;
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

    public function testCommentLoader()
    {
        $this->generateTestForLoader(CommentLoader::class, CommentRepositoryInterface::class);
    }

    public function testTagLoader()
    {
        $this->generateTestForLoader(TagLoader::class, TagRepositoryInterface::class);
    }

    public function testPostLoader()
    {
        $this->generateTestForLoader(PostLoader::class, PostRepositoryInterface::class);
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

    public function testCommentWriter()
    {
        $this->generateTestForWriter(CommentWriter::class);
    }

    public function testTagWriter()
    {
        $this->generateTestForWriter(TagWriter::class);
    }

    public function testPostWriter()
    {
        $this->generateTestForWriter(PostWriter::class);
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

    public function testLoadPost()
    {
        $container = $this->buildContainer();
        $container->set(PostRepositoryInterface::class, $this->createMock(PostRepositoryInterface::class));

        self::assertInstanceOf(
            LoadPost::class,
            $container->get(LoadPost::class)
        );
    }

    public function testListAllPostsEndPoint()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(ExtractPage::class, $this->createMock(ExtractPage::class));
        $container->set(ListPosts::class, $this->createMock(ListPosts::class));
        $container->set(ListTags::class, $this->createMock(ListTags::class));
        $container->set(Render::class, $this->createMock(Render::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        self::assertInstanceOf(
            ListAllPostsEndPoint::class,
            $container->get(ListAllPostsEndPoint::class)
        );

        self::assertInstanceOf(
            ListAllPostsEndPointInterface::class,
            $container->get(ListAllPostsEndPointInterface::class)
        );
    }
    public function testListCommentsOfPostEndPoint()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':CRUD', $this->createMock(OriginalRecipeInterface::class));
        $container->set(SearchFormLoaderInterface::class, $this->createMock(SearchFormLoaderInterface::class));
        $container->set(ListObjectsAccessControlInterface::class, $this->createMock(ListObjectsAccessControlInterface::class));
        $container->set(ExtractPage::class, $this->createMock(ExtractPage::class));
        $container->set(ExtractOrder::class, $this->createMock(ExtractOrder::class));
        $container->set(LoadPostFromRequest::class, $this->createMock(LoadPostFromRequest::class));
        $container->set(PrepareCriteriaFromPost::class, $this->createMock(PrepareCriteriaFromPost::class));
        $container->set(LoadListObjects::class, $this->createMock(LoadListObjects::class));
        $container->set(RenderList::class, $this->createMock(RenderList::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        self::assertInstanceOf(
            ListCommentsOfPostEndPoint::class,
            $container->get(ListCommentsOfPostEndPoint::class)
        );

        self::assertInstanceOf(
            ListCommentsOfPostEndPointInterface::class,
            $container->get(ListCommentsOfPostEndPointInterface::class)
        );
    }

    public function testModerateCommentOfPostEndPointInterface()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':CRUD', $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadPostFromRequest::class, $this->createMock(LoadPostFromRequest::class));
        $container->set(PrepareCriteriaFromPost::class, $this->createMock(PrepareCriteriaFromPost::class));
        $container->set(LoadObject::class, $this->createMock(LoadObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(ObjectAccessControlInterface::class, $this->createMock(ObjectAccessControlInterface::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        self::assertInstanceOf(
            ModerateCommentOfPostEndPoint::class,
            $container->get(ModerateCommentOfPostEndPoint::class)
        );

        self::assertInstanceOf(
            ModerateCommentOfPostEndPointInterface::class,
            $container->get(ModerateCommentOfPostEndPointInterface::class)
        );
    }

    public function testDeleteCommentOfPostEndPointInterface()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':CRUD', $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadPostFromRequest::class, $this->createMock(LoadPostFromRequest::class));
        $container->set(PrepareCriteriaFromPost::class, $this->createMock(PrepareCriteriaFromPost::class));
        $container->set(LoadObject::class, $this->createMock(LoadObject::class));
        $container->set(DeleteObject::class, $this->createMock(DeleteObject::class));
        $container->set(JumpIf::class, $this->createMock(JumpIf::class));
        $container->set(RedirectClientInterface::class, $this->createMock(RedirectClientInterface::class));
        $container->set(Render::class, $this->createMock(Render::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(ObjectAccessControlInterface::class, $this->createMock(ObjectAccessControlInterface::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        self::assertInstanceOf(
            DeleteCommentOfPostEndPoint::class,
            $container->get(DeleteCommentOfPostEndPoint::class)
        );

        self::assertInstanceOf(
            DeleteCommentOfPostEndPointInterface::class,
            $container->get(DeleteCommentOfPostEndPointInterface::class)
        );
    }

    public function testListAllPostsOfTagsEndPoint()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(ExtractPage::class, $this->createMock(ExtractPage::class));
        $container->set(ExtractTag::class, $this->createMock(ExtractTag::class));
        $container->set(ListPosts::class, $this->createMock(ListPosts::class));
        $container->set(ListTags::class, $this->createMock(ListTags::class));
        $container->set(Render::class, $this->createMock(Render::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        self::assertInstanceOf(
            ListAllPostsOfTagsEndPoint::class,
            $container->get(ListAllPostsOfTagsEndPoint::class)
        );

        self::assertInstanceOf(
            ListAllPostsOfTagsEndPointInterface::class,
            $container->get(ListAllPostsOfTagsEndPointInterface::class)
        );
    }

    public function testPostCommentOnPostEndPoint()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadPost::class, $this->createMock(LoadPost::class));
        $container->set(ListTags::class, $this->createMock(ListTags::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));
        $container->set(CreateObject::class, $this->createMock(CreateObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RedirectClientInterface::class, $this->createMock(RedirectClientInterface::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        self::assertInstanceOf(
            PostCommentOnPostEndPoint::class,
            $container->get(PostCommentOnPostEndPoint::class)
        );

        self::assertInstanceOf(
            PostCommentOnPostEndPointInterface::class,
            $container->get(PostCommentOnPostEndPointInterface::class)
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

    public function testRenderDynamicPostEndPoint()
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(ExtractSlug::class, $this->createMock(ExtractSlug::class));
        $container->set(LoadPost::class, $this->createMock(LoadPost::class));
        $container->set(ListTags::class, $this->createMock(ListTags::class));
        $container->set(Render::class, $this->createMock(Render::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createMock(LoadTranslationsInterface::class));

        self::assertInstanceOf(
            RenderDynamicPostEndPoint::class,
            $container->get(RenderDynamicPostEndPoint::class)
        );

        self::assertInstanceOf(
            RenderDynamicPostEndPointInterface::class,
            $container->get(RenderDynamicPostEndPointInterface::class)
        );
    }
}
