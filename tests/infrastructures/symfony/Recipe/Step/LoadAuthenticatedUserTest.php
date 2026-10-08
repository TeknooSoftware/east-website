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

namespace Teknoo\Tests\East\WebsiteBundle\Recipe\Step;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Teknoo\East\Common\Contracts\User\UserInterface as CommonUserInterface;
use Teknoo\East\Common\Object\User;
use Teknoo\East\CommonBundle\Object\AbstractUser;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\WebsiteBundle\Recipe\Step\LoadAuthenticatedUser;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoadAuthenticatedUser::class)]
class LoadAuthenticatedUserTest extends TestCase
{
    private (TokenStorageInterface&Stub)|null $tokenStorage = null;

    private function getTokenStorage(): TokenStorageInterface&Stub
    {
        if (!$this->tokenStorage instanceof TokenStorageInterface) {
            $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
        }

        return $this->tokenStorage;
    }

    private function buildStep(): LoadAuthenticatedUser
    {
        return new LoadAuthenticatedUser(
            $this->getTokenStorage(),
        );
    }

    public function testWithoutTokenInStorage(): void
    {
        $this->getTokenStorage()
            ->method('getToken')
            ->willReturn(null);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('updateWorkPlan');

        $step = $this->buildStep();
        $this->assertSame($step, $step($manager));
    }

    public function testWithNotAnEastCommonUserInToken(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->createStub(SymfonyUserInterface::class));

        $this->getTokenStorage()
            ->method('getToken')
            ->willReturn($token);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('updateWorkPlan');

        $step = $this->buildStep();
        $this->assertSame($step, $step($manager));
    }

    public function testWithAnEastCommonUserInToken(): void
    {
        $user = new User();
        $symfonyUser = $this->createStub(AbstractUser::class);
        $symfonyUser->method('getWrappedUser')->willReturn($user);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($symfonyUser);

        $this->getTokenStorage()
            ->method('getToken')
            ->willReturn($token);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([
                CommonUserInterface::class => $user,
                User::class => $user,
            ])
            ->willReturnSelf();

        $step = $this->buildStep();
        $this->assertSame($step, $step($manager));
    }

    public function testWithAUserAlreadyInTheWorkplan(): void
    {
        $this->getTokenStorage()
            ->method('getToken')
            ->willReturn($this->createStub(TokenInterface::class));

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('updateWorkPlan');

        $step = $this->buildStep();
        $this->assertSame($step, $step($manager, new User()));
    }
}
