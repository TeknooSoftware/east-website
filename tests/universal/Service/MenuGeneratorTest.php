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

namespace Teknoo\Tests\East\Website\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Item;
use Teknoo\East\Website\Query\Content\PublishedContentFromIdsQuery;
use Teknoo\East\Website\Query\Item\TopItemByLocationQuery;
use Teknoo\East\Website\Service\MenuGenerator;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(MenuGenerator::class)]
class MenuGeneratorTest extends TestCase
{
    private (ItemLoader&MockObject)|null $itemLoader = null;

    public function getItemLoader(): ItemLoader&MockObject
    {
        if (!$this->itemLoader instanceof ItemLoader) {
            $this->itemLoader = $this->createMock(ItemLoader::class);
        }

        return $this->itemLoader;
    }

    public function buildService(): MenuGenerator
    {
        return new MenuGenerator(
            $this->getItemLoader(),
            ['foo'],
        );
    }

    public function testExtract(): void
    {
        $item1 = (new Item())->setId('i1')->setLocation('location1');
        $item2 = (new Item())->setId('i2')->setLocation('location1')->setContent(
            new class () extends Content {
                public function getId(): string
                {
                    return 'c1';
                }
            }
        );
        $item3 = (new Item())->setId('i3')->setLocation('location1')->setParent($item1)->setContent(
            new class () extends Content {
                public function getId(): string
                {
                    return 'c2';
                }
            }
        );
        ;
        $item4 = (new Item())->setId('i3')->setLocation('location1')->setParent($item1);

        (new Content())->setId('c1');
        (new Content())->setId('c2');
        (new Content())->setId('c3');
        $content4 = (new Content())->setId('c4');
        $item4->setContent($content4);

        $this->getItemLoader()
            ->expects($this->once())
            ->method('query')
            ->with(new TopItemByLocationQuery(['foo', 'location1']))
            ->willReturnCallback(function ($value, PromiseInterface $promise) use ($item1, $item2, $item3, $item4): \Teknoo\East\Website\Loader\ItemLoader {
                $promise->success([$item1, $item2, $item3, $item4]);

                return $this->getItemLoader();
            });

        $stack = [];
        $service = $this->buildService();
        foreach ($service->extract('location1') as $key => $element) {
            $stack[$key][] = $element;
        }

        $this->assertEquals(['parent' => [$item1], 'top' => [$item2], 'i1' => [$item3, $item4]], $stack);

        $stack = [];
        foreach ($service->extract('location1') as $key => $element) {
            $stack[$key][] = $element;
        }

        $this->assertEquals(['parent' => [$item1], 'top' => [$item2], 'i1' => [$item3, $item4]], $stack);
    }

    public function testExtractWithoutTop(): void
    {
        $this->getItemLoader()
            ->method('query')
            ->with(new TopItemByLocationQuery(['foo', 'location1']))
            ->willReturnCallback(function ($value, PromiseInterface $promise): \Teknoo\East\Website\Loader\ItemLoader {
                $promise->success([]);

                return $this->getItemLoader();
            });

        $stack = [];
        foreach ($this->buildService()->extract('location1') as $key => $element) {
            $stack[$key][] = $element;
        }

        $this->assertEquals([], $stack);
    }

    public function testExtractWithoutContent(): void
    {
        $item1 = (new Item())->setId('i1')->setLocation('location1');
        $item2 = (new Item())->setId('i2')->setLocation('location1');
        $item3 = (new Item())->setId('i3')->setLocation('location1')->setParent($item1);

        $this->getItemLoader()
            ->expects($this->once())
            ->method('query')
            ->with(new TopItemByLocationQuery(['foo', 'location1']))
            ->willReturnCallback(function ($value, PromiseInterface $promise) use ($item1, $item2, $item3): \Teknoo\East\Website\Loader\ItemLoader {
                $promise->success([$item1, $item2, $item3]);

                return $this->getItemLoader();
            });

        $stack = [];
        foreach ($this->buildService()->extract('location1') as $key => $element) {
            $stack[$key][] = $element;
        }

        $this->assertEquals(['parent' => [$item1], 'top' => [$item2], 'i1' => [$item3]], $stack);
    }
}
