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

namespace Teknoo\East\Website\Doctrine\Translatable\ObjectManager;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Teknoo\East\Website\DBSource\ManagerInterface;
use Teknoo\East\Website\Doctrine\Translatable\TranslatableListener;
use Teknoo\East\Website\Object\TranslatableInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface AdapterInterface extends ManagerInterface
{
    public function findClassMetadata(string $class, TranslatableListener $listener): AdapterInterface;

    public function ifObjectHasChangeSet(TranslatableInterface $object, callable $callback): AdapterInterface;

    public function foreachScheduledObjectInsertions(callable $callback): AdapterInterface;

    public function foreachScheduledObjectUpdates(callable $callback): AdapterInterface;

    public function foreachScheduledObjectDeletions(callable $callback): AdapterInterface;

    public function recomputeSingleObjectChangeSet(
        ClassMetadata $meta,
        TranslatableInterface $object
    ): AdapterInterface;

    /**
     * @param mixed $value
     */
    public function setOriginalObjectProperty(string $oid, string $property, $value): AdapterInterface;
}
