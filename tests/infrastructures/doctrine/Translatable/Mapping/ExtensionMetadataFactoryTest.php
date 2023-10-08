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
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Doctrine\Translatable\Mapping;

use Doctrine\Common\Cache\Cache;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Teknoo\East\Website\Doctrine\Exception\InvalidMappingException;
use Teknoo\East\Website\Doctrine\Object\Content as DoctrineContent;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\Configuration;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\DriverInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\DriverFactoryInterface;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Website\Doctrine\Translatable\TranslatableListener;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @covers \Teknoo\East\Website\Doctrine\Translatable\Mapping\ExtensionMetadataFactory
 */
class ExtensionMetadataFactoryTest extends TestCase
{
    private ?ObjectManager $objectManager = null;

    private ?AbstractClassMetadataFactory $classMetadataFactory = null;

    private ?MappingDriver $mappingDriver = null;

    private ?DriverFactoryInterface $driverFactory = null;

    private ?CacheItemPoolInterface $cache = null;

    /**
     * @return ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getObjectManager(): ObjectManager
    {
        if (!$this->objectManager instanceof ObjectManager) {
            $this->objectManager = $this->createMock(ObjectManager::class);
        }

        return $this->objectManager;
    }

    /**
     * @return AbstractClassMetadataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getClassMetadataFactory(): AbstractClassMetadataFactory
    {
        if (!$this->classMetadataFactory instanceof AbstractClassMetadataFactory) {
            $this->classMetadataFactory = $this->createMock(AbstractClassMetadataFactory::class);
        }

        return $this->classMetadataFactory;
    }

    /**
     * @return MappingDriver|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMappingDriver(): MappingDriver
    {
        if (!$this->mappingDriver instanceof MappingDriver) {
            $this->mappingDriver = $this->createMock(MappingDriver::class);
        }

        return $this->mappingDriver;
    }

    /**
     * @return MappingDriver|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getCacheMock(): CacheItemPoolInterface&MockObject
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            $this->cache = $this->createMock(CacheItemPoolInterface::class);
        }

        return $this->cache;
    }

    /**
     * @return DriverFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getDriverFactory(?string $useObjectClass = null): DriverFactoryInterface
    {
        if (!$this->driverFactory instanceof DriverFactoryInterface) {
            $this->driverFactory = $this->createMock(DriverFactoryInterface::class);

            $this->driverFactory->expects(self::any())
                ->method('__invoke')
                ->willReturnCallback(function () use ($useObjectClass) {
                    $driver = $this->createMock(DriverInterface::class);
                    $driver->expects(self::any())
                        ->method('readExtendedMetadata')
                        ->willReturnCallback(
                            function (ClassMetadata $meta, array &$config) use ($driver, $useObjectClass) {
                                if (!empty($useObjectClass)) {
                                    $config['useObjectClass'] = $useObjectClass;
                                }

                                $config['fields'] = ['foo', 'bar'];
                                $config['fallbacks'] = ['foo', 'bar'];

                                return $driver;
                            }
                        );

                    return $driver;
                });
        }

        return $this->driverFactory;
    }

    public function build(?string $useObjectClass = null):ExtensionMetadataFactory
    {
        return new ExtensionMetadataFactory(
            $this->getObjectManager(),
            $this->getClassMetadataFactory(),
            $this->getMappingDriver(),
            $this->getDriverFactory($useObjectClass),
            $this->getCacheMock(),
        );
    }

    public function testLoadExtensionMetadataSuperClass()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = true;

            public function getName() {}
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::never())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataMissingDriver()
    {
        $this->expectException(InvalidMappingException::class);

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return Content::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::never())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithFileDriver()
    {

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return Content::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects(self::any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::once())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithFileDriverWithUseClassAlreadySet()
    {

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return Content::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects(self::any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::once())
            ->method('injectConfiguration')
            ->willReturnCallback(
                function ($metadata, $config) use ($listener) {
                    self::assertEquals(
                        'foo',
                        $config['useObjectClass'],
                    );
                    return $listener;
                }
            );

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build('foo')->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithMappingDriverChain()
    {

        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return Content::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(MappingDriverChain::class);
        $fileDriver = $this->createMock(FileDriver::class);

        $this->mappingDriver->expects(self::any())->method('getDrivers')->willReturn([
            $this->createMock(MappingDriver::class),
            $this->createMock(MappingDriver::class),
            $fileDriver
        ]);

        $locator = $this->createMock(FileLocator::class);
        $fileDriver->expects(self::any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::once())->method('injectConfiguration');

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWithFileDriverWithParent()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return DoctrineContent::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects(self::any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::once())->method('injectConfiguration');

        $this->getClassMetadataFactory()
            ->expects(self::any())
            ->method('hasMetadataFor')
            ->willReturn(true);

        $this->getObjectManager()
            ->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($this->createMock(ClassMetadata::class));

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWitchCacheEmpty()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return DoctrineContent::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects(self::any())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::once())->method('injectConfiguration');

        $this->getCacheMock()->expects(self::any())->method('hasItem')->willReturn(false);

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testLoadExtensionMetadataWitchCacheNotEmpty()
    {
        $meta = new class implements ClassMetadata
        {
            public $isMappedSuperclass = false;

            public function getName() {
                return DoctrineContent::class;
            }
            public function getIdentifier() {}
            public function getReflectionClass() {}
            public function isIdentifier(string $fieldName) {}
            public function hasField(string $fieldName) {}
            public function hasAssociation(string $fieldName) {}
            public function isSingleValuedAssociation(string $fieldName) {}
            public function isCollectionValuedAssociation(string $fieldName) {}
            public function getFieldNames() {}
            public function getIdentifierFieldNames() {}
            public function getAssociationNames() {}
            public function getTypeOfField(string $fieldName) {}
            public function getAssociationTargetClass(string $assocName) {}
            public function isAssociationInverseSide(string $assocName) {}
            public function getAssociationMappedByTargetField(string $assocName) {}
            public function getIdentifierValues(object $object) {}
        };

        $this->mappingDriver = $this->createMock(FileDriver::class);

        $locator = $this->createMock(FileLocator::class);
        $this->mappingDriver->expects(self::never())->method('getLocator')->willReturn($locator);

        $listener = $this->createMock(TranslatableListener::class);
        $listener->expects(self::once())->method('injectConfiguration');

        $this->getCacheMock()->expects(self::any())->method('hasItem')->willReturn(true);
        $this->getCacheMock()->expects(self::any())->method('getItem')->willReturn(
            new Configuration('foo', [])
        );

        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->loadExtensionMetadata($meta, $listener)
        );
    }

    public function testSetCache()
    {
        self::assertInstanceOf(
            ExtensionMetadataFactory::class,
            $this->build()->setCache(
                $this->createMock(CacheItemPoolInterface::class),
            ),
        );
    }
}
