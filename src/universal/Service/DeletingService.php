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

namespace Teknoo\East\Website\Service;

use Teknoo\East\Website\Object\DeletableInterface;
use Teknoo\East\Website\Writer\WriterInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DeletingService
{
    /**
     * @var \DateTime
     */
    private $currentDate;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * DeleteService constructor.
     * @param WriterInterface $writer
     */
    public function __construct(WriterInterface $writer)
    {
        $this->writer = $writer;
    }

    /**
     * @param \DateTime $currentDate
     * @return DeletingService
     */
    public function setCurrentDate(\DateTime $currentDate): DeletingService
    {
        $this->currentDate = $currentDate;
        return $this;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    private function getCurrentDate(): \DateTime
    {
        if ($this->currentDate instanceof \DateTime) {
            return $this->currentDate;
        }

        return new \DateTime();
    }

    /**
     * @param DeletableInterface $object
     * @return DeletingService
     * @throws \Exception
     */
    public function delete(DeletableInterface $object) : DeletingService
    {
        $object->setDeletedAt($this->getCurrentDate());

        $this->writer->save($object);

        return $this;
    }
}
