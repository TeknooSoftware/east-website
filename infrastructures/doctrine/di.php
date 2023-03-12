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

namespace Teknoo\East\Website\Doctrine;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as OdmClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use ProxyManager\Proxy\GhostObjectInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SimpleXMLElement;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ItemRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\MediaRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\TypeRepositoryInterface;
use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Contracts\Object\TranslatableInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\GetStreamFromMediaInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadTranslationsInterface;
use Teknoo\East\Website\Doctrine\DBSource\Common\ContentRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\ItemRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\MediaRepository;
use Teknoo\East\Website\Doctrine\DBSource\Common\TypeRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\ContentRepository as OdmContentRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\ItemRepository as OdmItemRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\MediaRepository as OdmMediaRepository;
use Teknoo\East\Website\Doctrine\DBSource\ODM\TypeRepository as OdmTypeRepository;
use Teknoo\East\Website\Doctrine\Exception\NotSupportedException;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Website\Doctrine\Object\Media;
use Teknoo\East\Website\Doctrine\Recipe\Step\LoadTranslations;
use Teknoo\East\Website\Doctrine\Recipe\Step\ODM\GetStreamFromMedia;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\Driver\SimpleXmlFactoryInterface;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\Driver\Xml;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\DriverFactoryInterface;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Website\Doctrine\Translatable\ObjectManager\Adapter\ODM as ODMAdapter;
use Teknoo\East\Website\Doctrine\Translatable\Persistence\Adapter\ODM as ODMPersistence;
use Teknoo\East\Website\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Website\Doctrine\Translatable\TranslationManager;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\DocumentWrapper;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\FactoryInterface as WrapperFactory;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Website\Doctrine\Writer\ODM\MediaWriter;
use Teknoo\East\Website\Middleware\LocaleMiddleware;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Writer\MediaWriter as OriginalWriter;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

use function DI\create;
use function DI\decorate;
use function DI\get;

return [

    ODMPersistence::class => static function (ContainerInterface $container): ODMPersistence {
        $objectManager = $container->get(ObjectManager::class);

        if (!$objectManager instanceof DocumentManager) {
            throw new NotSupportedException('Sorry currently, this listener supports only ODM');
        }

        $deferred = false;
        if ($container->has('teknoo.east.website.translatable.deferred_loading')) {
            $deferred = !empty($container->get('teknoo.east.website.translatable.deferred_loading'));
        }

        return new ODMPersistence(
            manager: $objectManager,
            deferred: $deferred,
        );
    },

    TranslationManager::class => static function (ContainerInterface $container): ?TranslationManager {
        $objectManager = $container->get(ObjectManager::class);

        if (!$objectManager instanceof DocumentManager) {
            return null;
        }

        return new TranslationManager(
            $container->get(ODMPersistence::class),
        );
    },

    TranslationManagerInterface::class => get(TranslationManager::class),

    LoadTranslations::class => create()
        ->constructor(get(TranslationManagerInterface::class)),

    LoadTranslationsInterface::class => get(LoadTranslations::class),

    TranslatableListener::class => static function (ContainerInterface $container): TranslatableListener {
        $objectManager = $container->get(ObjectManager::class);
        $eastManager = $container->get(ManagerInterface::class);
        $persistence = $container->get(ODMPersistence::class);

        $eventManager = $objectManager->getEventManager();

        $translatableManagerAdapter = new ODMAdapter(
            $eastManager,
            $objectManager
        );

        $mappingDriver = $objectManager->getConfiguration()->getMetadataDriverImpl();
        if (null === $mappingDriver) {
            throw new NotSupportedException('The Mapping Driver is not available from the Doctrine manager');
        }

        $extensionMetadataFactory = new ExtensionMetadataFactory(
            $objectManager,
            $objectManager->getMetadataFactory(),
            $mappingDriver,
            new class implements DriverFactoryInterface {
                public function __invoke(FileLocator $locator): DriverInterface
                {
                    return new Xml(
                        $locator,
                        new class implements SimpleXmlFactoryInterface {
                            public function __invoke(string $file): SimpleXMLElement
                            {
                                return new SimpleXMLElement($file, 0, true);
                            }
                        }
                    );
                }
            },
            $container->get(ArrayAdapter::class),
        );

        $translatableListener = new TranslatableListener(
            $extensionMetadataFactory,
            $translatableManagerAdapter,
            $persistence,
            new class implements WrapperFactory {
                /**
                 * @param ClassMetadata<IdentifiedObjectInterface> $metadata
                 */
                public function __invoke(TranslatableInterface $object, ClassMetadata $metadata): WrapperInterface
                {
                    if (!$metadata instanceof OdmClassMetadata) {
                        throw new NotSupportedException('Error wrapper support only ' . OdmClassMetadata::class);
                    }

                    return new DocumentWrapper($object, $metadata);
                }
            }
        );

        $eventManager->addEventSubscriber($translatableListener);

        return $translatableListener;
    },

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

    MediaRepositoryInterface::class => static function (ContainerInterface $container): MediaRepositoryInterface {
        $repository = $container->get(ObjectManager::class)->getRepository(Media::class);
        if ($repository instanceof DocumentRepository) {
            return new OdmMediaRepository($repository);
        }

        if ($repository instanceof ObjectRepository) {
            return new MediaRepository($repository);
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

    LocaleMiddleware::class => static function (ContainerInterface $container): LocaleMiddleware {
        if (
            $container->has(ObjectManager::class)
            && ($container->get(ObjectManager::class)) instanceof DocumentManager
        ) {
            $listener = $container->get(TranslatableListener::class);
            $callback = $listener->setLocale(...);
        } else {
            //do nothing
            $callback = null;
        }

        return new LocaleMiddleware($callback);
    },

    MediaWriter::class => static function (ContainerInterface $container): MediaWriter {
        $repository = $container->get(ObjectManager::class)->getRepository(Media::class);
        if ($repository instanceof GridFSRepository) {
            return new MediaWriter($repository, $container->get(OriginalWriter::class));
        }

        throw new NotSupportedException(sprintf(
            "Error, repository of class %s are not currently managed",
            $repository::class
        ));
    },
    'teknoo.east.website.doctrine.writer.media.new' => get(MediaWriter::class),

    ProxyDetectorInterface::class => static function (): ProxyDetectorInterface {
        return new class implements ProxyDetectorInterface {
            public function checkIfInstanceBehindProxy(
                object $object,
                PromiseInterface $promise
            ): ProxyDetectorInterface {
                if (!$object instanceof GhostObjectInterface) {
                    $promise->fail(new Exception('Object is not behind a proxy'));

                    return $this;
                }

                if ($object->isProxyInitialized()) {
                    $promise->fail(new Exception('Proxy is already initialized'));

                    return $this;
                }

                $promise->success($object);

                return $this;
            }
        };
    },

    GetStreamFromMediaInterface::class => get(GetStreamFromMedia::class),
    GetStreamFromMedia::class => static function (ContainerInterface $container): GetStreamFromMedia {
        $repository = $container->get(ObjectManager::class)->getRepository(Media::class);
        if (!$repository instanceof GridFSRepository) {
            throw new NotSupportedException('Repository for Media class is not a GridFSRepository');
        }

        return new GetStreamFromMedia(
            $repository,
            $container->get(StreamFactoryInterface::class)
        );
    },

    // @codeCoverageIgnoreStart
    OriginalRecipeInterface::class . ':CRUD' => decorate(
        static function ($previous, ContainerInterface $container): OriginalRecipeInterface {
            if ($previous instanceof OriginalRecipeInterface) {
                $previous = $previous->cook(
                    action: $container->get(LoadTranslationsInterface::class),
                    name: LoadTranslationsInterface::class,
                    position: 0,
                );
            }

            return $previous;
        }
    ),

    OriginalRecipeInterface::class . ':Static' => decorate(
        static function ($previous, ContainerInterface $container): OriginalRecipeInterface {
            if ($previous instanceof OriginalRecipeInterface) {
                $previous = $previous->cook(
                    action: $container->get(LoadTranslationsInterface::class),
                    name: LoadTranslationsInterface::class,
                    position: 0,
                );
            }

            return $previous;
        }
    ),
    // @codeCoverageIgnoreEnd
];
