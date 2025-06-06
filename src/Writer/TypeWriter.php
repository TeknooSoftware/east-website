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
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Writer;

use Teknoo\East\Common\Writer\PersistTrait;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Website\Object\Type;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Object writer in charge of object `Teknoo\East\Website\Object\Type`.
 * The writer will update object's timestamp before update. The object persisted will be passed, with its new id for
 * new persisted object, to the promise, else the error is also passed to the promise.
 * Must provide an implementation of `Teknoo\East\Common\Contracts\DBSource\ManagerInterface` to be able work.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements WriterInterface<Type>
 */
class TypeWriter implements WriterInterface
{
    /**
     * @use PersistTrait<Type>
     */
    use PersistTrait;

    /**
     * @throws Throwable
     */
    public function save(
        ObjectInterface $object,
        ?PromiseInterface $promise = null,
        ?bool $preferRealDateOnUpdate = null,
    ): WriterInterface {
        $this->persist($object, $promise, $preferRealDateOnUpdate);

        return $this;
    }
}
