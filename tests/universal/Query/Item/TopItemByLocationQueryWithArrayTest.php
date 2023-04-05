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

namespace Teknoo\Tests\East\Website\Query\Item;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\East\Common\Query\Expr\In;
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
class TopItemByLocationQueryWithArrayTest extends TestCase
{
    use QueryCollectionTestTrait;

    /**
     * @inheritDoc
     */
    public function buildQuery(): QueryCollectionInterface
    {
        return new TopItemByLocationQuery(['fooBar']);
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
            ->with(['location' => new In(['fooBar']), 'deletedAt' => null,], $promise);

        self::assertInstanceOf(
            TopItemByLocationQuery::class,
            $this->buildQuery()->execute($loader, $repository, $promise)
        );
    }
}
