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

namespace Teknoo\Tests\East\Website\Doctrine\Translatable\Persistence\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Id\IdGenerator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Doctrine\Translatable\Persistence\Adapter\ODM;
use Teknoo\East\Website\Doctrine\Translatable\Persistence\AdapterInterface;
use Teknoo\East\Website\Doctrine\Translatable\TranslationInterface;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\WrapperInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @covers \Teknoo\East\Website\Doctrine\Translatable\Persistence\Adapter\ODM
 */
class ODMTest extends TestCase
{
    private ?DocumentManager $manager = null;

    /**
     * @return DocumentManager|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getManager(): DocumentManager
    {
        if (!$this->manager instanceof DocumentManager) {
            $this->manager = $this->createMock(DocumentManager::class);
        }

        return $this->manager;
    }

    public function build(): ODM
    {
        return new ODM($this->getManager());
    }

    public function testLoadAllTranslations()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(
            $this->createMock(TranslationInterface::class)
        );

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $called = false;

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->loadAllTranslations(
                'fr',
                'fooId',
                'fooClass',
                'barClass',
                function () use (&$called) {
                    $called = true;
                }
            )
        );

        self::assertTrue($called);
    }

    public function testLoadAllTranslationsOnDeferred()
    {
        $this->getManager()
            ->expects(self::never())
            ->method('createQueryBuilder');

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->setDeferred(true)->loadAllTranslations(
                'fr',
                'fooId',
                'fooClass',
                'barClass',
                function () use (&$called) {
                    $called = true;
                }
            )
        );
    }

    public function testSetDeferred()
    {
        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->setDeferred(true)
        );
    }

    public function testExecuteAllDeferredLoadingsOnNonDeferred()
    {
        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->setDeferred(true)->executeAllDeferredLoadings()
        );
    }

    public function testExecuteAllDeferredLoadingsOnDeferred()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(
            [
                ['foreign_key' => 'fooId'],
                ['foreign_key' => 'fooId'],
                ['foreign_key' => 'fooId'],
                ['foreign_key' => 'barId'],
                ['foreign_key' => 'barId'],
                ['foreign_key' => 'barId'],
            ]
        );

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $called = 0;

        $odm = $this->build();

        $odm->setDeferred(true);

        self::assertInstanceOf(
            AdapterInterface::class,
            $odm->loadAllTranslations(
                locale: 'fr',
                identifier: 'fooId',
                translationClass: 'fooClass',
                objectClass: 'barClass',
                callback: function () use (&$called) {
                    $called++;
                }
            )
        );


        self::assertInstanceOf(
            AdapterInterface::class,
            $odm->loadAllTranslations(
                locale: 'fr',
                identifier: 'barId',
                translationClass: 'fooClass',
                objectClass: 'barClass',
                callback: function () use (&$called) {
                    $called++;
                }
            )
        );

        self::assertEquals(0, $called);

        self::assertInstanceOf(
            AdapterInterface::class,
            $odm->executeAllDeferredLoadings(),
        );

        self::assertEquals(2, $called);
    }

    public function testFindTranslationNotFound()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('getSingleResult')->willReturn(
            null
        );

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->findTranslation(
                'fr',
                'fooField',
                'fooId',
                'fooClass',
                'barClass',
                function () use (&$called) {
                    self::fail();
                }
            )
        );
    }

    public function testFindTranslationFound()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('getSingleResult')->willReturn(
            $this->createMock(TranslationInterface::class)
        );

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        $called = false;

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->findTranslation(
                'fr',
                'foo',
                'fooId',
                'fooClass',
                'barClass',
                function () use (&$called) {
                    $called = true;
                }
            )
        );

        self::assertTrue($called);
    }

    public function testRemoveAssociatedTranslations()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(true);

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->removeAssociatedTranslations('fooId', 'fooClass', 'barClass')
        );
    }

    public function testRemoveOrphansTranslations()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('notIn')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(true);

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->removeOrphansTranslations(
                'fooId',
                [
                    'barId',
                    '5a4c12e03b8a7e000b55c7a2',
                ],
                'fooClass',
                'barClass',
            )
        );
    }

    public function testRemoveOrphansTranslationsWithoutId()
    {
        $qBuilder = $this->createMock(Builder::class);
        $qBuilder->expects(self::any())
            ->method('field')
            ->willReturnSelf();

        $qBuilder->expects(self::any())
            ->method('equals')
            ->willReturnSelf();

        $qBuilder->expects(self::never())
            ->method('notIn')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(true);

        $qBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->getManager()
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qBuilder);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->removeOrphansTranslations(
                'fooId',
                [],
                'fooClass',
                'barClass',
            )
        );
    }

    public function testPersistTranslationRecordOnInsertNoneIdGeneration()
    {
        $translation = $this->createMock(TranslationInterface::class);
        $translation->expects(self::any())->method('getIdentifier')->willReturn('');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldNames')->willReturn(['foo']);
        $meta->expects(self::any())->method('getFieldMapping')->willReturn(['fieldName' => 'foo']);
        $meta->expects(self::any())->method('getFieldValue')->willReturn('bar');
        $meta->generatorType = ClassMetadata::GENERATOR_TYPE_NONE;

        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())->method('insertOne');
        $collection->expects(self::never())->method('updateOne');

        $this->getManager()->expects(self::any())->method('getClassMetadata')->willReturn($meta);
        $this->getManager()->expects(self::any())->method('getDocumentCollection')->willReturn($collection);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->persistTranslationRecord($translation)
        );
    }

    public function testPersistTranslationRecordOnInsertWithoutIdGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $translation = $this->createMock(TranslationInterface::class);
        $translation->expects(self::any())->method('getIdentifier')->willReturn('');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldNames')->willReturn(['foo']);
        $meta->expects(self::any())->method('getFieldMapping')->willReturn(['fieldName' => 'foo']);
        $meta->expects(self::any())->method('getFieldValue')->willReturn('bar');
        $meta->generatorType = ClassMetadata::GENERATOR_TYPE_UUID;

        $collection = $this->createMock(Collection::class);
        $collection->expects(self::never())->method('insertOne');
        $collection->expects(self::never())->method('updateOne');

        $this->getManager()->expects(self::any())->method('getClassMetadata')->willReturn($meta);
        $this->getManager()->expects(self::any())->method('getDocumentCollection')->willReturn($collection);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->persistTranslationRecord($translation)
        );
    }

    public function testPersistTranslationRecordOnInsertWithIdGenerator()
    {
        $translation = $this->createMock(TranslationInterface::class);
        $translation->expects(self::any())->method('getIdentifier')->willReturn('');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldNames')->willReturn(['foo']);
        $meta->expects(self::any())->method('getFieldMapping')->willReturn(['fieldName' => 'foo']);
        $meta->expects(self::any())->method('getFieldValue')->willReturn('bar');
        $meta->generatorType = ClassMetadata::GENERATOR_TYPE_UUID;
        $meta->idGenerator = $this->createMock(IdGenerator::class);

        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())->method('insertOne');
        $collection->expects(self::never())->method('updateOne');

        $this->getManager()->expects(self::any())->method('getClassMetadata')->willReturn($meta);
        $this->getManager()->expects(self::any())->method('getDocumentCollection')->willReturn($collection);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->persistTranslationRecord($translation)
        );
    }

    public function testPersistTranslationRecordOnUpdateWithUUID()
    {
        $translation = $this->createMock(TranslationInterface::class);
        $translation->expects(self::any())->method('getIdentifier')->willReturn('foo');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldNames')->willReturn(['id', 'foo']);
        $meta->expects(self::any())->method('getFieldMapping')->willReturnOnConsecutiveCalls(
            ['id' => true, 'fieldName' => '_id'],
            ['fieldName' => 'foo']
        );
        $meta->expects(self::any())->method('getFieldValue')->willReturn('bar');

        $collection = $this->createMock(Collection::class);
        $collection->expects(self::never())->method('insertOne');
        $collection->expects(self::once())->method('updateOne');

        $this->getManager()->expects(self::any())->method('getClassMetadata')->willReturn($meta);
        $this->getManager()->expects(self::any())->method('getDocumentCollection')->willReturn($collection);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->persistTranslationRecord($translation)
        );
    }

    public function testPersistTranslationRecordOnUpdateWithObjectId()
    {
        $translation = $this->createMock(TranslationInterface::class);
        $translation->expects(self::any())->method('getIdentifier')->willReturn('5a3d3e2ef7f98a00110ab582');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldNames')->willReturn(['id', 'foo']);
        $meta->expects(self::any())->method('getFieldMapping')->willReturnOnConsecutiveCalls(
            ['id' => true, 'fieldName' => '_id'],
            ['fieldName' => 'foo']
        );
        $meta->expects(self::any())->method('getFieldValue')->willReturn('bar');

        $collection = $this->createMock(Collection::class);
        $collection->expects(self::never())->method('insertOne');
        $collection->expects(self::once())->method('updateOne');

        $this->getManager()->expects(self::any())->method('getClassMetadata')->willReturn($meta);
        $this->getManager()->expects(self::any())->method('getDocumentCollection')->willReturn($collection);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->persistTranslationRecord($translation)
        );
    }

    public function testUpdateTranslationRecordWithGenericClassMetaData()
    {
        $this->expectException(\RuntimeException::class);

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects(self::never())->method('setPropertyValue');

        $meta = $this->createMock(BaseClassMetadata::class);

        $translation = $this->createMock(TranslationInterface::class);

        $this->build()->updateTranslationRecord($wrapper, $meta, 'foo', $translation);
    }

    public function testUpdateTranslationRecord()
    {
        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects(self::once())->method('updateTranslationRecord');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldMapping')->willReturn([
            'type' => Type::STRING
        ]);

        $translation = $this->createMock(TranslationInterface::class);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->updateTranslationRecord($wrapper, $meta, 'foo', $translation)
        );
    }

    public function testSetTranslatedValueWithGenericClassMetaData()
    {
        $this->expectException(\RuntimeException::class);

        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects(self::never())->method('setPropertyValue');

        $meta = $this->createMock(BaseClassMetadata::class);

        $this->build()->setTranslatedValue($wrapper, $meta, 'foo', 'bar');
    }

    public function testSetTranslatedValue()
    {
        $wrapper = $this->createMock(WrapperInterface::class);
        $wrapper->expects(self::once())->method('setPropertyValue');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(self::any())->method('getFieldMapping')->willReturn([
            'type' => Type::STRING
        ]);

        self::assertInstanceOf(
            AdapterInterface::class,
            $this->build()->setTranslatedValue($wrapper, $meta, 'foo', 'bar')
        );
    }
}
