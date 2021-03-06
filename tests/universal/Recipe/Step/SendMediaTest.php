<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Website\Recipe\Step;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Website\Object\Media;
use Teknoo\East\Website\Object\MediaMetadata;
use Teknoo\East\Website\Recipe\Step\SendMedia;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Website\Recipe\Step\SendMedia
 */
class SendMediaTest extends TestCase
{
    private ?ResponseFactoryInterface $responseFactory = null;

    /**
     * @return ResponseFactoryInterface|MockObject
     */
    private function getResponseFactory(): ResponseFactoryInterface
    {
        if (!$this->responseFactory instanceof ResponseFactoryInterface) {
            $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        }

        return $this->responseFactory;
    }

    public function buildStep(): SendMedia
    {
        return new SendMedia($this->getResponseFactory());
    }

    public function testInvokeBadClient()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            new \stdClass(),
            $this->createMock(Media::class),
            $this->createMock(StreamInterface::class)
        );
    }

    public function testInvokeBadMedia()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            $this->createMock(ClientInterface::class),
            new \stdClass(),
            $this->createMock(StreamInterface::class)
        );
    }

    public function testInvokeBadStream()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            $this->createMock(ClientInterface::class),
            $this->createMock(Media::class),
            new \stdClass()
        );
    }

    public function testInvokeWithMetadata()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('acceptResponse');

        $media = $this->createMock(Media::class);
        $media->expects(self::any())->method('getMetadata')->willReturn(
            $this->createMock(MediaMetadata::class)
        );

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();
        $this->getResponseFactory()
            ->expects(self::any())
            ->method('createResponse')
            ->willReturn($response);

        self::assertInstanceOf(
            SendMedia::class,
            $this->buildStep()(
                $client,
                $media,
                $stream
            )
        );
    }

    public function testInvokeWithoutMetadata()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('acceptResponse');

        $media = $this->createMock(Media::class);
        $media->expects(self::any())->method('getMetadata')->willReturn(null);

        $stream = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();
        $this->getResponseFactory()
            ->expects(self::any())
            ->method('createResponse')
            ->willReturn($response);

        self::assertInstanceOf(
            SendMedia::class,
            $this->buildStep()(
                $client,
                $media,
                $stream
            )
        );
    }
}
