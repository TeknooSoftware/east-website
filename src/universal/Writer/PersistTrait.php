<?php

declare(strict_types=1);

/**
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
 * @copyright   Copyright (c) 2009-2019 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website\Writer;

use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\DBSource\ManagerInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait PersistTrait
{
    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * PersistTrait constructor.
     * @param ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param object $object
     * @param PromiseInterface|null $promise
     * @return self
     * @throws \Throwable
     */
    private function persist($object, PromiseInterface $promise = null): self
    {
        try {
            $this->manager->persist($object);
            $this->manager->flush();

            if ($promise instanceof PromiseInterface) {
                $promise->success($object);
            }
        } catch (\Throwable $error) {
            if ($promise instanceof PromiseInterface) {
                $promise->fail($error);
            } else {
                throw $error;
            }
        }

        return $this;
    }
}
