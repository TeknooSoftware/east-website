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

namespace Teknoo\Tests\East\Website\Query\Item;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Query\Item\TopItemByLocationQuery;
use Teknoo\Tests\East\Website\Query\QueryCollectionTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Website\Query\Item\TopItemByLocationQuery
 */
class TopItemByLocationQueryTest extends TestCase
{
    use QueryCollectionTestTrait;

    /**
     * @inheritDoc
     */
    public function buildQuery(): QueryCollectionInterface
    {
        return new TopItemByLocationQuery('fooBar');
    }

    public function testExecute()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects(self::never())->method('success');
        $promise->expects(self::never())->method('fail');

        $repository->expects(self::once())
            ->method('findBy')
            ->with(['location' => 'fooBar', 'deletedAt' => null,], $promise);

        self::assertInstanceOf(
            TopItemByLocationQuery::class,
            $this->buildQuery()->execute($loader, $repository, $promise)
        );
    }
}
