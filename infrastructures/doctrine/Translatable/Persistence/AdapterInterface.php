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

namespace Teknoo\East\Website\Doctrine\Translatable\Persistence;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Teknoo\East\Website\Doctrine\Translatable\TranslationInterface;
use Teknoo\East\Website\Doctrine\Translatable\Wrapper\WrapperInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

/**
 * Interface to define adapter able to load and write translated value into a `TranslationInterface` implementation for
 * each field of a translatable object, and load, in an optimized query, all translations for an object
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface AdapterInterface
{
    public function executeAllDeferredLoadings(): AdapterInterface;

    public function loadAllTranslations(
        string $locale,
        string $identifier,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): AdapterInterface;

    public function findTranslation(
        string $locale,
        string $field,
        string $identifier,
        string $translationClass,
        string $objectClass,
        callable $callback
    ): AdapterInterface;

    public function removeAssociatedTranslations(
        string $identifier,
        string $translationClass,
        string $objectClass
    ): AdapterInterface;

    /**
     * @param string[] $updatedTranslations
     */
    public function removeOrphansTranslations(
        string $identifier,
        array $updatedTranslations,
        string $translationClass,
        string $objectClass
    ): AdapterInterface;

    public function persistTranslationRecord(
        TranslationInterface $translation
    ): AdapterInterface;

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    public function updateTranslationRecord(
        WrapperInterface $wrapped,
        ClassMetadata $metadata,
        string $field,
        TranslationInterface $translation
    ): AdapterInterface;

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $metadata
     */
    public function setTranslatedValue(
        WrapperInterface $wrapped,
        ClassMetadata $metadata,
        string $field,
        mixed $value
    ): AdapterInterface;
}
