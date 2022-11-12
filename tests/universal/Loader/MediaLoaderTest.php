<?php

/**
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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Loader;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\MediaRepositoryInterface;
use Teknoo\East\Website\Loader\MediaLoader;
use Teknoo\East\Website\Object\Media;
use Teknoo\East\Common\Query\Expr\InclusiveOr;
use Teknoo\Recipe\Promise\Promise;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Website\Loader\MediaLoader
 */
class MediaLoaderTest extends TestCase
{
    use LoaderTestTrait;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RepositoryInterface
     */
    public function getRepositoryMock(): RepositoryInterface
    {
        if (!$this->repository instanceof RepositoryInterface) {
            $this->repository = $this->createMock(MediaRepositoryInterface::class);
        }

        return $this->repository;
    }

    /**
     * @return LoaderInterface|MediaLoader
     */
    public function buildLoader(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new MediaLoader($repository);
    }

    /**
     * @return Media
     */
    public function getEntity()
    {
        return new Media();
    }

    public function testLoad()
    {
        /**
         * @var \PHPUnit\Framework\MockObject\MockObject $promiseMock
         */
        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->expects(self::never())->method('success');
        $promiseMock->expects(self::never())->method('fail');

        $this->getRepositoryMock()
            ->expects(self::any())
            ->method('findOneBy')
            ->with(
                [new InclusiveOr(
                    ['id'=>'fooBar'],
                    ['metadata.legacyId' => 'fooBar',]
                )],
                $promiseMock
            );

        self::assertInstanceOf(
            LoaderInterface::class,
            $this->buildLoader()->load('fooBar', $promiseMock)
        );
    }
}
