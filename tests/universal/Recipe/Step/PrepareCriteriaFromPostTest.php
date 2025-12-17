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

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Query\Expr\ObjectReference;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PrepareCriteriaFromPost::class)]
class PrepareCriteriaFromPostTest extends TestCase
{
    private function getStep(): PrepareCriteriaFromPost
    {
        return new PrepareCriteriaFromPost();
    }

    public function testInvoke(): void
    {
        $post = $this->createStub(Post::class);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([
                'criteria' => [
                    'foo' => 'bar',
                    'post' => new ObjectReference($post),
                ]
            ])
            ->willReturnSelf();

        $this->assertInstanceOf(PrepareCriteriaFromPost::class, $this->getStep()(
            $manager,
            $post,
            ['foo' => 'bar']
        ));
    }

    public function testInvokeWithoutPost(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())
            ->method('error')
            ->with(
                new DomainException(
                    message: 'Post is not loaded',
                    code: 404
                )
            )->willReturnSelf();
        $manager->expects($this->never())
            ->method('updateWorkPlan');

        $this->assertInstanceOf(PrepareCriteriaFromPost::class, $this->getStep()(
            $manager,
            null,
            ['foo' => 'bar']
        ));
    }
}
