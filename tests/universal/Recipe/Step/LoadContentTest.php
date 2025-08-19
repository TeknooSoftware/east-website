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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Recipe\Step\LoadContent;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(LoadContent::class)]
class LoadContentTest extends TestCase
{
    private ?ContentLoader $contentLoader = null;

    private ?DatesService $datesService = null;

    private function getContentLoader(): ContentLoader&MockObject
    {
        if (!$this->contentLoader instanceof ContentLoader) {
            $this->contentLoader = $this->createMock(ContentLoader::class);
        }

        return $this->contentLoader;
    }

    private function getDatesService(): DatesService&MockObject
    {
        if (!$this->datesService instanceof DatesService) {
            $this->datesService = $this->createMock(DatesService::class);
        }

        return $this->datesService;
    }

    public function buildStep(): LoadContent
    {
        return new LoadContent(
            $this->getContentLoader(),
            $this->getDatesService(),
        );
    }

    public function testInvokeBadSlug(): void
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            new \stdClass(),
            $this->createMock(ManagerInterface::class)
        );
    }

    public function testInvokeBadManager(): void
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            'slug',
            new \stdClass()
        );
    }

    public function testInvokeFoundWithType(): void
    {
        $type = (new Type())->setTemplate('foo');
        $content = (new Content())->setType($type);

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->never())->method('error');
        $manager->expects($this->once())->method('updateWorkPlan')->with([
            Content::class => $content,
            'objectInstance' => $content,
            'template' => 'foo',
        ]);

        $this->getDatesService()
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): \Teknoo\East\Foundation\Time\DatesService&\PHPUnit\Framework\MockObject\MockObject {
                    $callable(new \DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getContentLoader()
            ->method('fetch')
            ->willReturnCallback(
                function (\Teknoo\East\Common\Contracts\Query\QueryElementInterface $query, PromiseInterface $promise) use ($content): \Teknoo\East\Website\Loader\ContentLoader&\PHPUnit\Framework\MockObject\MockObject {
                    $promise->success($content);

                    return $this->getContentLoader();
                }
            );

        $bag = $this->createMock(ParametersBag::class);
        $bag->expects($this->once())->method('set')->with('content');

        $this->assertInstanceOf(LoadContent::class, $this->buildStep()(
            'foo',
            $manager,
            $bag,
        ));
    }

    public function testInvokeFoundWithNoType(): void
    {
        $content = (new Content());

        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->with(
            new \RuntimeException('Content type is not available')
        );
        $manager->expects($this->never())->method('updateWorkPlan');

        $this->getDatesService()
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): \Teknoo\East\Foundation\Time\DatesService&\PHPUnit\Framework\MockObject\MockObject {
                    $callable(new \DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getContentLoader()
            ->method('fetch')
            ->willReturnCallback(
                function (\Teknoo\East\Common\Contracts\Query\QueryElementInterface $query, PromiseInterface $promise) use ($content): \Teknoo\East\Website\Loader\ContentLoader&\PHPUnit\Framework\MockObject\MockObject {
                    $promise->success($content);

                    return $this->getContentLoader();
                }
            );

        $this->assertInstanceOf(LoadContent::class, $this->buildStep()(
            'foo',
            $manager,
            $this->createMock(ParametersBag::class),
        ));
    }

    public function testInvokeNotFound(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects($this->once())->method('error')->with(
            new \DomainException('foo', 404, new \DomainException('foo'))
        );
        $manager->expects($this->never())->method('updateWorkPlan');

        $this->getDatesService()
            ->method('passMeTheDate')
            ->willReturnCallback(
                function (callable $callable): \Teknoo\East\Foundation\Time\DatesService&\PHPUnit\Framework\MockObject\MockObject {
                    $callable(new \DateTimeImmutable('2025-03-24'));

                    return $this->getDatesService();
                }
            );

        $this->getContentLoader()
            ->method('fetch')
            ->willReturnCallback(
                function (\Teknoo\East\Common\Contracts\Query\QueryElementInterface $query, PromiseInterface $promise): \Teknoo\East\Website\Loader\ContentLoader&\PHPUnit\Framework\MockObject\MockObject {
                    $promise->fail(new \DomainException('foo'));

                    return $this->getContentLoader();
                }
            );

        $this->assertInstanceOf(LoadContent::class, $this->buildStep()(
            'foo',
            $manager,
            $this->createMock(ParametersBag::class),
        ));
    }
}
