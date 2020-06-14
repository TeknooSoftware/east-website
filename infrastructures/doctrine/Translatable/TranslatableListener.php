<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Doctrine\Translatable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use ProxyManager\Proxy\GhostObjectInterface;
use Teknoo\East\Website\Doctrine\Exception\RuntimeException;
use Teknoo\East\Website\Doctrine\Translatable\ObjectManager\AdapterInterface as ManagerAdapterInterface;
use Teknoo\East\Website\Doctrine\Translatable\Persistence\AdapterInterface as PersistenceAdapterInterface;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\ExtensionMetadataFactory;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\FactoryInterface;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Website\Object\TranslatableInterface;

/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the TranslatableInterface interface.
 *
 * This behavior can impact the performance of your application
 * since it does an additional query for each field to translate.
 *
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all entity annotations since
 * the caching is activated for metadata
 */
class TranslatableListener implements EventSubscriber
{
    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata through the extension drivers
     */
    private ExtensionMetadataFactory $extensionMetadataFactory;

    private ManagerAdapterInterface $manager;

    private PersistenceAdapterInterface $persistence;

    private FactoryInterface $wrapperFactory;

    /**
     * Locale which is set on this listener.
     * If Entity being translated has locale defined it
     * will override this one
     */
    private string $locale;

    /**
     * Default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default. This
     * will load the default translation in other locales
     * if record is not translated yet
     */
    private string $defaultLocale;

    /**
     * If this is set to false, when if entity does
     * not have a translation for requested locale
     * it will show a blank value
     */
    private bool $translationFallback;

    /**
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     */
    private array $pendingTranslationInserts = [];

    /**
     * Tracks locale the objects currently translated in
     */
    private array $translatedInLocale = [];

    /**
     * Tracks objects to reload after flush
     * @var array<int, array<WrapperInterface, string, array<string, string, ClassMetadata>>
     */
    private array $objetsToTranslate = [];

    /**
     * Static List of cached object configurations
     * leaving it static for reasons to look into
     * other listener configuration
     */
    private array $configurations = array();

    public function __construct(
        ExtensionMetadataFactory $extensionMetadataFactory,
        ManagerAdapterInterface $manager,
        PersistenceAdapterInterface $persistence,
        FactoryInterface $wrapperFactory,
        string $locale = 'en',
        string $defaultLocale = 'en',
        bool $translationFallback = true
    ) {
        $this->extensionMetadataFactory = $extensionMetadataFactory;
        $this->manager = $manager;
        $this->persistence = $persistence;
        $this->wrapperFactory = $wrapperFactory;
        $this->locale = $locale;
        $this->defaultLocale = $defaultLocale;
        $this->translationFallback = $translationFallback;
    }

    public function getSubscribedEvents(): array
    {
        return [
            'loadClassMetadata',
            'postLoad',
            'onFlush',
            'postPersist',
            'postFlush',
        ];
    }

    public function setLocale(string $locale): TranslatableListener
    {
        $this->locale = $locale;
        return $this;
    }

    private function getObjectClassName(TranslatableInterface $object): string
    {
        if ($object instanceof GhostObjectInterface) {
            return (string) \get_parent_class($object);
        }

        return \get_class($object);
    }

    private function wrap(TranslatableInterface $translatable): WrapperInterface
    {
        return ($this->wrapperFactory)($translatable, $this->manager->getRootObject());
    }

    private function loadMetadataForObjectClass(ClassMetadata $metadata): array
    {
        return $this->extensionMetadataFactory->getExtensionMetadata($this->manager->getRootObject(), $metadata);
    }

    private function getConfiguration(ClassMetadata $meta): array
    {
        $className = $meta->getName();
        if (isset($this->configurations[$className])) {
            return $this->configurations[$className];
        }

        $this->configurations[$className] = $this->loadMetadataForObjectClass($meta);

        return $this->configurations[$className];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $classMetaData = $event->getClassMetadata();

        $this->configurations[$classMetaData->getName()] =  $this->loadMetadataForObjectClass($classMetaData);
    }

    /*
     * Gets the locale to use for translation. Loads object
     * defined locale first..
     */
    private function getTranslatableLocale(
        TranslatableInterface $object
    ): string {
        return $object->getLocaleField() ?? $this->locale;
    }

    private function loadTranslations(
        WrapperInterface $wrapper,
        string $locale,
        string $translationClass,
        array $config,
        ClassMetadata $metaData
    ): void {
        $result = $this->persistence->loadTranslations(
            $wrapper,
            $locale,
            $translationClass,
            $config['useObjectClass']
        );

        $reflectionClass = $metaData->getReflectionClass();

        // translate object's translatable properties
        foreach ($config['fields'] as $field) {
            $translated = '';
            $isTranslated = false;
            foreach ($result as $entry) {
                if ($entry['field'] === $field) {
                    $translated = $entry['content'] ?? null;
                    $isTranslated = true;
                    break;
                }
            }

            // update translation
            if (
                $isTranslated
                || (!$this->translationFallback && empty($config['fallback'][$field]))
            ) {
                $orignalValue = $wrapper->getPropertyValue($field);
                $this->persistence->setTranslationValue($wrapper, $metaData, $field, $translated);
                // ensure clean changeset
                $this->manager->setOriginalObjectProperty(
                    \spl_object_hash($wrapper->getObject()),
                    $field,
                    $orignalValue
                );
            }
        }
    }

    /*
     * After object is loaded, listener updates the translations by currently used locale
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if (!$object instanceof TranslatableInterface) {
            return;
        }

        $metaData = $this->manager->getClassMetadata($this->getObjectClassName($object));

        $config = $this->getConfiguration($metaData);
        if (!isset($config['fields'])) {
            return;
        }

        $locale = $this->getTranslatableLocale($object);
        $oid = \spl_object_hash($object);
        $this->translatedInLocale[$oid] = $locale;

        if ($locale === $this->defaultLocale) {
            return;
        }

        // fetch translations
        $translationClass = $config['translationClass'];
        $wrapper = $this->wrap($object);

        $this->loadTranslations($wrapper, $locale, $translationClass, $config, $metaData);
    }

    /*
     * Creates and update the translation for object being flushed
     */
    private function handleTranslatableObjectChanges(
        TranslatableInterface $object,
        bool $isInsert
    ): void {
        $wrapper = $this->wrap($object);
        $metaData = $this->manager->getClassMetadata($this->getObjectClassName($object));
        $config = $this->getConfiguration($metaData);

        $translationClass = $config['translationClass'];
        $translationMetadata = $this->manager->getClassMetadata($translationClass);
        $translationReflection = $translationMetadata->getReflectionClass();

        // check for the availability of the primary key
        $objectId = $wrapper->getIdentifier();
        $oid = \spl_object_hash($object);

        // load the currently used locale
        $locale = $this->getTranslatableLocale($object);

        if ($locale === $this->defaultLocale) {
            return;
        }

        $this->objetsToTranslate[$locale][] = [$wrapper, $translationClass, $config, $metaData];

        $changeSet = $this->manager->getObjectChangeSet($object);

        $translatableFields = \array_flip($config['fields']);
        foreach ($translatableFields as $field=>$notUsed) {
            if (
                isset($this->translatedInLocale[$oid])
                && $locale === $this->translatedInLocale[$oid]
                && !isset($changeSet[$field])
            ) {
                continue; // locale is same and nothing changed
            }

            $translation = null;
            if (!$isInsert) {
                $translation = $this->persistence->findTranslation(
                    $wrapper,
                    $locale,
                    $field,
                    $translationClass,
                    $config['useObjectClass']
                );
            }

            // create new translation if translation not already created and locale is different from default
            // locale, otherwise, we have the date in the original record
            if (!$translation instanceof TranslationInterface && $locale !== $this->defaultLocale) {
                $translation = $translationReflection->newInstance();
                $translation->setLocale($locale);
                $translation->setField($field);
                $translation->setObjectClass($config['useObjectClass']);
                $translation->setForeignKey($objectId);
            }

            if ($translation instanceof TranslationInterface) {
                // set the translated field, take value using reflection
                $content = $this->persistence->getTranslationValue($wrapper, $metaData, $field);
                $translation->setContent($content);

                if ($isInsert) {
                    // if we do not have the primary key yet available
                    // keep this translation in memory to insert it later with foreign key
                    $this->pendingTranslationInserts[$oid][] = $translation;
                } else {
                    $this->persistence->insertTranslationRecord($translation);
                }
            }
        }

        $this->translatedInLocale[$oid] = $locale;

        // check if we have default translation and need to reset the translation
        if (!$isInsert) {
            $modifiedChangeSet = $changeSet;
            foreach ($changeSet as $field => $changes) {
                $this->manager->setOriginalObjectProperty($oid, $field, $changes[0]);
                if (isset($translatableFields[$field]) && $locale !== $this->defaultLocale) {
                    $wrapper->setPropertyValue($field, $changes[0]);
                }
            }

            $this->manager->recomputeSingleObjectChangeset($metaData, $object);
        }
    }

    /*
     * Looks for translatable objects being inserted or updated for further processing
     */
    public function onFlush(EventArgs $event): void
    {
        $this->objetsToTranslate = [];

        $handling = function ($object, $isInsert) {
            if (!$object instanceof TranslatableInterface) {
                return;
            }

            $metaData = $this->manager->getClassMetadata($this->getObjectClassName($object));
            $config = $this->getConfiguration($metaData);

            if (isset($config['fields'])) {
                $this->handleTranslatableObjectChanges($object, $isInsert);
            }
        };

        // check all scheduled inserts for TranslatableInterface objects
        foreach ($this->manager->getScheduledObjectInsertions() as $object) {
            $handling($object, true);
        }

        // check all scheduled updates for TranslatableInterface entities
        foreach ($this->manager->getScheduledObjectUpdates() as $object) {
            $handling($object, false);
        }

        // check scheduled deletions for TranslatableInterface entities
        foreach ($this->manager->getScheduledObjectDeletions() as $object) {
            if (!$object instanceof TranslatableInterface) {
                return;
            }

            $metaData = $this->manager->getClassMetadata($this->getObjectClassName($object));
            $config = $this->getConfiguration($metaData);

            if (isset($config['fields'])) {
                $wrapper = $this->wrap($object);
                $this->persistence->removeAssociatedTranslations(
                    $wrapper,
                    $config['translationClass'],
                    $config['useObjectClass']
                );
            }
        }
    }

    public function postFlush(EventArgs $event): void
    {
        foreach ($this->objetsToTranslate as $local => &$objects) {
            foreach ($objects as &$object) {
                $this->loadTranslations($object[0], $local, $object[1], $object[2], $object[3]);
            }
        }

        $this->objetsToTranslate = [];
    }

    /*
     * Checks for inserted object to update their translation foreign keys
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if (!$object instanceof TranslatableInterface) {
            return;
        }

        $oid = \spl_object_hash($object);

        if (!isset($this->pendingTranslationInserts[$oid])) {
            return;
        }

        $wrapper = $this->wrap($object);
        // load the pending translations without key
        $objectId = $wrapper->getIdentifier();
        foreach ($this->pendingTranslationInserts[$oid] as $translation) {
            $translation->setForeignKey($objectId);
            $this->persistence->insertTranslationRecord($translation);
        }
        unset($this->pendingTranslationInserts[$oid]);
    }
}
