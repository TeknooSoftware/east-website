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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Writer;

use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\Contracts\ObjectInterface;

/**
 * Interface defining methods to implement in writer in charge of persisted objects, to save or delete persisted objects
 * to be used into recipes of this library.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface WriterInterface
{
    public function save(ObjectInterface $object, PromiseInterface $promise = null): WriterInterface;

    public function remove(ObjectInterface $object, PromiseInterface $promise = null): WriterInterface;
}
