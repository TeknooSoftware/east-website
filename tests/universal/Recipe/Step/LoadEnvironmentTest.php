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

use Closure;
use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Teknoo\East\Common\Contracts\User\UserInterface;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Session\SessionInterface;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentNotFoundException;
use Teknoo\East\Website\Object\Environments;
use Teknoo\East\Website\Recipe\Step\LoadEnvironment;
use Teknoo\East\Website\Service\EnvironmentsFactory;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\Recipe\Promise\PromiseInterface;

use function array_keys;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoadEnvironment::class)]
class LoadEnvironmentTest extends TestCase
{
    private ?Environments $environments = null;

    private ?MenuGenerator $menuGenerator = null;

    private ?Closure $bagChecker = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environments = EnvironmentsFactory::fromDefinitions([
            'validation' => 'default',
            'testing' => 'default',
            'test-a' => 'testing',
        ]);
    }

    protected function tearDown(): void
    {
        Environment::reset();
        parent::tearDown();
    }

    private function getMenuGenerator(): MenuGenerator
    {
        return $this->menuGenerator ??= new MenuGenerator($this->createStub(ItemLoader::class));
    }

    /**
     * @param array<string, list<string>> $access
     */
    private function buildStep(array $access = ['testing' => ['ROLE_TESTER', 'ROLE_ADMIN']]): LoadEnvironment
    {
        return new LoadEnvironment(
            $this->environments,
            $access,
            $this->getMenuGenerator(),
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private function buildRequest(array $query = [], mixed $body = null): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($query);
        $request->method('getParsedBody')->willReturn($body);

        return $request;
    }

    private function buildUser(string ...$roles): UserInterface
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn($roles);

        return $user;
    }

    private function buildSession(?string $storedName): SessionInterface&MockObject
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            static function (string $key, PromiseInterface $promise) use ($session, $storedName): SessionInterface {
                if (null === $storedName) {
                    $promise->fail(new DomainException("$key is not available"));
                } else {
                    $promise->success($storedName);
                }

                return $session;
            }
        );

        return $session;
    }

    private function expectEnvironmentSelected(
        ManagerInterface&MockObject $manager,
        ParametersBag&MockObject $bag,
        string $name,
    ): void {
        $environment = $this->environments->get($name);
        $menuGenerator = $this->getMenuGenerator();

        $manager->expects($this->once())
            ->method('updateWorkPlan')
            ->with([
                'environment' => $environment,
                Environment::class => $environment,
            ]);
        $manager->expects($this->never())->method('error');

        $bagValues = [];
        $bag->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(
                static function (string $key, mixed $value) use (&$bagValues, $bag): ParametersBag {
                    $bagValues[$key] = $value;

                    return $bag;
                }
            );

        $this->bagChecker = function () use (&$bagValues, $environment, $menuGenerator): void {
            $this->assertSame(['environment', 'menuGenerator'], array_keys($bagValues));
            $this->assertSame($environment, $bagValues['environment']);
            //a copy of the generator, scoped to the selected environment
            $this->assertInstanceOf(MenuGenerator::class, $bagValues['menuGenerator']);
            $this->assertNotSame($menuGenerator, $bagValues['menuGenerator']);
            $this->assertSame($environment, $bagValues['menuGenerator']->getEnvironment());
            $this->assertSame(Environment::default(), $menuGenerator->getEnvironment());
        };
    }

    private function expectError(ManagerInterface&MockObject $manager, ParametersBag&MockObject $bag): void
    {
        $manager->expects($this->never())->method('updateWorkPlan');
        $manager->expects($this->once())
            ->method('error')
            ->with($this->callback(
                static fn (mixed $error): bool => $error instanceof EnvironmentNotFoundException
                    && 404 === $error->getCode(),
            ));
        $bag->expects($this->never())->method('set');
    }

    public function testWithoutParameterNorSession(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $step = $this->buildStep();
        $this->assertInstanceOf(LoadEnvironment::class, $step($manager, $this->buildRequest(), $bag));
        ($this->bagChecker)();
    }

    public function testWithoutParameterAndEmptySession(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $session = $this->buildSession(null);
        $session->expects($this->never())->method('set');
        $session->expects($this->never())->method('remove');

        $step = $this->buildStep();
        $this->assertInstanceOf(LoadEnvironment::class, $step($manager, $this->buildRequest(), $bag, $session));
        ($this->bagChecker)();
    }

    public function testWithAValidQueryParameter(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'validation');

        $session = $this->buildSession(null);
        $session->expects($this->once())->method('set')->with('website-env', 'validation');
        $session->expects($this->never())->method('remove');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => 'validation']), $bag, $session),
        );
        ($this->bagChecker)();
    }

    public function testWithAValidQueryParameterWithoutSession(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'validation');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => 'validation']), $bag),
        );
        ($this->bagChecker)();
    }

    public function testTheBodyParameterWinsOverTheQueryParameter(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'validation');

        $session = $this->buildSession(null);
        $session->expects($this->once())->method('set')->with('website-env', 'validation');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step(
                $manager,
                $this->buildRequest(['website-env' => 'testing'], ['website-env' => 'validation']),
                $bag,
                $session,
            ),
        );
        ($this->bagChecker)();
    }

    public function testNonStringParametersAreIgnored(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $session = $this->buildSession(null);
        $session->expects($this->never())->method('set');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step(
                $manager,
                $this->buildRequest(['website-env' => ['validation']], new stdClass()),
                $bag,
                $session,
            ),
        );
        ($this->bagChecker)();
    }

    public function testEmptyParameterIsIgnored(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => ''], ['website-env' => '']), $bag),
        );
        ($this->bagChecker)();
    }

    public function testWithAnUnknownEnvironmentInParameter(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectError($manager, $bag);

        $session = $this->buildSession('validation');
        $session->expects($this->never())->method('set');
        $session->expects($this->never())->method('remove');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => 'foo']), $bag, $session),
        );
    }

    public function testWithARestrictedEnvironmentForAnAnonymousVisitor(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectError($manager, $bag);

        $session = $this->buildSession(null);
        $session->expects($this->never())->method('set');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => 'testing']), $bag, $session),
        );
    }

    public function testWithARestrictedEnvironmentForAUserWithoutTheRole(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectError($manager, $bag);

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step(
                $manager,
                $this->buildRequest(['website-env' => 'testing']),
                $bag,
                null,
                $this->buildUser('ROLE_USER'),
            ),
        );
    }

    public function testWithARestrictedEnvironmentForAUserWithTheRole(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'testing');

        $session = $this->buildSession(null);
        $session->expects($this->once())->method('set')->with('website-env', 'testing');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step(
                $manager,
                $this->buildRequest(['website-env' => 'testing']),
                $bag,
                $session,
                $this->buildUser('ROLE_USER', 'ROLE_ADMIN'),
            ),
        );
        ($this->bagChecker)();
    }

    public function testAChildOfARestrictedEnvironmentIsPublicWhenNotListed(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'test-a');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => 'test-a']), $bag),
        );
        ($this->bagChecker)();
    }

    public function testWithoutAccessDefinitionsAllEnvironmentsArePublic(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'testing');

        $step = $this->buildStep([]);
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(['website-env' => 'testing']), $bag),
        );
        ($this->bagChecker)();
    }

    public function testWithAValidEnvironmentInSession(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'validation');

        $session = $this->buildSession('validation');
        $session->expects($this->never())->method('set');
        $session->expects($this->never())->method('remove');

        $step = $this->buildStep();
        $this->assertInstanceOf(LoadEnvironment::class, $step($manager, $this->buildRequest(), $bag, $session));
        ($this->bagChecker)();
    }

    public function testWithARestrictedEnvironmentInSessionForAUserWithTheRole(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'testing');

        $session = $this->buildSession('testing');
        $session->expects($this->never())->method('set');
        $session->expects($this->never())->method('remove');

        $step = $this->buildStep();
        $this->assertInstanceOf(
            LoadEnvironment::class,
            $step($manager, $this->buildRequest(), $bag, $session, $this->buildUser('ROLE_TESTER')),
        );
        ($this->bagChecker)();
    }

    public function testWithAnUnknownEnvironmentInSession(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $session = $this->buildSession('removed-env');
        $session->expects($this->never())->method('set');
        $session->expects($this->once())->method('remove')->with('website-env');

        $step = $this->buildStep();
        $this->assertInstanceOf(LoadEnvironment::class, $step($manager, $this->buildRequest(), $bag, $session));
        ($this->bagChecker)();
    }

    public function testWithARestrictedEnvironmentInSessionForAnAnonymousVisitor(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $session = $this->buildSession('testing');
        $session->expects($this->never())->method('set');
        $session->expects($this->once())->method('remove')->with('website-env');

        $step = $this->buildStep();
        $this->assertInstanceOf(LoadEnvironment::class, $step($manager, $this->buildRequest(), $bag, $session));
        ($this->bagChecker)();
    }

    public function testWithANonStringValueInSession(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $bag = $this->createMock(ParametersBag::class);
        $this->expectEnvironmentSelected($manager, $bag, 'default');

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            static function (string $key, PromiseInterface $promise) use ($session): SessionInterface {
                $promise->success(123);

                return $session;
            }
        );
        $session->expects($this->never())->method('remove');

        $step = $this->buildStep();
        $this->assertInstanceOf(LoadEnvironment::class, $step($manager, $this->buildRequest(), $bag, $session));
        ($this->bagChecker)();
    }
}
