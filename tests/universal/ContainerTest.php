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
        $containerDefinition->addDefinitions(__DIR__.'/../../vendor/teknoo/east-foundation/src/di.php');
        $containerDefinition->addDefinitions(__DIR__ . '/../../src/di.php');

        return $containerDefinition->build();
    }

    private function generateTestForLoader(string $className, string $repositoryInterface): void
    {
        $container = $this->buildContainer();
        $repository = $this->createStub($repositoryInterface);

        $container->set($repositoryInterface, $repository);
        $loader = $container->get($className);

        $this->assertInstanceOf($className, $loader);
    }

    public function testItemLoader(): void
    {
        $this->generateTestForLoader(ItemLoader::class, ItemRepositoryInterface::class);
    }

    public function testCommentLoader(): void
    {
        $this->generateTestForLoader(CommentLoader::class, CommentRepositoryInterface::class);
    }

    public function testTagLoader(): void
    {
        $this->generateTestForLoader(TagLoader::class, TagRepositoryInterface::class);
    }

    public function testPostLoader(): void
    {
        $this->generateTestForLoader(PostLoader::class, PostRepositoryInterface::class);
    }

    public function testContentLoader(): void
    {
        $this->generateTestForLoader(ContentLoader::class, ContentRepositoryInterface::class);
    }

    public function testTypeLoader(): void
    {
        $this->generateTestForLoader(TypeLoader::class, TypeRepositoryInterface::class);
    }

    private function generateTestForWriter(string $className): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createStub(DbManagerInterface::class);

        $container->set(DbManagerInterface::class, $objectManager);
        $loader = $container->get($className);

        $this->assertInstanceOf($className, $loader);
    }

    public function testItemWriter(): void
    {
        $this->generateTestForWriter(ItemWriter::class);
    }

    public function testContentWriter(): void
    {
        $this->generateTestForWriter(ContentWriter::class);
    }

    public function testCommentWriter(): void
    {
        $this->generateTestForWriter(CommentWriter::class);
    }

    public function testTagWriter(): void
    {
        $this->generateTestForWriter(TagWriter::class);
    }

    public function testPostWriter(): void
    {
        $this->generateTestForWriter(PostWriter::class);
    }

    public function testTypeWriter(): void
    {
        $this->generateTestForWriter(TypeWriter::class);
    }

    private function generateTestForDelete(string $key): void
    {
        $container = $this->buildContainer();
        $objectManager = $this->createStub(DbManagerInterface::class);

        $container->set(DbManagerInterface::class, $objectManager);
        $loader = $container->get($key);

        $this->assertInstanceOf(DeletingService::class, $loader);
    }

    public function testItemDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.website.deleting.item');
    }

    public function testContentDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.website.deleting.content');
    }

    public function testTypeDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.website.deleting.type');
    }

    public function testMenuGenerator(): void
    {
        $container = $this->buildContainer();
        $container->set(ItemRepositoryInterface::class, $this->createStub(ItemRepositoryInterface::class));
        $container->set(ContentRepositoryInterface::class, $this->createStub(ContentRepositoryInterface::class));
        $container->set(TranslationManagerInterface::class, $this->createStub(TranslationManagerInterface::class));
        $container->set('teknoo.east.website.menu_generator.default_locations', ['foo']);
        $loader = $container->get(MenuGenerator::class);

        $this->assertInstanceOf(MenuGenerator::class, $loader);
    }

    public function testMenuMiddleware(): void
    {
        $container = $this->buildContainer();
        $container->set(ItemRepositoryInterface::class, $this->createStub(ItemRepositoryInterface::class));
        $container->set(ContentRepositoryInterface::class, $this->createStub(ContentRepositoryInterface::class));
        $loader = $container->get(MenuMiddleware::class);

        $this->assertInstanceOf(MenuMiddleware::class, $loader);
    }

    public function testEastManagerMiddlewareInjection(): void
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../vendor/teknoo/east-foundation/src/di.php');
        $containerDefinition->addDefinitions(__DIR__ . '/../../src/di.php');

        $container = $containerDefinition->build();

        $container->set(LoggerInterface::class, $this->createStub(LoggerInterface::class));
        $container->set(RouterInterface::class, $this->createStub(RouterInterface::class));
        $container->set(ItemRepositoryInterface::class, $this->createStub(ItemRepositoryInterface::class));
        $container->set(ContentRepositoryInterface::class, $this->createStub(ContentRepositoryInterface::class));

        $manager1 = $container->get(Manager::class);
        $manager2 = $container->get(ManagerInterface::class);

        $this->assertInstanceOf(Manager::class, $manager1);

        $this->assertInstanceOf(Manager::class, $manager2);

        $this->assertSame($manager1, $manager2);
    }

    public function testLoadContent(): void
    {
        $container = $this->buildContainer();
        $container->set(ContentRepositoryInterface::class, $this->createStub(ContentRepositoryInterface::class));

        $this->assertInstanceOf(LoadContent::class, $container->get(LoadContent::class));
    }

    public function testLoadPost(): void
    {
        $container = $this->buildContainer();
        $container->set(PostRepositoryInterface::class, $this->createStub(PostRepositoryInterface::class));

        $this->assertInstanceOf(LoadPost::class, $container->get(LoadPost::class));
    }

    public function testListAllPostsEndPoint(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createStub(OriginalRecipeInterface::class));
        $container->set(ExtractPage::class, $this->createStub(ExtractPage::class));
        $container->set(ListPosts::class, $this->createStub(ListPosts::class));
        $container->set(ListTags::class, $this->createStub(ListTags::class));
        $container->set(Render::class, $this->createStub(Render::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createStub(LoadTranslationsInterface::class));

        $this->assertInstanceOf(ListAllPostsEndPoint::class, $container->get(ListAllPostsEndPoint::class));

        $this->assertInstanceOf(ListAllPostsEndPointInterface::class, $container->get(ListAllPostsEndPointInterface::class));
    }

    public function testListCommentsOfPostEndPoint(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':CRUD', $this->createStub(OriginalRecipeInterface::class));
        $container->set(SearchFormLoaderInterface::class, $this->createStub(SearchFormLoaderInterface::class));
        $container->set(ListObjectsAccessControlInterface::class, $this->createStub(ListObjectsAccessControlInterface::class));
        $container->set(ExtractPage::class, $this->createStub(ExtractPage::class));
        $container->set(ExtractOrder::class, $this->createStub(ExtractOrder::class));
        $container->set(LoadPostFromRequest::class, $this->createStub(LoadPostFromRequest::class));
        $container->set(PrepareCriteriaFromPost::class, $this->createStub(PrepareCriteriaFromPost::class));
        $container->set(LoadListObjects::class, $this->createStub(LoadListObjects::class));
        $container->set(RenderList::class, $this->createStub(RenderList::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        $this->assertInstanceOf(ListCommentsOfPostEndPoint::class, $container->get(ListCommentsOfPostEndPoint::class));

        $this->assertInstanceOf(ListCommentsOfPostEndPointInterface::class, $container->get(ListCommentsOfPostEndPointInterface::class));
    }

    public function testModerateCommentOfPostEndPointInterface(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':CRUD', $this->createStub(OriginalRecipeInterface::class));
        $container->set(LoadPostFromRequest::class, $this->createStub(LoadPostFromRequest::class));
        $container->set(PrepareCriteriaFromPost::class, $this->createStub(PrepareCriteriaFromPost::class));
        $container->set(LoadObject::class, $this->createStub(LoadObject::class));
        $container->set(FormHandlingInterface::class, $this->createStub(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createStub(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createStub(SaveObject::class));
        $container->set(RenderFormInterface::class, $this->createStub(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set(ObjectAccessControlInterface::class, $this->createStub(ObjectAccessControlInterface::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        $this->assertInstanceOf(ModerateCommentOfPostEndPoint::class, $container->get(ModerateCommentOfPostEndPoint::class));

        $this->assertInstanceOf(ModerateCommentOfPostEndPointInterface::class, $container->get(ModerateCommentOfPostEndPointInterface::class));
    }

    public function testDeleteCommentOfPostEndPointInterface(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class . ':CRUD', $this->createStub(OriginalRecipeInterface::class));
        $container->set(LoadPostFromRequest::class, $this->createStub(LoadPostFromRequest::class));
        $container->set(PrepareCriteriaFromPost::class, $this->createStub(PrepareCriteriaFromPost::class));
        $container->set(LoadObject::class, $this->createStub(LoadObject::class));
        $container->set(DeleteObject::class, $this->createStub(DeleteObject::class));
        $container->set(JumpIf::class, $this->createStub(JumpIf::class));
        $container->set(RedirectClientInterface::class, $this->createStub(RedirectClientInterface::class));
        $container->set(Render::class, $this->createStub(Render::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set(ObjectAccessControlInterface::class, $this->createStub(ObjectAccessControlInterface::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        $this->assertInstanceOf(DeleteCommentOfPostEndPoint::class, $container->get(DeleteCommentOfPostEndPoint::class));

        $this->assertInstanceOf(DeleteCommentOfPostEndPointInterface::class, $container->get(DeleteCommentOfPostEndPointInterface::class));
    }

    public function testListAllPostsOfTagsEndPoint(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createStub(OriginalRecipeInterface::class));
        $container->set(ExtractPage::class, $this->createStub(ExtractPage::class));
        $container->set(ExtractTag::class, $this->createStub(ExtractTag::class));
        $container->set(ListPosts::class, $this->createStub(ListPosts::class));
        $container->set(ListTags::class, $this->createStub(ListTags::class));
        $container->set(Render::class, $this->createStub(Render::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createStub(LoadTranslationsInterface::class));

        $this->assertInstanceOf(ListAllPostsOfTagsEndPoint::class, $container->get(ListAllPostsOfTagsEndPoint::class));

        $this->assertInstanceOf(ListAllPostsOfTagsEndPointInterface::class, $container->get(ListAllPostsOfTagsEndPointInterface::class));
    }

    public function testPostCommentOnPostEndPoint(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createStub(OriginalRecipeInterface::class));
        $container->set(LoadPost::class, $this->createStub(LoadPost::class));
        $container->set(ListTags::class, $this->createStub(ListTags::class));
        $container->set(LoadTranslationsInterface::class, $this->createStub(LoadTranslationsInterface::class));
        $container->set(CreateObject::class, $this->createStub(CreateObject::class));
        $container->set(FormHandlingInterface::class, $this->createStub(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createStub(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createStub(SaveObject::class));
        $container->set(RedirectClientInterface::class, $this->createStub(RedirectClientInterface::class));
        $container->set(RenderFormInterface::class, $this->createStub(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set('teknoo.east.common.get_default_error_template', 'foo.bar');

        $this->assertInstanceOf(PostCommentOnPostEndPoint::class, $container->get(PostCommentOnPostEndPoint::class));

        $this->assertInstanceOf(PostCommentOnPostEndPointInterface::class, $container->get(PostCommentOnPostEndPointInterface::class));
    }

    public function testRenderDynamicContentEndPoint(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createStub(OriginalRecipeInterface::class));
        $container->set(ExtractSlug::class, $this->createStub(ExtractSlug::class));
        $container->set(LoadContent::class, $this->createStub(LoadContent::class));
        $container->set(Render::class, $this->createStub(Render::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createStub(LoadTranslationsInterface::class));

        $this->assertInstanceOf(RenderDynamicContentEndPoint::class, $container->get(RenderDynamicContentEndPoint::class));

        $this->assertInstanceOf(RenderDynamicContentEndPointInterface::class, $container->get(RenderDynamicContentEndPointInterface::class));
    }

    public function testRenderDynamicPostEndPoint(): void
    {
        $container = $this->buildContainer();
        $container->set(OriginalRecipeInterface::class, $this->createStub(OriginalRecipeInterface::class));
        $container->set(ExtractSlug::class, $this->createStub(ExtractSlug::class));
        $container->set(LoadPost::class, $this->createStub(LoadPost::class));
        $container->set(ListTags::class, $this->createStub(ListTags::class));
        $container->set(Render::class, $this->createStub(Render::class));
        $container->set(RenderError::class, $this->createStub(RenderError::class));
        $container->set(LoadTranslationsInterface::class, $this->createStub(LoadTranslationsInterface::class));

        $this->assertInstanceOf(RenderDynamicPostEndPoint::class, $container->get(RenderDynamicPostEndPoint::class));

        $this->assertInstanceOf(RenderDynamicPostEndPointInterface::class, $container->get(RenderDynamicPostEndPointInterface::class));
    }
}
