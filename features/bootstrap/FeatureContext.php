<?php

declare(strict_types=1);

use DI\Bridge\Symfony\Kernel as BaseKernel;
use DI\ContainerBuilder as DIContainerBuilder;
use Behat\Behat\Context\Context;
use DI\Container;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\ObjectManager;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Form\Extension\DataCollector\Proxy\ResolvedTypeDataCollectorProxy;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\ResolvedFormType;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Templating\EngineInterface;
use Teknoo\East\Foundation\Recipe\RecipeInterface;
use Teknoo\East\Foundation\Router\RouterInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Manager\Manager;
use Teknoo\East\Foundation\Router\Result;
use Teknoo\East\Foundation\Middleware\MiddlewareInterface;
use Teknoo\East\Foundation\EndPoint\EndPointInterface;
use Teknoo\East\Website\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Loader\MediaLoader;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\TypeLoader;
use Teknoo\East\Website\EndPoint\MediaEndPointTrait;
use Teknoo\East\Website\EndPoint\ContentEndPointTrait;
use Teknoo\East\Website\EndPoint\StaticEndPointTrait;
use Teknoo\East\FoundationBundle\EndPoint\EastEndPointTrait;
use Teknoo\East\Website\Object\Media;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Object\Block;

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

    private ?ObjectRepository $objectRepository = null;

    private ?MediaLoader $mediaLoader = null;

    private ?ContentLoader $contentLoader = null;

    private ?ItemLoader $itemLoader = null;

    private ?TypeLoader $typeLoader = null;

    /**
     * @var MediaEndPointTrait|EndPointInterface
     */
    private ?EndPointInterface $mediaEndPoint = null;

    /**
     * @var ContentEndPointTrait|EndPointInterface
     */
    private ?EndPointInterface $contentEndPoint = null;

    /**
     * @var StaticEndPointTrait|EndPointInterface
     */
    private ?EndPointInterface $staticEndPoint = null;

    private ?Type $type = null;

    public ?EngineInterface $templating = null;

    public ?string $templateToCall = null;

    public ?string $templateContent = null;

    public ?ResponseInterface $response = null;

    public ?\Throwable $error = null;

    public $createdObjects = [];

    public $updatedObjects = [];

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I have DI initialized
     */
    public function iHaveDiInitialized(): void
    {
        $containerDefinition = new \DI\ContainerBuilder();
        $rootDir = \dirname(__DIR__, 2);
        $containerDefinition->addDefinitions(
            include $rootDir.'/vendor/teknoo/east-foundation/src/universal/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir . '/src/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir.'/infrastructures/doctrine/di.php'
        );
        $containerDefinition->addDefinitions(
            include $rootDir.'/infrastructures/di.php'
        );

        $this->container = $containerDefinition->build();

        $this->container->set(ObjectManager::class, $this->buildObjectManager());
    }

    /**
     * @Given I have DI With Symfony initialized
     */
    public function iHaveDiWithSymfonyInitialized(): void
    {
        $this->symfonyKernel = new class($this, 'test') extends BaseKernel
        {
            use MicroKernelTrait;

            private FeatureContext $context;

            public function __construct(FeatureContext $context, $environment)
            {
                $this->context = $context;

                parent::__construct($environment, false);
            }

            public function getCacheDir()
            {
                return \dirname(__DIR__, 2).'/tests/var/cache';
            }

            public function getLogDir()
            {
                return \dirname(__DIR__, 2).'/tests/var/logs';
            }

            public function registerBundles()
            {
                yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
                //todo ?yield new \Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle();
                yield new \Teknoo\East\FoundationBundle\EastFoundationBundle();
                yield new \Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle();
            }

            protected function buildPHPDIContainer(DIContainerBuilder $builder)
            {
                $rootDir = \dirname(__DIR__, 2);
                $builder->addDefinitions(
                    include $rootDir.'/vendor/teknoo/east-foundation/src/universal/di.php'
                );
                $builder->addDefinitions(
                    include $rootDir.'/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/di.php'
                );
                $builder->addDefinitions(
                    include $rootDir.'/src/di.php'
                );
                $builder->addDefinitions(
                    include $rootDir.'/infrastructures/doctrine/di.php'
                );
                $builder->addDefinitions(
                    include $rootDir.'/infrastructures/symfony/Resources/config/di.php'
                );
                $builder->addDefinitions(
                    include $rootDir.'/infrastructures/di.php'
                );

                $this->context->container = $builder->build();
                $this->context->container->set(ObjectManager::class, $this->context->buildObjectManager());
                $this->container->set('templating.engine.twig', $this->context->templating);

                return $this->context->container;
            }

            protected function configureContainer(SfContainerBuilder $container, LoaderInterface $loader)
            {
                $loader->load(__DIR__.'/config/packages/*.yaml', 'glob');
                $loader->load(__DIR__.'/config/services.yaml');
                $container->setParameter('container.autowiring.strict_mode', true);
                $container->setParameter('container.dumper.inline_class_loader', true);
            }

            protected function configureRoutes(RouteCollectionBuilder $routes)
            {
                $rootDir = \dirname(__DIR__, 2);
                $routes->import( $rootDir.'/infrastructures/symfony/Resources/config/r*.yml', '/', 'glob');
                $routes->import( $rootDir.'/infrastructures/symfony/Resources/config/admin_*.yml', '/admin', 'glob');
            }
        };
    }

    public function buildObjectManager(): ObjectManager
    {
        return new class($this) implements ObjectManager {
            private $featureContext;

            /**
             *  constructor.
             * @param FeatureContext $featureContext
             */
            public function __construct(FeatureContext $featureContext)
            {
                $this->featureContext = $featureContext;
            }

            public function find($className, $id)
            {
            }

            /**
             * @param \Teknoo\East\Website\Object\ObjectInterface $object
             */
            public function persist($object)
            {
                if ($id = $object->getId()) {
                    $this->featureContext->updatedObjects[$id] = $object;
                } else {
                    $object->setId(\uniqid());
                    $class = \explode('\\', \get_class($object));
                    $this->featureContext->createdObjects[\array_pop($class)][] = $object;
                }
            }

            public function remove($object)
            {
            }

            public function merge($object)
            {
            }

            public function clear($objectName = null)
            {
            }

            public function detach($object)
            {
            }

            public function refresh($object)
            {
            }

            public function flush()
            {
            }

            public function getRepository($className)
            {
                return $this->featureContext->buildObjectRepository($className);
            }

            public function getClassMetadata($className)
            {
            }

            public function getMetadataFactory()
            {
            }

            public function initializeObject($obj)
            {
            }

            public function contains($object)
            {
            }
        };
    }

    public function buildObjectRepository(string $className): ObjectRepository
    {
        $this->objectRepository = new class($className) implements ObjectRepository {
            /**
             * @var string
             */
            private $className;

            /**
             * @var object
             */
            private $object;

            /**
             * @var array
             */
            private $criteria;

            /**
             *  constructor.
             * @param string $className
             */
            public function __construct(string $className)
            {
                $this->className = $className;
            }

            /**
             * @param array $criteria
             * @param object $object
             * @return $this
             */
            public function setObject(array $criteria, $object): self
            {
                $this->criteria = $criteria;
                $this->object = $object;

                return $this;
            }

            public function find($id)
            {
                // TODO: Implement find() method.
            }

            public function findAll()
            {
                // TODO: Implement findAll() method.
            }

            public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
            {
            }

            public function findOneBy(array $criteria)
            {
                if (\array_key_exists('deletedAt', $criteria)) {
                    unset($criteria['deletedAt']);
                }
                
                if (isset($criteria['slug']) && 'page-with-error' == $criteria['slug']) {
                    throw new \Exception('Error');
                }

                if ($this->criteria == $criteria) {
                    return $this->object;
                }

                return null;
            }

            public function getClassName()
            {
                return $this->className;
            }
        };

        return $this->objectRepository;
    }

    /**
     * @Given a Media Loader
     */
    public function aMediaLoader(): void
    {
        $this->mediaLoader = $this->container->get(MediaLoader::class);
    }
    
    /**
     * @Given a Item Loader
     */
    public function aItemLoader(): void
    {
        $this->itemLoader = $this->container->get(ItemLoader::class);
    }

    /**
     * @Given a Type Loader
     */
    public function aTypeLoader(): void
    {
        $this->typeLoader = $this->container->get(TypeLoader::class);
    }

    /**
     * @Given an available image called :name
     */
    public function anAvailableImageCalled(string $name): void
    {
        $media = new class extends Media {
            /**
             * @inheritDoc
             */
            public function getResource()
            {
                $hf = \fopen('php://memory', 'rw');
                fwrite($hf, 'fooBar');
                fseek($hf, 0);

                return $hf;
            }
        };

        $this->objectRepository->setObject(
            ['id' => $name],
            $media->setId($name)
                ->setName($name)
        );
    }

    /**
     * @Given a Endpoint able to serve resource from database.
     */
    public function aEndpointAbleToServeResourceFromDatabase(): void
    {
        $this->mediaEndPoint = new class(
            $this->mediaLoader,
            $this->container->get(StreamFactoryInterface::class)
        ) implements EndPointInterface {
            use EastEndPointTrait;
            use MediaEndPointTrait;

            protected function getStream(Media $media): StreamInterface
            {
                $hf = fopen('php://memory', 'rw+');
                fwrite($hf, 'fooBar');
                fseek($hf, 0);

                return $this->streamFactory->createStreamFromResource($hf);
            }
        };

        $this->mediaEndPoint->setResponseFactory($this->container->get(ResponseFactoryInterface::class));
    }

    /**
     * @Given I register a router
     */
    public function iRegisterARouter(): void
    {
        $this->router = new class implements RouterInterface {
            private $routes = [];
            private $params = [];

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
                ServerRequestInterface $request,
                ManagerInterface $manager
            ): MiddlewareInterface {
                $path = $request->getUri()->getPath();

                foreach ($this->routes as $route => $endpoint) {
                    $values = [];
                    if (\preg_match($route, $path, $values)) {
                        $result = new Result($endpoint);
                        $request = $request->withAttribute(RouterInterface::ROUTER_RESULT_KEY, $result);

                        foreach ($values as $key=>$value) {
                            if (!\is_numeric($key)) {
                                $request = $request->withAttribute($key, $value);
                            }
                        }

                        foreach ($this->params[$route] as $key=>$value) {
                            $request = $request->withAttribute($key, $value);
                        }

                        $manager->updateWorkPlan([\Teknoo\East\Foundation\Router\ResultInterface::class => $result]);

                        $manager->continueExecution($client, $request);

                        break;
                    }
                }

                return $this;
            }
        };

        $this->container->set(RouterInterface::class, $this->router);
    }

    /**
     * @Given The router can process the request :url to controller :controllerName
     */
    public function theRouterCanProcessTheRequestToController(string $url, string $controllerName): void
    {
        $controller = null;
        $params = [];
        switch ($controllerName) {
            case 'contentEndPoint':
                $controller = $this->contentEndPoint;
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
        $this->client = new class($this) implements ClientInterface {
            /**
             * @var FeatureContext
             */
            private $context;

            /**
             *  constructor.
             * @param FeatureContext $context
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
            public function acceptResponse(ResponseInterface $response): ClientInterface
            {
                $this->context->response = $response;

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function sendResponse(ResponseInterface $response = null, bool $silently = false): ClientInterface
            {
                if (!empty($response)) {
                    $this->context->response = $response;
                }

                return $this;
            }

            /**
             * @inheritDoc
             */
            public function errorInRequest(\Throwable $throwable): ClientInterface
            {
                $this->context->error = $throwable;

                return $this;
            }
        };

        return $this->client;
    }

    private function buildManager(ServerRequest $request): Manager
    {
        $manager = new Manager($this->container->get(RecipeInterface::class));

        $this->response = null;
        $this->error = null;

        $this->buildClient();

        $manager->receiveRequest(
            $this->client,
            $request
        );

        return $manager;
    }

    /**
     * @When The server will receive the request :url
     */
    public function theServerWillReceiveTheRequest(string $url): void
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withUri(new Uri($url));
        $query = [];
        \parse_str($request->getUri()->getQuery(), $query);
        $request = $request->withQueryParams($query);

        $this->buildManager($request);
    }

    /**
     * @Then The client must accept a response
     */
    public function theClientMustAcceptAResponse(): void
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);
        Assert::assertNull($this->error);
    }

    /**
     * @Then I should get :body
     */
    public function iShouldGet(string $body): void
    {
        Assert::assertEquals($body, (string) $this->response->getBody());
    }

    /**
     * @Then The client must accept an error
     */
    public function theClientMustAcceptAnError(): void
    {
        Assert::assertNull($this->response);
        Assert::assertInstanceOf(\Throwable::class, $this->error);
    }

    /**
     * @Given a Content Loader
     */
    public function aContentLoader(): void
    {
        $this->contentLoader = new ContentLoader(
            $this->container->get(ContentRepositoryInterface::class)
        );
    }

    /**
     * @Given a type of page, called :name with :blockNumber blocks :blocks and template :template with :config
     */
    public function aTypeOfPageCalledWithBlocksAndTemplateWith(
        string $name,
        int $blockNumber,
        string $blocks,
        string $template,
        string $config
    ) :void {
        $this->type = new Type();
        $this->type->setName($name);
        $blocksList = [];
        foreach (\explode(',', $blocks) as $blockName) {
            $blocksList[] = new Block($blockName, 'text');
        }
        $this->type->setBlocks($blocksList);
        $this->type->setTemplate($template);
        $this->templateToCall = $template;
        $this->templateContent = $config;
    }

    /**
     * @Given an available page with the slug :slug of type :type
     */
    public function anAvailablePageWithTheSlugOfType(string $slug, string $type): void
    {
        $this->objectRepository->setObject(
            ['slug' => $slug],
            (new Content())->setSlug($type)
                ->setType($this->type)
                ->setParts(['block1' => 'hello', 'block2' => 'world'])
                ->setPublishedAt(new \DateTime(2017-11-25))
        );
    }

    /**
     * @Given a Endpoint able to render and serve page.
     */
    public function aEndpointAbleToRenderAndServePage(): void
    {
        $this->contentEndPoint = new class($this->contentLoader, '404-error') implements EndPointInterface {
            use EastEndPointTrait;
            use ContentEndPointTrait;
        };

        $this->contentEndPoint->setResponseFactory($this->container->get(ResponseFactoryInterface::class));
        $this->contentEndPoint->setStreamFactory($this->container->get(StreamFactoryInterface::class));
    }

    private function buildFormRegistry(): void
    {
        $registry = new class implements FormRegistryInterface {
            private $types = [];

            public function getType(string $name)
            {
                if (isset($this->types[$name])) {
                    return $this->types[$name];
                }

                if (\class_exists($name)) {
                    $type = new $name;
                    $parent = $type->getParent();
                    return $this->types[$name] = new ResolvedTypeDataCollectorProxy(
                        new ResolvedFormType($type, [], new ResolvedFormType(new $parent)),
                        new \Symfony\Component\Form\Extension\DataCollector\FormDataCollector(
                            new \Symfony\Component\Form\Extension\DataCollector\FormDataExtractor()
                        )
                    );
                }

                throw new \InvalidArgumentException("No form type found for $name");
            }

            public function hasType(string $name)
            {
                return \class_exists($name) || isset($this->types[$name]);
            }

            public function getTypeGuesser()
            {
                return null;
            }

            public function getExtensions()
            {
                return [];
            }
        };

        $this->container->set(FormRegistryInterface::class, $registry);
    }

    /**
     * @Given a templating engine
     */
    public function aTemplatingEngine(): void
    {
        $this->templating = new class($this) implements EngineInterface {
            private $context;

            /**
             * @param FeatureContext $context
             */
            public function __construct(FeatureContext $context)
            {
                $this->context = $context;
            }

            public function render($name, array $parameters = array())
            {
                if ('404-error' === $name) {
                    return 'Error 404';
                }
                
                Assert::assertEquals($this->context->templateToCall, $name);

                $keys = [];
                $values = [];
                if (isset($parameters['content']) && $parameters['content'] instanceof Content) {
                    foreach ($parameters['content']->getParts() as $key=>$value) {
                        $keys[] = '{'.$key.'}';
                        $values[] = $value;
                    }
                }

                return \str_replace($keys, $values, $this->context->templateContent);
            }

            public function exists($name)
            {
            }

            public function supports($name)
            {
            }
        };

        if ($this->staticEndPoint instanceof EndPointInterface) {
            $this->staticEndPoint->setTemplating($this->templating);
        }

        if ($this->contentEndPoint instanceof EndPointInterface) {
            $this->contentEndPoint->setTemplating($this->templating);
        }
    }

    /**
     * @Given a template :template with :content
     */
    public function aTemplateWith(string $template, string $content): void
    {
        $this->templateToCall = $template;
        $this->templateContent = $content;
    }

    /**
     * @Given a Endpoint able to render and serve this template.
     */
    public function aEndpointAbleToRenderAndServeThisTemplate(): void
    {
        $this->staticEndPoint = new class implements EndPointInterface {
            use EastEndPointTrait;
            use StaticEndPointTrait;
        };

        $this->staticEndPoint->setResponseFactory($this->container->get(ResponseFactoryInterface::class));
        $this->staticEndPoint->setStreamFactory($this->container->get(StreamFactoryInterface::class));
    }

    /**
     * @Then An object :class must be persisted
     */
    public function anObjectMustBePersisted(string $class)
    {
        Assert::assertNotEmpty($this->createdObjects[$class]);
    }

    /**
     * @When the client follows the redirection
     */
    public function theClientfollowsTheRedirection()
    {
        $url = \current($this->response->getHeader('location'));
        $serverRequest = SfRequest::create($url, 'GET');


        $response = $this->symfonyKernel->handle($serverRequest);

        $psrFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory(
            new \Laminas\Diactoros\ServerRequestFactory(),
            new \Laminas\Diactoros\StreamFactory(),
            new \Laminas\Diactoros\UploadedFileFactory(),
            new \Laminas\Diactoros\ResponseFactory()
        );

        $this->response = $psrFactory->createResponse($response);

        $this->symfonyKernel->terminate($serverRequest, $response);
    }

    /**
     * @Then the last object updated must be deleted
     */
    public function theLastObjectUpdatedMustBeDeleted()
    {
        Assert::assertNotEmpty(\current($this->updatedObjects)->getIsDeleted());
    }

    /**
     * @Then An object :id must be updated
     */
    public function anObjectMustBeUpdated(string $id)
    {
        Assert::assertNotEmpty($this->updatedObjects[$id]);
    }

    /**
     * @Then It is redirect to :url
     */
    public function itIsRedirectTo($url)
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);
        Assert::assertEquals(302, $this->response->getStatusCode());
        $location = \current($this->response->getHeader('location'));

        Assert::assertGreaterThan(0, \preg_match("#$url#i", $location));
    }

    /**
     * @Then I should get in the form :body
     */
    public function iShouldGetInTheForm(string $body): void
    {
        $expectedBody = [];
        \parse_str($body, $expectedBody);

        $actualBody = [];
        \parse_str((string) $this->response->getBody(), $actualBody);

        Assert::assertEquals($expectedBody, $actualBody);
    }

    /**
     * @When Symfony will receive the POST request :url with :body
     */
    public function symfonyWillReceiveThePostRequestWith($url, $body)
    {
        $expectedBody = [];
        \parse_str($body, $expectedBody);
        $serverRequest = SfRequest::create($url, 'POST', $expectedBody);


        $response = $this->symfonyKernel->handle($serverRequest);

        $psrFactory = new \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory(
            new \Laminas\Diactoros\ServerRequestFactory(),
            new \Laminas\Diactoros\StreamFactory(),
            new \Laminas\Diactoros\UploadedFileFactory(),
            new \Laminas\Diactoros\ResponseFactory()
        );

        $this->response = $psrFactory->createResponse($response);

        $this->symfonyKernel->terminate($serverRequest, $response);
    }

    /**
     * @When Symfony will receive the DELETE request :url
     */
    public function symfonyWillReceiveTheDeleteRequest($url)
    {
        $serverRequest = SfRequest::create($url, 'DELETE', []);
    }
}
