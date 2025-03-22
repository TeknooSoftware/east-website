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
use Teknoo\East\Foundation\Manager\ManagerInterface;
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

    /**
     * @return PostLoader|MockObject
     */
    private function getPostLoader(): PostLoader
    {
        if (!$this->postLoader instanceof PostLoader) {
            $this->postLoader = $this->createMock(PostLoader::class);
        }

        return $this->postLoader;
    }

    public function buildStep(): LoadPost
    {
        return new LoadPost($this->getPostLoader());
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
            'objectViewKey' => 'post',
            'template' => 'foo',
        ]);

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
                $manager
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
                $manager
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
                $manager
            )
        );
    }
}
