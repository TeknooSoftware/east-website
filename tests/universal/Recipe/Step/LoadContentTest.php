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
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Recipe\Step\LoadContent;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Website\Recipe\Step\LoadContent
 */
class LoadContentTest extends TestCase
{
    private ?ContentLoader $contentLoader = null;

    /**
     * @return ContentLoader|MockObject
     */
    private function getContentLoader(): ContentLoader
    {
        if (!$this->contentLoader instanceof ContentLoader) {
            $this->contentLoader = $this->createMock(ContentLoader::class);
        }

        return $this->contentLoader;
    }

    public function buildStep(): LoadContent
    {
        return new LoadContent($this->getContentLoader());
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
        $content = (new Content())->setType($type);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::never())->method('error');
        $manager->expects(self::once())->method('updateWorkPlan')->with([
            Content::class => $content,
            'objectInstance' => $content,
            'objectViewKey' => 'content',
            'template' => 'foo',
        ]);

        $this->getContentLoader()
            ->expects(self::any())
            ->method('query')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) use ($content) {
                    $promise->success($content);

                    return $this->getContentLoader();
                }
            );

        self::assertInstanceOf(
            LoadContent::class,
            $this->buildStep()(
                'foo',
                $manager
            )
        );
    }

    public function testInvokeFoundWithNoType()
    {
        $content = (new Content());

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())->method('error')->with(
            new \RuntimeException('Content type is not available')
        );
        $manager->expects(self::never())->method('updateWorkPlan');

        $this->getContentLoader()
            ->expects(self::any())
            ->method('query')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) use ($content) {
                    $promise->success($content);

                    return $this->getContentLoader();
                }
            );

        self::assertInstanceOf(
            LoadContent::class,
            $this->buildStep()(
                'foo',
                $manager
            )
        );
    }

    public function testInvokeNotFound()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())->method('error')->with(
            new \DomainException('foo', 404, new \DomainException('foo'))
        );
        $manager->expects(self::never())->method('updateWorkPlan');

        $this->getContentLoader()
            ->expects(self::any())
            ->method('query')
            ->willReturnCallback(
                function ($query, PromiseInterface $promise) {
                    $promise->fail(new \DomainException('foo'));

                    return $this->getContentLoader();
                }
            );

        self::assertInstanceOf(
            LoadContent::class,
            $this->buildStep()(
                'foo',
                $manager
            )
        );
    }
}
