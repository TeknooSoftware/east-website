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

namespace Teknoo\Tests\East\Website\Query\Item;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Query\QueryCollectionInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Query\Item\TopItemByLocationQuery;
use Teknoo\Tests\East\Website\Query\QueryCollectionTestTrait;
use Teknoo\East\Common\Query\Expr\In;
use Teknoo\East\Common\Query\Expr\InclusiveOr;
use Teknoo\East\Website\Object\Environment;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TopItemByLocationQuery::class)]
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

    public function testExecute(): void
    {
        $loader = $this->createStub(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['location' => 'fooBar', 'environment' => new InclusiveOr(['environment' => new In(['default'])], ['environment' => null])], $promise);

        $this->assertInstanceOf(TopItemByLocationQuery::class, $this->buildQuery()->execute($loader, $repository, $promise));
    }
    public function testExecuteWithEnvironment(): void
    {
        $validation = Environment::define('validation', Environment::default());

        $loader = $this->createStub(LoaderInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $promise = $this->createStub(PromiseInterface::class);

        $repository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'location' => 'fooBar',
                    'environment' => new InclusiveOr(
                        ['environment' => new In(['validation', 'default'])],
                        ['environment' => null],
                    ),
                ],
                $promise,
            );

        $query = new TopItemByLocationQuery('fooBar', $validation);
        $this->assertInstanceOf(TopItemByLocationQuery::class, $query->execute($loader, $repository, $promise));

        Environment::reset();
    }
}
