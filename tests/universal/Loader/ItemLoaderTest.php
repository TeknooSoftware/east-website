<?php

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
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Website\Loader;

use Doctrine\Common\Persistence\ObjectRepository;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\Object\Item;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Loader\LoaderInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Website\Loader\ItemLoader
 * @covers      \Teknoo\East\Website\Loader\CollectionLoaderTrait
 */
class ItemLoaderTest extends \PHPUnit\Framework\TestCase
{
    use LoaderTestTrait;

    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectRepository
     */
    public function getRepositoryMock(): ObjectRepository
    {
        if (!$this->repository instanceof ObjectRepository) {
            $this->repository = $this->createMock(ObjectRepository::class);
        }

        return $this->repository;
    }

    /**
     * @return LoaderInterface|ItemLoader
     */
    public function buildLoader(): LoaderInterface
    {
        return new ItemLoader($this->getRepositoryMock());
    }

    /**
     * @return Item
     */
    public function getEntity()
    {
        return new Item();
    }

    public function testTopByLocation()
    {
        $entity = $this->getEntity();

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject $promiseMock
         *
         */
        $promiseMock = $this->createMock(PromiseInterface::class);
        $promiseMock->expects(self::once())->method('success')->with([$entity]);
        $promiseMock->expects(self::never())->method('fail');

        $this->getRepositoryMock()
            ->expects(self::any())
            ->method('findBy')
            ->with(['location' => 'abc', 'deletedAt'=>null], ['position' => 'ASC'])
            ->willReturn([$entity]);

        self::assertInstanceOf(
            LoaderInterface::class,
            $this->buildLoader()->topByLocation(
                'abc',
                $promiseMock
            )
        );
    }
}