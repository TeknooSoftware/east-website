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

namespace Teknoo\Tests\East\Website\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use DI\Container;
use DI\ContainerBuilder;
use DateTime;
use DateTimeInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionObject;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Teknoo\DI\SymfonyBridge\DIBridgeBundle;
use Teknoo\East\CommonBundle\Object\PasswordAuthenticatedUser;
use Teknoo\East\CommonBundle\TeknooEastCommonBundle;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\GetStreamFromMediaInterface;
use Teknoo\East\Common\Loader\MediaLoader;
use Teknoo\East\Common\Object\Media as BaseMedia;
use Teknoo\East\Common\Object\Media;
use Teknoo\East\Common\Object\StoredPassword;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Common\Recipe\Plan\RenderMediaEndPoint;
use Teknoo\East\Common\Recipe\Plan\RenderStaticContentEndPoint;
use Teknoo\East\FoundationBundle\EastFoundationBundle;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Client\ResponseInterface as EastResponse;
use Teknoo\East\Foundation\EndPoint\RecipeEndPoint;
use Teknoo\East\Foundation\Manager\Manager;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Middleware\MiddlewareInterface;
use Teknoo\East\Foundation\Recipe\PlanInterface;
use Teknoo\East\Foundation\Router\Result;
use Teknoo\East\Foundation\Router\ResultInterface as RouterResultInterface;
use Teknoo\East\Foundation\Router\RouterInterface;
use Teknoo\East\Foundation\Template\EngineInterface;
use Teknoo\East\Foundation\Template\ResultInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Twig\Template\Engine;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Recipe\Plan\ListAllPostsEndPoint;
use Teknoo\East\Website\Recipe\Plan\ListAllPostsOfTagsEndPoint;
use Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Post;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\Object\Block;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicContentEndPoint;
use Teknoo\East\Website\Recipe\Plan\RenderDynamicPostEndPoint;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;
use Twig\Environment;

use function array_key_exists;
use function array_pop;
use function array_reverse;
use function bin2hex;
use function current;
use function dirname;
use function error_reporting;
use function explode;
use function fopen;
use function in_array;
use function is_numeric;
use function json_decode;
use function json_encode;
use function parse_str;
use function preg_match;
use function random_bytes;
use function random_int;
use function str_replace;
use function strlen;
use function trim;

use const E_USER_NOTICE;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    public ?Container $container = null;

    private ?BaseKernel $symfonyKernel = null;

    private ?RouterInterface $router = null;

    public string $locale = 'en';

    private ?ClientInterface $client = null;

    public array $objectRepository = [];

    private ?MediaLoader $mediaLoader = null;

    private ?ContentLoader $contentLoader = null;

    private ?ItemLoader $itemLoader = null;

    private ?TypeLoader $typeLoader = null;

    private ?RecipeEndPoint $mediaEndPoint = null;

    private ?RecipeEndPoint $contentEndPoint = null;

    private ?RecipeEndPoint $postsEndPoint = null;

    private ?RecipeEndPoint $postEndPoint = null;

    private ?RecipeEndPoint $staticEndPoint = null;

    private ?Type $type = null;

    public ?EngineInterface $templating = null;

    public ?Environment $twig = null;

    public ?string $templateToCall = null;

    public ?string $templateContent = null;

    public ?ResponseInterface $response = null;

    public ?Throwable $error = null;

    public $createdObjects = [];

    public $updatedObjects = [];

    public $tags = [];

    private ?DateTimeInterface $now = null;

    #[BeforeScenario]
    public function prepareScenario(): void
    {
        error_reporting(E_ALL);
    }

    #[Given('I have DI initialized')]
    public function iHaveDiInitialized(): void
    {
        $containerDefinition = new ContainerBuilder();
        $rootDir = dirname(__DIR__, 2);
        $containerDefinition->addDefinitions(
            include $rootDir.'/vendor/teknoo/east-foundation/src/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir.'/vendor/teknoo/east-common/src/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir.'/vendor/teknoo/east-common/infrastructures/doctrine/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir.'/vendor/teknoo/east-common/infrastructures/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir . '/src/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir.'/infrastructures/doctrine/di.php'
        );

        $this->container = $containerDefinition->build();

        $this->container->set(ObjectManager::class, $this->buildObjectManager());

        $this->container->get(DatesService::class)->setCurrentDate($this->getCurrentDate());
    }

    private function getCurrentDate(): DateTimeInterface
    {
        return $this->now ??= new DateTime();
    }

    #[Given('I have DI With Symfony initialized')]
    public function iHaveDiWithSymfonyInitialized(): void
    {
        $this->locale = 'en';
        $this->symfonyKernel = new class ($this, 'test') extends BaseKernel {
            use MicroKernelTrait;

            private FeatureContext $context;

            public function __construct(FeatureContext $context, string $environment)
            {
                $this->context = $context;

                parent::__construct($environment, false);
            }

            public function getProjectDir(): string
            {
                return dirname(__DIR__, 2);
            }

            public function getCacheDir(): string
            {
                return dirname(__DIR__).'/var/cache';
            }

            public function getLogDir(): string
            {
                return dirname(__DIR__).'/var/logs';
            }

            public function registerBundles(): iterable
            {
                yield new FrameworkBundle();
                yield new EastFoundationBundle();
                yield new TeknooEastCommonBundle();
                yield new TeknooEastWebsiteBundle();
                yield new DIBridgeBundle();
                yield new SecurityBundle();
            }

            protected function configureContainer(SfContainerBuilder $container, LoaderInterface $loader)
            {
                $loader->load(__DIR__.'/config/packages/*.yaml', 'glob');
                $loader->load(__DIR__.'/config/services.yaml');

                $container->setParameter('container.autowiring.strict_mode', true);
                $container->setParameter('container.dumper.inline_class_loader', true);
            }

            protected function configureRoutes($routes): void
            {
                $thisDir = __DIR__;
                $rootDir = dirname(__DIR__, 2);
                if ($routes instanceof RoutingConfigurator) {
                    $routes->import($rootDir . '/infrastructures/symfony/Resources/config/admin_*.yaml', 'glob')
                        ->prefix('/admin');
                    $routes->import($thisDir . '/config/routes/*.yaml', 'glob');
                    $routes->import($rootDir . '/infrastructures/symfony/Resources/config/r*.yaml', 'glob');
                } else {
                    $routes->import($rootDir . '/infrastructures/symfony/Resources/config/admin_*.yaml', '/admin', 'glob');
                    $routes->import($thisDir . '/config/routes/*.yaml', '/', 'glob');
                    $routes->import($rootDir . '/infrastructures/symfony/Resources/config/r*.yaml', '/', 'glob');
                }
            }

            protected function getContainerClass(): string
            {
                $characters = 'abcdefghijklmnopqrstuvwxyz';
                $str = '';
                for ($i = 0; $i < 10; ++$i) {
                    $str .= $characters[random_int(0, strlen($characters) - 1)];
                }

                return $str;
            }
        };
    }

    public function buildObjectManager(): ObjectManager
    {
        return new readonly class ($this) implements ObjectManager {
            private FeatureContext $featureContext;

            /**
             *  constructor.
             */
            public function __construct(FeatureContext $featureContext)
            {
                $this->featureContext = $featureContext;
            }

            public function find($className, $id): ?object
            {
            }

            /**
             * @param IdentifiedObjectInterface $object
             */
            public function persist($object): void
            {
                if ($id = $object->getId()) {
                    $this->featureContext->updatedObjects[$id] = $object;
                } else {
                    $object->setId(bin2hex(random_bytes(23)));
                    $class = explode('\\', $object::class);
                    $this->featureContext->createdObjects[array_pop($class)][] = $object;

                    $this->featureContext->getObjectRepository($object::class)->setObject(['id' => $object->getId()], $object);
                }
            }

            public function remove($object): void
            {
            }

            public function clear($objectName = null): void
            {
            }

            public function detach($object): void
            {
            }

            public function refresh($object): void
            {
            }

            public function flush(): void
            {
            }

            public function getRepository($className): ObjectRepository
            {
                return $this->featureContext->getObjectRepository($className);
            }

            public function getClassMetadata($className): ClassMetadata
            {
            }

            public function getMetadataFactory(): ClassMetadataFactory
            {
            }

            public function initializeObject($obj): void
            {
            }

            public function contains($object): bool
            {
            }

            public function isUninitializedObject(mixed $value): bool
            {
                return false;
            }
        };
    }

    public function getObjectRepository(string $className): ObjectRepository
    {
        return $this->objectRepository[$className] ??= new class ($className) implements ObjectRepository {
            private readonly string $className;

            /**
             * @var object
             */
            private $object;

            private array $allObjects = [];

            private array $criteria = [];

            public function __construct(string $className)
            {
                $this->className = $className;
            }

            /**
             * @param object $object
             * @return $this
             */
            public function setObject(array $criteria, $object): self
            {
                $this->criteria = $criteria;
                $this->object = $object;
                $this->allObjects[] = $object;

                return $this;
            }

            public function find($id): ?object
            {
            }

            public function findAll(): array
            {
                return [];
            }

            public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
            {
                return array_reverse($this->allObjects);
            }

            public function findOneBy(array $criteria): ?object
            {
                if (array_key_exists('deletedAt', $criteria)) {
                    unset($criteria['deletedAt']);
                }

                if (isset($criteria['or'][0]['active'])) {
                    unset($criteria['or']);
                }

                if (
                    isset($criteria['slug'])
                    && ('page-with-error' === $criteria['slug'] || 'post-with-error' === $criteria['slug'])
                ) {
                    throw new Exception('Error', 404);
                }

                if ($this->criteria == $criteria) {
                    return $this->object;
                }

                return null;
            }

            public function getClassName(): string
            {
                return $this->className;
            }
        };
    }

    #[Given('a Media Loader')]
    public function aMediaLoader(): void
    {
        $this->mediaLoader = $this->container->get(MediaLoader::class);
    }

    #[Given('a Item Loader')]
    public function aItemLoader(): void
    {
        $this->itemLoader = $this->container->get(ItemLoader::class);
    }

    #[Given('a Type Loader')]
    public function aTypeLoader(): void
    {
        $this->typeLoader = $this->container->get(TypeLoader::class);
    }

    #[Given('an available image called :name')]
    public function anAvailableImageCalled(string $name): void
    {
        $media = new class () extends Media {
            /**
             * @inheritDoc
             */
            public function getResource()
            {
                $hf = fopen('php://memory', 'rw');
                fwrite($hf, 'fooBar');
                fseek($hf, 0);

                return $hf;
            }
        };

        current($this->objectRepository)->setObject(
            [
                'or' => [
                    ['id' => $name],
                    ['metadata.legacyId' => $name,]
                ]
            ],
            $media->setId($name)
                ->setName($name)
        );
    }

    #[Given('a Endpoint able to serve resource from database.')]
    public function aEndpointAbleToServeResourceFromDatabase(): void
    {
        $this->container->set(
            GetStreamFromMediaInterface::class,
            new class ($this->container->get(StreamFactoryInterface::class)) implements GetStreamFromMediaInterface {
                protected StreamFactoryInterface $streamFactory;

                public function __construct(StreamFactoryInterface $streamFactory)
                {
                    $this->streamFactory = $streamFactory;
                }

                public function __invoke(
                    BaseMedia $media,
                    ManagerInterface $manager
                ): GetStreamFromMediaInterface {
                    $hf = fopen('php://memory', 'rw+');
                    fwrite($hf, 'fooBar');
                    fseek($hf, 0);

                    $stream = $this->streamFactory->createStreamFromResource($hf);

                    $manager->updateWorkPlan([
                        StreamInterface::class => $stream,
                    ]);

                    return $this;
                }
            }
        );

        $this->mediaEndPoint = new RecipeEndPoint(
            $this->container->get(RenderMediaEndPoint::class),
            $this->container
        );
    }

    #[Given('I register a router')]
    public function iRegisterARouter(): void
    {
        $this->router = new class () implements RouterInterface {
            private array $routes = [];

            private array $params = [];

            public function registerRoute(string $route, callable $controller, array $params = []): self
            {
                $this->routes[$route] = $controller;
                $this->params[$route] = $params;

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function execute(
                ClientInterface $client,
                MessageInterface $request,
                ManagerInterface $manager
            ): MiddlewareInterface {
                if (!$request instanceof ServerRequestInterface) {
                    return $this;
                }

                $path = $request->getUri()->getPath();

                foreach ($this->routes as $route => $endpoint) {
                    $values = [];
                    if (preg_match($route, $path, $values)) {
                        $result = new Result($endpoint);
                        $request = $request->withAttribute(RouterInterface::ROUTER_RESULT_KEY, $result);

                        foreach ($values as $key => $value) {
                            if (!is_numeric($key)) {
                                $request = $request->withAttribute($key, $value);
                            }
                        }

                        foreach ($this->params[$route] as $key => $value) {
                            $request = $request->withAttribute($key, $value);
                        }

                        if ('/posts' === $path) {
                            $manager->updateWorkPlan([
                                'itemsPerPage' => 10,
                                'page' => '1',
                                'template' => 'Acme:MyBundle:list.html.twig',
                            ]);
                        }

                        $manager->updateWorkPlan([RouterResultInterface::class => $result]);

                        $manager->continueExecution($client, $request);

                        break;
                    }
                }

                return $this;
            }
        };

        $this->container->set(RouterInterface::class, $this->router);
    }

    #[Given('The router can process the request :url to controller :controllerName')]
    public function theRouterCanProcessTheRequestToController(string $url, string $controllerName): void
    {
        $controller = null;
        $params = [];
        switch ($controllerName) {
            case 'contentEndPoint':
                $controller = $this->contentEndPoint;
                break;
            case 'postEndPoint':
                $controller = $this->postEndPoint;
                break;
            case 'postsEndPoint':
                $controller = $this->postsEndPoint;
                break;
            case 'staticEndPoint':
                $params = ['template' => 'Acme:MyBundle:template.html.twig'];
                $controller = $this->staticEndPoint;
                break;
            case 'mediaEndPoint':
                $controller = $this->mediaEndPoint;
                break;
        }

        if (null !== $controller) {
            $this->router->registerRoute($url, $controller, $params);
        }
    }

    private function buildClient(): ClientInterface
    {
        $this->client = new readonly class ($this) implements ClientInterface {
            private FeatureContext $context;

            /**
             *  constructor.
             */
            public function __construct(FeatureContext $context)
            {
                $this->context = $context;
            }

            /**
             * @inheritDoc
             */
            public function updateResponse(callable $modifier): ClientInterface
            {
                $modifier($this, $this->context->response);

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function acceptResponse(EastResponse | MessageInterface $response): ClientInterface
            {
                $this->context->response = $response;

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function sendResponse(EastResponse | MessageInterface | null $response = null, bool $silently = false): ClientInterface
            {
                if (!empty($response)) {
                    $this->context->response = $response;
                }

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function errorInRequest(Throwable $throwable, bool $silently = false): ClientInterface
            {
                $this->context->error = $throwable;

                return $this;
            }

            public function mustSendAResponse(): ClientInterface
            {
                return $this;
            }

            public function sendAResponseIsOptional(): ClientInterface
            {
                return $this;
            }
        };

        return $this->client;
    }

    private function buildManager(ServerRequest $request): Manager
    {
        $manager = new Manager($this->container->get(PlanInterface::class));

        $this->response = null;
        $this->error = null;

        $this->buildClient();

        $manager->receiveRequest(
            $this->client,
            $request
        );

        return $manager;
    }

    #[When('The server will receive the request :url')]
    public function theServerWillReceiveTheRequest(string $url): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('errorTemplate', '<error>-error');
        $request = $request->withMethod('GET');
        $request = $request->withUri(new Uri($url));
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $request = $request->withQueryParams($query);

        $this->buildManager($request);
    }

    #[Then('The client must accept a response')]
    public function theClientMustAcceptAResponse(): void
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);
        Assert::assertNull($this->error);
    }

    #[Then('I should get :body')]
    public function iShouldGet(string $body): void
    {
        Assert::assertEquals($body, (string) $this->response->getBody());
    }

    #[Then('The client must accept an error')]
    public function theClientMustAcceptAnError(): void
    {
        Assert::assertNull($this->response);
        Assert::assertInstanceOf(Throwable::class, $this->error);
    }

    #[Given('a Content Loader')]
    public function aContentLoader(): void
    {
        $this->contentLoader = new ContentLoader(
            $this->container->get(ContentRepositoryInterface::class)
        );
    }

    #[Given('a type of :contentType, called :name with :blockNumber blocks :blocks and template :template with :config')]
    public function aTypeOfPageCalledWithBlocksAndTemplateWith(
        string $name,
        int $blockNumber,
        string $blocks,
        string $template,
        string $config,
        string $contentType
    ): void {
        $this->type = new Type();
        $this->type->setName($name);
        $blocksList = [];
        foreach (explode(',', $blocks) as $blockName) {
            $blocksList[] = new Block($blockName, BlockType::Text);
        }

        $this->type->setBlocks($blocksList);
        $this->type->setTemplate($template);
        $this->templateToCall = $template;
        $this->templateContent = $config;
    }

    private function getTag(string $name): Tag
    {
        return $this->tags[$name] ??= (new Tag())->setName($name)->setSlug($name);
    }

    #[Given('an available :contentType with the slug :slug of type :type')]
    #[Given('an available :contentType with the slug :slug of type :type and tag :tag')]
    public function anAvailablePageWithTheSlugOfType(
        string $contentType,
        string $slug,
        string $type,
        ?string $tag = null
    ): void {
        $className = match($contentType) {
            'post' => Post::class,
            default => Content::class,
        };

        $object = (new $className())
            ->setSlug($slug)
            ->setType($this->type)
            ->setParts(['block1' => 'hello', 'block2' => 'world'])
            ->setSanitizedParts(['block1' => 'hello', 'block2' => 'world'], 'fooBar')
            ->setPublishedAt(new DateTime('2017-11-25'));

        if ($tag !== null && $tag !== '' && $tag !== '0') {
            $object->setTags([$this->getTag($tag)]);
        }

        $this->getObjectRepository($className)
            ->setObject(
                [
                    'publishedAt' => [
                        'lte' => $this->getCurrentDate(),
                    ],
                    'slug' => $slug,
                ],
                $object
            );
    }

    #[Given('a Endpoint able to render and serve page.')]
    public function aEndpointAbleToRenderAndServePage(): void
    {
        $this->contentEndPoint = new RecipeEndPoint(
            $this->container->get(RenderDynamicContentEndPoint::class),
            $this->container
        );
    }

    #[Given('a Endpoint able to render and serve post.')]
    public function aEndpointAbleToRenderAndServePost(): void
    {
        $this->postEndPoint = new RecipeEndPoint(
            $this->container->get(RenderDynamicPostEndPoint::class),
            $this->container
        );

        error_reporting(E_ALL & ~E_USER_NOTICE);
    }

    #[Given('a Endpoint able to render and serve list of posts.')]
    public function aEndpointAbleToRenderAndServeListOfPosts(): void
    {
        $this->postsEndPoint = new RecipeEndPoint(
            $this->container->get(ListAllPostsEndPoint::class),
            $this->container
        );

        error_reporting(E_ALL & ~E_USER_NOTICE);

        $this->templateToCall = 'Acme:MyBundle:list.html.twig';
        $this->templateContent = 'list: {posts}';
    }

    public function buildResultObject(string $body): ResultInterface
    {
        return $result = new readonly class ($body) implements ResultInterface {
            private string $content;

            public function __construct(string $content)
            {
                $this->content = $content;
            }

            public function __toString(): string
            {
                return $this->content;
            }
        };
    }

    #[Given('a twig templating engine')]
    public function aTwigTemplatingEngine(): void
    {
        $this->twig = new class () extends Environment {
            public function __construct()
            {
            }

            public function render($name, array $parameters = []): string
            {
                if (empty($parameters['objectInstance'])) {
                    return 'non-object-view';
                }

                //To avoid to manage templating view for crud
                $final = [];
                $ro = new ReflectionObject($object = $parameters['objectInstance']);
                foreach ($ro->getProperties() as $rp) {
                    if (in_array($rp->getName(), [
                        'id',
                        'createdAt',
                        'updatedAt',
                        'deletedAt',
                        'states',
                        'activesStates',
                        'classesByStates',
                        'statesAliasesList',
                        'callerStatedClassesStack',
                        'localeField',
                        'publishedAt',
                        'defaultCallerStatedClassName',
                        'decodedParts',
                        'sanitizedParts',
                        'decodedSanitizedParts',
                        'sanitizedHash',
                        'loadedStatesCaches',
                        'calledMethodCache',
                        'disableCalledMethodCache'
                    ])) {
                        continue;
                    }

                    $final[$rp->getName()] = $rp->getValue($object);
                }

                return json_encode($final);
            }
        };
    }

    #[Given('a templating engine')]
    public function aTemplatingEngine(): void
    {
        $this->templating = new readonly class ($this) implements EngineInterface {
            private FeatureContext $context;

            public function __construct(FeatureContext $context)
            {
                $this->context = $context;
            }

            public function render(PromiseInterface $promise, $view, array $parameters = []): EngineInterface
            {
                if ('404-error' === $view) {
                    $promise->fail(new Exception('Error 404'));

                    return $this;
                }

                Assert::assertEquals($this->context->templateToCall, $view);

                $keys = [];
                $values = [];
                if (isset($parameters['post']) && $parameters['post'] instanceof Post) {
                    foreach ($parameters['post']->getParts()->toArray() as $key => $value) {
                        $keys[] = '{'.$key.'}';
                        $values[] = $value;
                    }
                } elseif (isset($parameters['postsCollection'])) {
                    $keys[] = '{posts}';
                    $value = '';
                    foreach ($parameters['postsCollection'] as $post) {
                        $value .= $post->getSlug() . ':';
                    }

                    $values[] = trim($value, ':');
                } elseif (isset($parameters['content']) && $parameters['content'] instanceof Content) {
                    foreach ($parameters['content']->getParts()->toArray() as $key => $value) {
                        $keys[] = '{'.$key.'}';
                        $values[] = $value;
                    }
                }

                $result = $this->context->buildResultObject(str_replace($keys, $values, $this->context->templateContent));
                $promise->success($result);

                return $this;
            }

            public function exists($name): void
            {
            }

            public function supports($name): void
            {
            }
        };

        $this->container->set(EngineInterface::class, $this->templating);
    }

    #[Given('a templating engine for sanitized :contentType')]
    public function aTemplatingEngineForSanitizedContent(string $contentType): void
    {
        $this->templating = new readonly class ($this) implements EngineInterface {
            private FeatureContext $context;

            public function __construct(FeatureContext $context)
            {
                $this->context = $context;
            }

            public function render(PromiseInterface $promise, $view, array $parameters = []): EngineInterface
            {
                if ('404-error' === $view) {
                    $promise->fail(new Exception('Error 404'));

                    return $this;
                }

                Assert::assertEquals($this->context->templateToCall, $view);

                $keys = [];
                $values = [];
                if (isset($parameters['post']) && $parameters['post'] instanceof Post) {
                    foreach ($parameters['post']->getSanitizedParts('fooBar')->toArray() as $key => $value) {
                        $keys[] = '{'.$key.'}';
                        $values[] = $value;
                    }
                } elseif (isset($parameters['content']) && $parameters['content'] instanceof Content) {
                    foreach ($parameters['content']->getSanitizedParts('fooBar')->toArray() as $key => $value) {
                        $keys[] = '{'.$key.'}';
                        $values[] = $value;
                    }
                }

                $result = $this->context->buildResultObject(str_replace($keys, $values, $this->context->templateContent));
                $promise->success($result);

                return $this;
            }

            public function exists($view): void
            {
            }

            public function supports($view): void
            {
            }
        };

        $this->container->set(EngineInterface::class, $this->templating);
    }

    #[Given('a template :template with :content')]
    public function aTemplateWith(string $template, string $content): void
    {
        $this->templateToCall = $template;
        $this->templateContent = $content;
    }

    #[Given('a Endpoint able to render and serve this template.')]
    public function aEndpointAbleToRenderAndServeThisTemplate(): void
    {
        $this->staticEndPoint = new RecipeEndPoint(
            $this->container->get(RenderStaticContentEndPoint::class),
            $this->container
        );
    }

    #[Then('An object :class must be persisted')]
    public function anObjectMustBePersisted(string $class): void
    {
        Assert::assertNotEmpty($this->createdObjects[$class]);
    }

    private function runSymfony(SFRequest $serverRequest): void
    {
        $this->symfonyKernel->boot();

        $container = $this->symfonyKernel->getContainer();

        $container->set(ObjectManager::class, $this->buildObjectManager());
        $container->set('twig', $this->twig);

        $container->set(
            EngineInterface::class,
            new Engine($this->twig)
        );

        $container->get(DatesService::class)->setCurrentDate($this->getCurrentDate());

        $response = $this->symfonyKernel->handle($serverRequest);

        $psrFactory = new PsrHttpFactory(
            new ServerRequestFactory(),
            new StreamFactory(),
            new UploadedFileFactory(),
            new ResponseFactory()
        );

        $this->response = $psrFactory->createResponse($response);

        $this->symfonyKernel->terminate($serverRequest, $response);
    }

    #[When('the client follows the redirection')]
    public function theClientfollowsTheRedirection(): void
    {
        $url = current($this->response->getHeader('location'));
        $serverRequest = SfRequest::create($url, 'GET');

        $this->runSymfony($serverRequest);
    }

    #[Then('the last object updated must be deleted')]
    public function theLastObjectUpdatedMustBeDeleted(): void
    {
        Assert::assertNotEmpty(current($this->updatedObjects)->getDeletedAt());
    }

    #[Then('An object :id must be updated')]
    public function anObjectMustBeUpdated(string $id): void
    {
        Assert::assertNotEmpty($this->updatedObjects[$id]);
    }

    #[Then('It is redirect to :url')]
    public function itIsRedirectTo($url): void
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);
        Assert::assertEquals(302, $this->response->getStatusCode());
        $location = current($this->response->getHeader('location'));

        Assert::assertGreaterThan(0, preg_match("#$url#i", $location));
    }

    #[Then('I should get in the form :body')]
    public function iShouldGetInTheForm(string $body): void
    {
        $expectedBody = json_decode($body, true);

        $actualBody = json_decode((string) $this->response->getBody(), true);

        Assert::assertEquals($expectedBody, $actualBody);
    }

    #[When('Symfony will receive the POST request :url with :body')]
    public function symfonyWillReceiveThePostRequestWith(string $url, $body): void
    {
        $expectedBody = [];
        parse_str((string) $body, $expectedBody);
        $serverRequest = SfRequest::create($url, 'POST', $expectedBody);

        $this->runSymfony($serverRequest);
    }

    #[When('Symfony will receive the DELETE request :url')]
    public function symfonyWillReceiveTheDeleteRequest(string $url): void
    {
        $serverRequest = SfRequest::create($url, 'DELETE', []);

        $this->runSymfony($serverRequest);
    }

    #[Given('a object of type :class with id :id')]
    public function aObjectOfTypeWithId(string $class, $id): void
    {
        $object = new $class();
        $object->setId($id);

        $this->getObjectRepository($class)->setObject(['id' => $id], $object);
    }

    #[Given('a object of type :class with id :id and :properties')]
    public function aObjectOfTypeWithIdAnd(string $class, $id, $properties): void
    {
        $object = new $class();
        $object->setId($id);

        $ro = new ReflectionObject($object);
        foreach (json_decode((string) $properties, true) as $name => $value) {
            if (!$ro->hasProperty($name)) {
                continue;
            }

            $rp = $ro->getProperty($name);
            $isAccessible = !($rp->isPrivate() || $rp->isProtected());
            $rp->setValue($object, $value);
        }

        $this->getObjectRepository($class)->setObject(['id' => $id], $object);
    }

    #[Then('no session must be opened')]
    public function noSessionMustBeOpened(): void
    {
        $container = $this->symfonyKernel->getContainer()->get(GetTokenStorageService::class);
        if (!$container->tokenStorage) {
            Assert::fail('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $container->tokenStorage->getToken()) {
            return;
        }

        Assert::assertEmpty($token->getUser());
    }

    #[Then('a session must be opened')]
    public function aSessionMustBeOpened(): void
    {
        $container = $this->symfonyKernel->getContainer()->get(GetTokenStorageService::class);
        if (!$container->tokenStorage) {
            Assert::fail('The SecurityBundle is not registered in your application.');
        }

        Assert::assertNotEmpty($token = $container->tokenStorage->getToken());
        Assert::assertInstanceOf(PasswordAuthenticatedUser::class, $token->getUser());
    }

    #[Given('a user with password :password')]
    public function aUserWithPassword(string $password): void
    {
        $this->symfonyKernel->boot();

        $object = new User();

        $storedPassword = new StoredPassword();
        $storedPassword->setAlgo(PasswordAuthenticatedUser::class)
            ->setHashedPassword(
                (new SodiumPasswordHasher())->hash($password)
            );

        $object->setId($id = 'userid');
        $object->setEmail('admin@teknoo.software')
            ->setFirstName('ad')
            ->setLastName('min')
            ->setAuthData([$storedPassword]);

        $this->getObjectRepository(User::class)->setObject(['email' => 'admin@teknoo.software'], $object);
    }

    #[Given('an empty locale')]
    public function anEmptyLocale(): void
    {
        $this->locale = '';
    }
}
