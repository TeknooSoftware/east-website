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

namespace Teknoo\Tests\East\Website\Recipe\Step;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Teknoo\East\Common\Contracts\Query\QueryElementInterface;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Recipe\Step\LoadPost;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoadPost::class)]
class LoadPostTest extends TestCase
{
    private (PostLoader&Stub)|(PostLoader&MockObject)|null $postLoader = null;

    private (DatesService&Stub)|(DatesService&MockObject)|null $datesService = null;

    private function getPostLoader(bool $stub = false): (PostLoader&Stub)|(PostLoader&MockObject)
    {
        if (!$this->postLoader instanceof PostLoader) {
            if ($stub) {
                $this->postLoader = $this->createStub(PostLoader::class);
            } else {
                $this->postLoader = $this->createMock(PostLoader::class);
            }
        }

        return $this->postLoader;
    }

    private function getDatesService(bool $stub = false): (DatesService&Stub)|(DatesService&MockObject)
    {
        if (!$this->datesService instanceof DatesService) {
            if ($stub) {
                $this->datesService = $this->createStub(DatesService::class);
            } else {
                $this->datesService = $this->createMock(DatesService::class);
            }
        }

        return $this->datesService;
    }

    public function buildStep(): LoadPost
    {
        return new LoadPost($this->getPostLoader(true), $this->getDatesService(true));
    }

    public function testInvokeBadSlug(): void
    {
        $this->expectException(TypeError::class);

        $this->buildStep()(
            new stdClass(),
            $this->createStub(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(TypeError::class);

        $this->buildStep()(
            'slug',
            new stdClass()
        );
    }

    public function testInvokeFoundWithType(): void
    {
        $type = (new Type())->setTemplate('foo');
        $post = (new Post())->setType($type);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan')->with([
            Post::class => $post,
            'objectInstance' => $post,
            'template' => 'foo',
        ]);

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader(true)
            ->method('fetch')
            ->willReturnCallback(
                function (QueryElementInterface $query, PromiseInterface $promise) use ($post): PostLoader {
                    $promise->success($post);

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(LoadPost::class, $this->buildStep()(
            'foo',
            $manager,
            $this->createStub(ParametersBag::class),
        ));
    }

    public function testInvokeFoundWithNoType(): void
    {
        $post = (new Post());

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->with(
            new RuntimeException('Post type is not available')
        );
        $manager->expects($this->never())->method('updateWorkPlan');

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader(true)
            ->method('fetch')
            ->willReturnCallback(
                function (QueryElementInterface $query, PromiseInterface $promise) use ($post): PostLoader {
                    $promise->success($post);

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(LoadPost::class, $this->buildStep()(
            'foo',
            $manager,
            $this->createStub(ParametersBag::class),
        ));
    }

    public function testInvokeNotFound(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->with(
            new DomainException('foo', 404, new DomainException('foo'))
        );
        $manager->expects($this->never())->method('updateWorkPlan');

        $this->getDatesService(true)
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): DatesService&Stub {
                    $callable(new DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader(true)
            ->method('fetch')
            ->willReturnCallback(
                function (QueryElementInterface $query, PromiseInterface $promise): PostLoader {
                    $promise->fail(new DomainException('foo'));

                    return $this->getPostLoader();
                }
            );

        $this->assertInstanceOf(LoadPost::class, $this->buildStep()(
            'foo',
            $manager,
            $this->createStub(ParametersBag::class),
        ));
    }
}
