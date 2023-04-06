<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Doctrine\Translatable\ObjectManager;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Teknoo\East\Website\Contracts\Object\TranslatableInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Website\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;

/**
 * Interface to help this extension to work evenly with Doctrine Document Manager or Doctrine Entity Manager.
 * This manager extends `Teknoo\East\Common\Contracts\DBSource\ManagerInterface` because this parent interface define
 * wrapper for Persistence Manager to use in East Website.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface AdapterInterface extends ManagerInterface
{
    public function findClassMetadata(string $class, TranslatableListener $listener): AdapterInterface;

    public function ifObjectHasChangeSet(TranslatableInterface $object, callable $callback): AdapterInterface;

    public function foreachScheduledObjectInsertions(callable $callback): AdapterInterface;

    public function foreachScheduledObjectUpdates(callable $callback): AdapterInterface;

    public function foreachScheduledObjectDeletions(callable $callback): AdapterInterface;

    /**
     * @param ClassMetadata<IdentifiedObjectInterface> $meta
     */
    public function recomputeSingleObjectChangeSet(
        ClassMetadata $meta,
        TranslatableInterface $object
    ): AdapterInterface;

    public function setObjectPropertyInManager(string $oid, string $property, mixed $value): AdapterInterface;
}
