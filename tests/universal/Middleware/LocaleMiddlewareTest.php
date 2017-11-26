<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Website\Middleware;

use Gedmo\Translatable\TranslatableListener;
use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Foundation\Session\SessionInterface;
use Teknoo\East\Website\Middleware\LocaleMiddleware;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Website\Middleware\LocaleMiddleware
 */
class LocaleMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatableListener
     */
    private $listenerTranslatable;

    /**
     * @return TranslatableListener|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getListenerTranslatable(): TranslatableListener
    {
        if (!$this->listenerTranslatable instanceof TranslatableListener) {
            $this->listenerTranslatable = $this->createMock(TranslatableListener::class);
        }

        return $this->listenerTranslatable;
    }

    /**
     * @param string $locale
     * @return LocaleMiddleware
     */
    public function buildMiddleware($locale='en'): LocaleMiddleware
    {
        return new LocaleMiddleware($this->getListenerTranslatable(), $locale);
    }

    /**
     * @expectedException \TypeError
     */
    public function testExecuteBadClient()
    {
        $this->buildMiddleware()->execute(
            new \stdClass(),
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(ManagerInterface::class)
        );
    }

    /**
     * @expectedException \TypeError
     */
    public function testExecuteBadRequest()
    {
        $this->buildMiddleware()->execute(
            $this->createMock(ClientInterface::class),
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    /**
     * @expectedException \TypeError
     */
    public function testExecuteBadManager()
    {
        $this->buildMiddleware()->execute(
            $this->createMock(ClientInterface::class),
            $this->createMock(ServerRequestInterface::class),
            new \stdClass()
        );
    }

    public function testExecuteNoInRequestNoInSession()
    {
        $client = $this->createMock(ClientInterface::class);

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequestFinal = $this->createMock(ServerRequestInterface::class);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())
            ->method('continueExecution')
            ->with($client, $serverRequestFinal)
            ->willReturnSelf();

        $this->getListenerTranslatable()
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with('en');

        $serverRequest->expects(self::once())
            ->method('withAttribute')
            ->with('locale', 'en')
            ->willReturn($serverRequestFinal);

        $sessionMiddleware = $this->createMock(SessionInterface::class);
        $sessionMiddleware->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($key, PromiseInterface $promise) use ($sessionMiddleware) {
                $promise->fail(new \DomainException());

                return $sessionMiddleware;
            });

        $serverRequest->expects(self::any())
            ->method('getAttribute')
            ->with(SessionInterface::ATTRIBUTE_KEY)
            ->willReturn($sessionMiddleware);

        self::assertInstanceOf(
            LocaleMiddleware::class,
            $this->buildMiddleware('en')->execute($client, $serverRequest, $manager)
        );
    }

    public function testExecuteNoInRequestInSession()
    {
        $client = $this->createMock(ClientInterface::class);

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequestFinal = $this->createMock(ServerRequestInterface::class);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())
            ->method('continueExecution')
            ->with($client, $serverRequestFinal)
            ->willReturnSelf();

        $this->getListenerTranslatable()
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with('fr');

        $serverRequest->expects(self::once())
            ->method('withAttribute')
            ->with('locale', 'fr')
            ->willReturn($serverRequestFinal);

        $sessionMiddleware = $this->createMock(SessionInterface::class);
        $sessionMiddleware->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($key, PromiseInterface $promise) use ($sessionMiddleware) {
                $promise->success('fr');

                return $sessionMiddleware;
            });

        $serverRequest->expects(self::any())
            ->method('getAttribute')
            ->with(SessionInterface::ATTRIBUTE_KEY)
            ->willReturn($sessionMiddleware);

        self::assertInstanceOf(
            LocaleMiddleware::class,
            $this->buildMiddleware('en')->execute($client, $serverRequest, $manager)
        );
    }

    public function testExecuteInRequestInSession()
    {
        $client = $this->createMock(ClientInterface::class);

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequestFinal = $this->createMock(ServerRequestInterface::class);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())
            ->method('continueExecution')
            ->with($client, $serverRequestFinal)
            ->willReturnSelf();

        $this->getListenerTranslatable()
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with('es');

        $serverRequest->expects(self::once())
            ->method('withAttribute')
            ->with('locale', 'es')
            ->willReturn($serverRequestFinal);

        $sessionMiddleware = $this->createMock(SessionInterface::class);
        $sessionMiddleware->expects(self::never())->method('get');
        $sessionMiddleware->expects(self::once())->method('set')->with('locale', 'es');

        $serverRequest->expects(self::any())
            ->method('getQueryParams')
            ->willReturn(['locale' => 'es']);

        $serverRequest->expects(self::any())
            ->method('getAttribute')
            ->with(SessionInterface::ATTRIBUTE_KEY)
            ->willReturn($sessionMiddleware);

        self::assertInstanceOf(
            LocaleMiddleware::class,
            $this->buildMiddleware('en')->execute($client, $serverRequest, $manager)
        );
    }
}