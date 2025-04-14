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
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Recipe\Step;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Recipe\Step\LoadPost;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoadPost::class)]
class LoadPostTest extends TestCase
{
    private ?PostLoader $postLoader = null;

    private ?DatesService $datesService = null;

    private function getPostLoader(): PostLoader&MockObject
    {
        if (!$this->postLoader instanceof PostLoader) {
            $this->postLoader = $this->createMock(PostLoader::class);
        }

        return $this->postLoader;
    }

    private function getDatesService(): DatesService&MockObject
    {
        if (!$this->datesService instanceof DatesService) {
            $this->datesService = $this->createMock(DatesService::class);
        }

        return $this->datesService;
    }

    public function buildStep(): LoadPost
    {
        return new LoadPost($this->getPostLoader(), $this->getDatesService());
    }

    public function testInvokeBadSlug()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            'slug',
            new \stdClass()
        );
    }

    public function testInvokeFoundWithType()
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

        $this->getDatesService()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function ($callable) {
                    $callable(new \DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader()
            ->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) use ($post) {
                    $promise->success($post);

                    return $this->getPostLoader();
                }
            );

        self::assertInstanceOf(
            LoadPost::class,
            $this->buildStep()(
                'foo',
                $manager,
                $this->createMock(ParametersBag::class),
            )
        );
    }

    public function testInvokeFoundWithNoType()
    {
        $post = (new Post());

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->with(
            new \RuntimeException('Post type is not available')
        );
        $manager->expects($this->never())->method('updateWorkPlan');

        $this->getDatesService()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function ($callable) {
                    $callable(new \DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader()
            ->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) use ($post) {
                    $promise->success($post);

                    return $this->getPostLoader();
                }
            );

        self::assertInstanceOf(
            LoadPost::class,
            $this->buildStep()(
                'foo',
                $manager,
                $this->createMock(ParametersBag::class),
            )
        );
    }

    public function testInvokeNotFound()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->with(
            new \DomainException('foo', 404, new \DomainException('foo'))
        );
        $manager->expects($this->never())->method('updateWorkPlan');

        $this->getDatesService()
            ->expects($this->any())
            ->method('passMeTheDate')
            ->willReturnCallback(
                function ($callable) {
                    $callable(new \DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getPostLoader()
            ->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) {
                    $promise->fail(new \DomainException('foo'));

                    return $this->getPostLoader();
                }
            );

        self::assertInstanceOf(
            LoadPost::class,
            $this->buildStep()(
                'foo',
                $manager,
                $this->createMock(ParametersBag::class),
            )
        );
    }
}
