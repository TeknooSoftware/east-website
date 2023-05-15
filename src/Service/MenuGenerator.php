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
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Service;

use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Common\Contracts\Service\ProxyDetectorInterface;
use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Object\Item;
use Teknoo\East\Website\Query\Content\PublishedContentFromIdsQuery;
use Teknoo\East\Website\Query\Item\TopItemByLocationQuery;

use function array_diff;
use function array_keys;
use function array_unique;

/**
 * Service to generate a menu from persisted item and loader. It will use the query TopItemByLocationQuery to extract
 * an ordered list of items, by items's order and items's hierarchie and build a PHP Generator to call in a template
 * to build the menu by looping in result.
 *
 * Sadly, some DB abstraction layer use proxy class to fetch data from the database. This service require an
 * implementation of the interface ProxyDetectorInterface to fetch the real object's instance.
 *
 * Content instance linked to Item instance are also fetched during the main query `TopItemByLocationQuery\.
 * To avoid multiple queries, all Content ids are extracted to fetch all required instances in a single query via
 * `PublishedContentFromIdsQuery`, then redispatched to each item.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class MenuGenerator
{
    /**
     * @var array<string, array<array{0:string, 1:Item}>>
     */
    private array $cache = [];

    /**
     * @param array<string> $preloadItemsLocations
     */
    public function __construct(
        private readonly ItemLoader $itemLoader,
        private readonly ContentLoader $contentLoader,
        private readonly ?ProxyDetectorInterface $proxyDetector = null,
        private readonly array $preloadItemsLocations = [],
        private readonly ?TranslationManagerInterface $translationManager = null,
    ) {
    }

    private function fetch(string $location): void
    {
        $itemsStacks = [];
        $contentsStacks = [];

        $itemsSorting = function (iterable $items) use (&$itemsStacks, &$contentsStacks): void {
            foreach ($items as $item) {
                if (null !== $this->proxyDetector && null !== ($content = $item->getContent())) {
                    /** @var Promise<ObjectInterface, mixed, mixed> $contentFetchedPromise */
                    $contentFetchedPromise = new Promise(
                        static function ($content) use (&$contentsStacks, $item): void {
                            $contentsStacks[$content->getId()] = $item;
                        }
                    );

                    $this->proxyDetector->checkIfInstanceBehindProxy(
                        $content,
                        $contentFetchedPromise
                    );
                }

                if (!($parent = $item->getParent())) {
                    $itemsStacks['top'][] = $item;

                    continue;
                }

                $itemsStacks[$parent->getId()][] = $item;
            }

            if (empty($contentsStacks)) {
                return;
            }

            /** @var Promise<iterable<Content>, mixed, mixed> $fetchedPromise */
            $fetchedPromise = new Promise(
                static function (iterable $contents) use (&$contentsStacks): void {
                    foreach ($contents as $content) {
                        $cId = $content->getId();

                        if (!isset($contentsStacks[$cId])) {
                            continue;
                        }

                        $contentsStacks[$cId]->setContent($content);
                    }
                }
            );

            $this->contentLoader->query(
                new PublishedContentFromIdsQuery(array_keys($contentsStacks)),
                $fetchedPromise
            );
        };

        /** @var Promise<iterable<Item>, mixed, mixed> $promise */
        $promise = new Promise($itemsSorting);

        $locations = $this->preloadItemsLocations;
        $locations[] = $location;

        $locations = array_diff(array_unique($locations), array_keys($this->cache));

        $this->translationManager?->deferringTranslationsLoading();
        $this->itemLoader->query(new TopItemByLocationQuery($locations), $promise);
        $this->translationManager?->stopDeferringTranslationsLoading();

        if (empty($itemsStacks['top'])) {
            return;
        }

        $generator = static function () use ($itemsStacks): iterable {
            foreach ($itemsStacks['top'] as $element) {
                $haveChildren = !empty($itemsStacks[$id = $element->getId()]);

                if ($haveChildren) {
                    yield 'parent' => $element;
                    foreach ($itemsStacks[$id] as $child) {
                        yield $id => $child;
                    }
                } else {
                    yield 'top' => $element;
                }
            }
        };

        /**
         * @var string $key
         * @var Item $item
         */
        foreach ($generator() as $key => $item) {
            $this->cache[$item->getLocation()][] = [$key, $item];
        }
    }

    /**
     * @return iterable<Item>
     */
    public function extract(string $location): iterable
    {
        if (!isset($this->cache[$location])) {
            $this->fetch($location);
        }

        foreach ($this->cache[$location] ?? [] as $item) {
            yield $item[0] => $item[1];
        }

        return $this;
    }
}
