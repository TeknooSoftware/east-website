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

namespace Teknoo\East\Website\Service;

use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\East\Translation\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Environment;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Loader\ItemLoader;
use Teknoo\East\Website\Object\Item;
use Teknoo\East\Website\Query\Content\PublishedContentFromIdsQuery;
use Teknoo\East\Website\Query\Item\TopItemByLocationQuery;

use function array_diff;
use function array_keys;
use function array_unique;
use function in_array;

/**
 * Service to generate a menu from persisted item and loader. It will use the query TopItemByLocationQuery to extract
 * an ordered list of items, by items's order and items's hierarchie and build a PHP Generator to call in a template
 * to build the menu by looping in result.
 *
 * Content instance linked to Item instance are also fetched during the main query `TopItemByLocationQuery\.
 * To avoid multiple queries, all Content ids are extracted to fetch all required instances in a single query via
 * `PublishedContentFromIdsQuery`, then redispatched to each item.
 *
 * The generator is scoped to an environment (`default` by default, see `withEnvironment()`): only items of the
 * environment's chain are fetched, and items linked to a content out of this chain are skipped (the content would
 * not be available).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class MenuGenerator
{
    /**
     * @var array<string, array<array{0:string, 1:Item}>>
     */
    private array $cache = [];

    private ?Environment $environment = null;

    /**
     * @param array<string> $preloadItemsLocations
     */
    public function __construct(
        private readonly ItemLoader $itemLoader,
        private readonly array $preloadItemsLocations = [],
        private readonly ?TranslationManagerInterface $translationManager = null,
    ) {
    }

    public function __clone()
    {
        $this->cache = [];
    }

    /*
     * Return a copy of this generator, with an empty cache, scoped to the environment
     */
    public function withEnvironment(Environment $environment): static
    {
        $that = clone $this;
        $that->environment = $environment;

        return $that;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment ?? Environment::default();
    }

    private function fetch(string $location): void
    {
        $itemsStacks = [];
        $environment = $this->getEnvironment();
        $chain = $environment->getChain();

        $itemsSorting = function (iterable $items) use (&$itemsStacks, $chain): void {
            /** @var Item[] $items */
            foreach ($items as $item) {
                $content = $item->getContent();
                if (
                    null !== $content
                    && !in_array($content->getEnvironment()->getName(), $chain, true)
                ) {
                    continue;
                }

                if (!($parent = $item->getParent())) {
                    $itemsStacks['top'][] = $item;

                    continue;
                }

                $itemsStacks[$parent->getId()][] = $item;
            }
        };

        /** @var Promise<iterable<Item>, mixed, mixed> $promise */
        $promise = new Promise($itemsSorting);

        $locations = $this->preloadItemsLocations;
        $locations[] = $location;

        $locations = array_diff(array_unique($locations), array_keys($this->cache));

        $this->translationManager?->deferringTranslationsLoading();
        $this->itemLoader->query(new TopItemByLocationQuery($locations, $environment), $promise);
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
