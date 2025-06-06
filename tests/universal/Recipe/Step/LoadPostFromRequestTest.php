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
use Teknoo\East\Website\Loader\PostLoader;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoadPostFromRequest::class)]
class LoadPostFromRequestTest extends TestCase
{
    private (PostLoader&MockObject)|null $postLoader = null;

    private function getPostLoader(): (PostLoader&MockObject)|null
    {
        if (null === $this->postLoader) {
            $this->postLoader = $this->createMock(PostLoader::class);
        }

        return $this->postLoader;
    }

    private function getStep(): LoadPostFromRequest
    {
        return new LoadPostFromRequest($this->getPostLoader());
    }

    public function testInvoke()
    {
        $post = $this->createMock(Post::class);

        $this->getPostLoader()
            ->expects($this->any())
            ->method('load')
            ->with('foo')
            ->willReturnCallback(
                function ($id, $promise) use ($post) {
                    $promise->success($post);

                    return $this->getPostLoader();
                }
            );

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([
                'post' => $post,
                'parameters' => [
                    'bar' => 'foo',
                    'postId' => 'foo',
                ],
            ])
            ->willReturnSelf();

        self::assertInstanceOf(
            LoadPostFromRequest::class,
            $this->getStep()(
                $manager,
                'foo',
                ['bar' => 'foo'],
            )
        );
    }

    public function testInvokeNotFound()
    {
        $post = $this->createMock(Post::class);

        $this->getPostLoader()
            ->expects($this->any())
            ->method('load')
            ->with('foo')
            ->willReturnCallback(
                function ($id, $promise) use ($post) {
                    $promise->fail(new \DomainException('Post not found', 404));

                    return $this->getPostLoader();
                }
            );

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->willReturnSelf();
        $manager->expects($this->never())->method('updateWorkPlan');

        self::assertInstanceOf(
            LoadPostFromRequest::class,
            $this->getStep()(
                $manager,
                'foo',
                ['bar' => 'foo'],
            )
        );
    }
}
