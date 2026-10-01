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

use RuntimeException;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Common\Object\Media;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * Writer to replace, in Behat tests, the ODM Media Writer, which requires a GridFS repository (not available with the
 * in-memory object manager). Media are registered into the in-memory repository, to be loadable by the MediaLoader.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements WriterInterface<Media>
 */
class FakeMediaWriter implements WriterInterface
{
    public function __construct(
        private readonly FeatureContext $context,
    ) {
    }

    public function save(
        ObjectInterface $object,
        ?PromiseInterface $promise = null,
        ?bool $preferRealDateOnUpdate = null,
    ): WriterInterface {
        if (!$object instanceof Media) {
            $promise?->fail(new RuntimeException('This type of media is not managed by this writer'));

            return $this;
        }

        $this->context->registerMedia($object, true);

        $promise?->success($object);

        return $this;
    }

    public function remove(ObjectInterface $object, ?PromiseInterface $promise = null): WriterInterface
    {
        $promise?->success($object);

        return $this;
    }
}
