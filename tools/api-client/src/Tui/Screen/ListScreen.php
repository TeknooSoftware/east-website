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

namespace Teknoo\East\Website\Tools\Tui\Screen;

use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\Session;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;
use Teknoo\East\Website\Tools\Tui\Widget\TableAction;
use Teknoo\East\Website\Tools\Tui\Widget\TableWidget;

use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function max;
use function sprintf;

/**
 * Table of the objects of a resource, page by page (the size of the pages is fixed by the server). It is used to
 * browse and manage the objects, or to choose the objects of a relation of a form: its result is then the list of
 * the chosen {id, label}, an empty list for "none", or null when the choice was cancelled.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ListScreen implements ScreenInterface
{
    private readonly TableWidget $table;

    /**
     * @var list<array<mixed>>
     */
    private array $items = [];

    private int $page = 1;

    private int $totalPages = 1;

    /**
     * @var array<string, string> labels of the chosen objects, by their id
     */
    private array $picked = [];

    private ?string $deleting = null;

    /**
     * @param array<string, string> $params ids of the parents of the resource
     * @param array<string, scalar> $query query of the list (page, order, direction, locale)
     * @param list<array{id: string, label: string}> $picked objects already chosen
     * @param ApiResponse|null $first first page when it is already fetched
     */
    public function __construct(
        private readonly Session $session,
        private readonly ResourceDefinition $definition,
        private readonly array $params = [],
        private array $query = [],
        private readonly ListMode $mode = ListMode::Browse,
        array $picked = [],
        ?ApiResponse $first = null,
    ) {
        $this->table = (new TableWidget())
            ->setEmptyMessage(sprintf('No %s', $definition->label))
            ->onAction($this->act(...));

        foreach ($picked as $item) {
            $this->picked[$item['id']] = $item['label'];
        }

        $page = $this->query['page'] ?? 1;
        if (null !== $first) {
            $this->show($first, is_numeric($page) ? (int) $page : 1);
        } else {
            $this->load(is_numeric($page) ? (int) $page : 1);
        }
    }

    public function title(): string
    {
        return match ($this->mode) {
            ListMode::Browse => sprintf('%s · list', $this->definition->label),
            ListMode::PickOne => sprintf('Choose a %s', $this->definition->label),
            ListMode::PickMany => sprintf('Choose the %s objects', $this->definition->name),
        };
    }

    public function hints(): string
    {
        if (ListMode::PickOne === $this->mode) {
            return 'Enter choose · Backspace none · ←→ page · Esc cancel';
        }

        if (ListMode::PickMany === $this->mode) {
            return 'Space check · Enter confirm · Backspace none · ←→ page · Esc cancel';
        }

        // The most useful keys first: the line is cut on a narrow terminal
        $hints = ['Enter view'];
        if ($this->definition->supports(Operation::Update)) {
            $hints[] = 'e edit';
        }

        if ($this->definition->supports(Operation::Create)) {
            $hints[] = 'n new';
        }

        if ($this->definition->supports(Operation::Delete)) {
            $hints[] = 'd delete';
        }

        return implode(' · ', [...$hints, '←→ page', 'r reload', 'Esc back', 'q quit']);
    }

    public function widget(): AbstractWidget&FocusableInterface
    {
        return $this->table;
    }

    public function resume(mixed $result): void
    {
        if (null !== $this->deleting) {
            $id = $this->deleting;
            $this->deleting = null;
            if (true === $result) {
                $this->delete($id);
            }

            return;
        }

        if (FormScreen::CHANGED === $result) {
            $this->load($this->page);
        }
    }

    private function act(TableAction $action, ?int $index): void
    {
        $navigator = $this->session->navigator;
        $navigator->status('');

        $item = null !== $index ? ($this->items[$index] ?? null) : null;
        $id = null !== $item ? $this->id($item) : null;

        switch ($action) {
            case TableAction::Back:
                $navigator->pop();

                return;
            case TableAction::Quit:
                if (ListMode::Browse === $this->mode) {
                    $navigator->quit();
                }

                return;
            case TableAction::PreviousPage:
                if ($this->page > 1) {
                    $this->load($this->page - 1);
                }

                return;
            case TableAction::NextPage:
                if ($this->page < $this->totalPages) {
                    $this->load($this->page + 1);
                }

                return;
            case TableAction::Reload:
                $this->load($this->page);

                return;
            default:
                break;
        }

        match ($this->mode) {
            ListMode::Browse => $this->manage($action, $item, $id),
            ListMode::PickOne => $this->pickOne($action, $item, $id),
            ListMode::PickMany => $this->pickMany($action, $item, $id),
        };
    }

    /**
     * @param array<mixed>|null $item
     */
    private function manage(TableAction $action, ?array $item, ?string $id): void
    {
        $navigator = $this->session->navigator;
        $definition = $this->definition;

        if (TableAction::Create === $action && $definition->supports(Operation::Create)) {
            $navigator->push(new FormScreen(
                $this->session,
                $definition,
                $this->params,
                $this->itemQuery(),
                FormMode::Create,
            ));

            return;
        }

        if (null === $item || null === $id) {
            return;
        }

        if (TableAction::Delete === $action && $definition->supports(Operation::Delete)) {
            $this->deleting = $id;
            $navigator->push(new ConfirmScreen(
                $navigator,
                sprintf('%s · delete', $definition->label),
                sprintf('Delete the %s "%s"?', $definition->label, $this->label($item, $id)),
            ));

            return;
        }

        $mode = match (true) {
            TableAction::Open === $action => FormMode::View,
            TableAction::Edit === $action && $definition->supports(Operation::Update) => FormMode::Edit,
            default => null,
        };

        if (null === $mode) {
            return;
        }

        $params = $this->params + ['id' => $id];
        try {
            $document = $navigator->busy('Loading…', fn (): ApiResponse => $this->session->gateway->send(
                $this->session->connection,
                $this->session->gateway->getRequest(
                    $this->session->connection,
                    $definition,
                    $params,
                    $this->itemQuery(),
                ),
            ));
        } catch (ApiException $error) {
            $navigator->status($error->getMessage(), true);

            return;
        }

        $data = $document->data();
        $navigator->push(new FormScreen(
            $this->session,
            $definition,
            $params,
            $this->itemQuery(),
            $mode,
            is_array($data) ? $data : [],
        ));
    }

    /**
     * @param array<mixed>|null $item
     */
    private function pickOne(TableAction $action, ?array $item, ?string $id): void
    {
        if (TableAction::Clear === $action) {
            $this->session->navigator->pop([]);
        } elseif (TableAction::Open === $action && null !== $item && null !== $id) {
            $this->session->navigator->pop([['id' => $id, 'label' => $this->label($item, $id)]]);
        }
    }

    /**
     * @param array<mixed>|null $item
     */
    private function pickMany(TableAction $action, ?array $item, ?string $id): void
    {
        if (TableAction::Open === $action) {
            $picked = [];
            foreach ($this->picked as $pickedId => $label) {
                $picked[] = ['id' => (string) $pickedId, 'label' => $label];
            }

            $this->session->navigator->pop($picked);

            return;
        }

        if (TableAction::Clear === $action) {
            $this->picked = [];
        } elseif (TableAction::Toggle === $action && null !== $item && null !== $id) {
            if (array_key_exists($id, $this->picked)) {
                unset($this->picked[$id]);
            } else {
                $this->picked[$id] = $this->label($item, $id);
            }
        }

        $this->marks();
    }

    private function delete(string $id): void
    {
        $navigator = $this->session->navigator;

        try {
            $navigator->busy('Deleting…', fn (): ApiResponse => $this->session->gateway->send(
                $this->session->connection,
                $this->session->gateway->deleteRequest(
                    $this->session->connection,
                    $this->definition,
                    $this->params + ['id' => $id],
                ),
            ));
        } catch (ApiException $error) {
            $navigator->status($error->getMessage(), true);

            return;
        }

        $this->load($this->page);
        $navigator->status(sprintf('The %s "%s" was deleted', $this->definition->label, $id));
    }

    private function load(int $page): void
    {
        $navigator = $this->session->navigator;
        $query = ['page' => $page] + $this->query;

        try {
            $response = $navigator->busy('Loading…', fn (): ApiResponse => $this->session->gateway->send(
                $this->session->connection,
                $this->session->gateway->listRequest(
                    $this->session->connection,
                    $this->definition,
                    $this->params,
                    $query,
                ),
            ));
        } catch (ApiException $error) {
            $navigator->status($error->getMessage(), true);

            return;
        }

        $this->query = $query;
        $this->show($response, $page);
    }

    private function show(ApiResponse $response, int $requestedPage): void
    {
        $this->items = [];
        $data = $response->data();
        foreach (is_array($data) ? $data : [] as $item) {
            if (is_array($item)) {
                $this->items[] = $item;
            }
        }

        $meta = $response->meta();
        $this->page = max(1, $this->number($meta['page'] ?? null, $requestedPage));
        $this->totalPages = max($this->page, $this->number($meta['totalPages'] ?? null, $this->page));
        $total = $this->number($meta['count'] ?? null, count($this->items));

        $columns = [] !== $this->definition->listColumns
            ? $this->definition->listColumns
            : array_keys($this->items[0] ?? []);

        $names = [];
        foreach ($columns as $column) {
            $names[] = (string) $column;
        }

        $rows = [];
        foreach ($this->items as $item) {
            $row = [];
            foreach ($names as $column) {
                $row[$column] = CellFormatter::cell($item[$column] ?? null);
            }

            $rows[] = $row;
        }

        $this->table->setRows($names, $rows);
        $this->table->setFooter(sprintf('page %d/%d · %d item(s)', $this->page, $this->totalPages, $total));
        $this->marks();
    }

    private function marks(): void
    {
        if (ListMode::PickMany !== $this->mode) {
            return;
        }

        $marked = [];
        foreach ($this->items as $index => $item) {
            $id = $this->id($item);
            if (null !== $id && array_key_exists($id, $this->picked)) {
                $marked[] = $index;
            }
        }

        $this->table->setMarked($marked);
    }

    /**
     * @return array<string, scalar> query of the requests on an object: only its locale
     */
    private function itemQuery(): array
    {
        return array_intersect_key($this->query, ['locale' => true]);
    }

    /**
     * @param array<mixed> $item
     */
    private function id(array $item): ?string
    {
        $id = $item['id'] ?? null;

        return is_scalar($id) && !is_bool($id) && '' !== (string) $id ? (string) $id : null;
    }

    /**
     * @param array<mixed> $item
     */
    private function label(array $item, string $id): string
    {
        return CellFormatter::label($item, $this->definition->labelField) ?? $id;
    }

    private function number(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }
}
