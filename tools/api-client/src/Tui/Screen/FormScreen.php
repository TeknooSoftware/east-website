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
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\Form\DocumentMapper;
use Teknoo\East\Website\Tools\Tui\Form\FormRow;
use Teknoo\East\Website\Tools\Tui\Form\FormState;
use Teknoo\East\Website\Tools\Tui\Form\RowKind;
use Teknoo\East\Website\Tools\Tui\Session;
use Teknoo\East\Website\Tools\Tui\Text\CellFormatter;
use Teknoo\East\Website\Tools\Tui\Widget\FormWidget;

use function array_key_exists;
use function count;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Form of an object: read only, to update it or to create it. Like the commands, an update sends only what was
 * changed, and the blocks of a content are sent with a second request when the API needs it. Its result is
 * CHANGED when the object was saved or deleted, so the table below reloads its page.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FormScreen implements ScreenInterface
{
    public const string CHANGED = 'changed';

    private const string TYPE_FIELD = 'type';

    private readonly DocumentMapper $mapper;

    private FormState $state;

    private FormWidget $form;

    /**
     * @var list<array{name: string, kind: string}> blocks of the type of the content
     */
    private array $blocks = [];

    private ?FormRow $picking = null;

    private bool $deleting = false;

    private bool $changed = false;

    private bool $discarding = false;

    /**
     * @param array<string, string> $params ids of the parents of the resource, and the id of the object
     * @param array<string, scalar> $query query of the requests on the object (its locale)
     * @param array<mixed> $document document of the object, empty for a creation
     * @param Payload|null $prefill values given on the command line
     */
    public function __construct(
        private readonly Session $session,
        private readonly ResourceDefinition $definition,
        private array $params,
        private readonly array $query,
        private FormMode $mode,
        private array $document = [],
        private ?Payload $prefill = null,
    ) {
        $this->mapper = new DocumentMapper($session->registry);
        $this->state = new FormState([]);
        $this->form = new FormWidget([]);

        if (FormMode::View !== $mode) {
            $this->blocks = $this->blocksOf($this->typeOf($document, $prefill));
        }

        $this->build(true);
    }

    public function title(): string
    {
        $label = $this->definition->label;
        $name = CellFormatter::label($this->document, $this->definition->labelField) ?? ($this->params['id'] ?? '');

        return match ($this->mode) {
            FormMode::View => sprintf('%s · %s', $label, $name),
            FormMode::Edit => sprintf('%s · edit %s', $label, $name),
            FormMode::Create => sprintf('%s · new', $label),
        };
    }

    public function hints(): string
    {
        if (FormMode::View !== $this->mode) {
            return 'Ctrl+S or F2 save · Tab/Shift+Tab field · Esc back';
        }

        $hints = [];
        if ($this->definition->supports(Operation::Update)) {
            $hints[] = 'e edit';
        }

        if ($this->definition->supports(Operation::Delete)) {
            $hints[] = 'd delete';
        }

        return implode(' · ', [...$hints, '↑↓ move', 'Esc back', 'q quit']);
    }

    public function widget(): AbstractWidget&FocusableInterface
    {
        return $this->form;
    }

    public function resume(mixed $result): void
    {
        if ($this->deleting) {
            $this->deleting = false;
            if (true === $result) {
                $this->delete();
            }

            return;
        }

        $row = $this->picking;
        $this->picking = null;
        if (null === $row || !is_array($result)) {
            return;
        }

        $references = [];
        foreach ($result as $item) {
            if (is_array($item) && is_string($item['id'] ?? null) && is_string($item['label'] ?? null)) {
                $references[] = ['id' => $item['id'], 'label' => $item['label']];
            }
        }

        $row->value = RowKind::Relation === $row->kind ? ($references[0] ?? null) : $references;
        $row->error = null;
        $this->discarding = false;

        if (self::TYPE_FIELD === $row->name && $this->definition->hasParts) {
            // The blocks of a content are the ones of its type
            $this->blocks = $this->blocksOf(null !== $row->value ? ['id' => $row->ids()[0] ?? ''] : null);
            $this->build(false);
            $this->form->focusRow($row->name);
        }
    }

    /**
     * (Re)builds the rows and their widget. The rows already known keep their value when they are rebuilt.
     */
    private function build(bool $reset): void
    {
        $rows = $this->mapper->rows($this->definition, $this->document, $this->mode, $this->blocks);
        if ($reset) {
            $this->state = new FormState($rows);
        } else {
            $this->state->replace($rows);
        }

        if (null !== $this->prefill && FormMode::View !== $this->mode) {
            $this->mapper->overlay($this->state, $this->prefill);
        }

        $this->form = (new FormWidget($this->state->rows(), FormMode::View === $this->mode))
            ->onSubmit($this->submit(...))
            ->onCancel($this->cancel(...))
            ->onPick($this->pick(...))
            ->onChange($this->touched(...))
            ->onAction($this->act(...));
    }

    private function touched(FormRow $row): void
    {
        $row->error = null;
        $this->discarding = false;
    }

    private function act(string $action): void
    {
        $navigator = $this->session->navigator;
        $navigator->status('');

        if (FormWidget::ACTION_QUIT === $action) {
            $navigator->quit();
        } elseif (FormWidget::ACTION_EDIT === $action && $this->definition->supports(Operation::Update)) {
            $this->mode = FormMode::Edit;
            $this->blocks = $this->blocksOf($this->typeOf($this->document, null));
            $this->build(true);
            $navigator->refresh();
        } elseif (FormWidget::ACTION_DELETE === $action && $this->definition->supports(Operation::Delete)) {
            $this->deleting = true;
            $navigator->push(new ConfirmScreen(
                $navigator,
                sprintf('%s · delete', $this->definition->label),
                sprintf(
                    'Delete the %s "%s"?',
                    $this->definition->label,
                    CellFormatter::label($this->document, $this->definition->labelField) ?? ($this->params['id'] ?? ''),
                ),
            ));
        }
    }

    private function cancel(): void
    {
        $navigator = $this->session->navigator;

        if (FormMode::View !== $this->mode && $this->state->isDirty() && !$this->discarding) {
            $this->discarding = true;
            $navigator->status('Unsaved changes: press Esc again to discard them, Ctrl+S to save', true);

            return;
        }

        $navigator->status('');
        $navigator->pop($this->changed ? self::CHANGED : null);
    }

    private function pick(FormRow $row): void
    {
        $target = null !== $row->field?->target ? $this->session->registry->resource($row->field->target) : null;
        if (null === $target) {
            return;
        }

        $picked = [];
        if (is_array($row->value)) {
            foreach (RowKind::Relation === $row->kind ? [$row->value] : $row->value as $item) {
                if (is_array($item) && is_string($item['id'] ?? null) && is_string($item['label'] ?? null)) {
                    $picked[] = ['id' => $item['id'], 'label' => $item['label']];
                }
            }
        }

        $this->picking = $row;
        $this->session->navigator->status('');
        $this->session->navigator->push(new ListScreen(
            $this->session,
            $target,
            [],
            [],
            RowKind::Relation === $row->kind ? ListMode::PickOne : ListMode::PickMany,
            $picked,
        ));
    }

    private function submit(): void
    {
        $navigator = $this->session->navigator;
        $creation = FormMode::Create === $this->mode;
        $this->discarding = false;
        $this->state->clearErrors();

        $payload = $this->state->payload($this->session->builder);
        if (null === $payload) {
            $navigator->status('Some values are not valid, nothing was sent', true);
            $this->focusFirstError();

            return;
        }

        if ([] === $payload->fields && !$payload->hasParts() && !$payload->publish) {
            $navigator->status($creation ? 'Nothing to create: fill at least one field' : 'Nothing was changed');

            return;
        }

        $gateway = $this->session->gateway;
        $plan = $gateway->plan(
            $this->session->connection,
            $this->definition,
            $this->params,
            $this->query,
            $payload,
            $creation,
        );

        try {
            $response = $navigator->busy(
                'Saving…',
                fn (): ApiResponse => $gateway->write($this->session->connection, $plan),
            );
        } catch (ApiException $error) {
            $this->failed($error);

            return;
        }

        $this->saved($response, $creation);
    }

    private function saved(ApiResponse $response, bool $creation): void
    {
        $this->changed = true;
        $this->prefill = null;

        $id = $response->id() ?? ($this->params['id'] ?? null);
        if (null !== $id) {
            $this->params['id'] = $id;
            $this->mode = FormMode::Edit;
        }

        $data = $response->data();
        if (is_array($data) && count($data) > 1) {
            // The saved object replaces the form: its values are the ones kept by the server
            $this->document = $data;
            $this->blocks = $this->blocksOf($this->typeOf($data, null));
            $this->build(true);
        } else {
            $this->state->commit();
        }

        $navigator = $this->session->navigator;
        $navigator->refresh();

        $warning = $response->meta()['warning'] ?? null;
        $navigator->status(match (true) {
            is_string($warning) && '' !== $warning => $warning,
            $creation => sprintf('The %s was created', $this->definition->label),
            default => sprintf('The %s was saved', $this->definition->label),
        });
    }

    private function failed(ApiException $error): void
    {
        $messages = [$error->getMessage()];

        $partial = $error->extra['partial'] ?? null;
        $id = is_array($partial) ? ($partial['id'] ?? null) : null;
        if (is_string($id)) {
            // The object exists (or its fields are saved) but not its blocks: the form goes on as an update of
            // this object, a new submit must not create another one
            $this->changed = true;
            $this->params['id'] = $id;
            $this->mode = FormMode::Edit;
            $this->state->commit(false);
            $this->prefill = null;
            $messages = ['The object was saved, but not its blocks: ' . $error->getMessage()];
        }

        foreach ($this->state->applyErrors($error->fields) as $unmapped) {
            $messages[] = $unmapped;
        }

        $navigator = $this->session->navigator;
        if (is_string($id)) {
            $navigator->refresh();
        }

        $navigator->status(implode(' — ', $messages), true);
        $this->focusFirstError();
    }

    private function focusFirstError(): void
    {
        foreach ($this->state->rows() as $row) {
            if (null !== $row->error) {
                $this->form->focusRow($row->name);

                return;
            }
        }

        $this->form->invalidate();
    }

    private function delete(): void
    {
        $navigator = $this->session->navigator;

        try {
            $navigator->busy('Deleting…', fn (): ApiResponse => $this->session->gateway->send(
                $this->session->connection,
                $this->session->gateway->deleteRequest($this->session->connection, $this->definition, $this->params),
            ));
        } catch (ApiException $error) {
            $navigator->status($error->getMessage(), true);

            return;
        }

        $navigator->pop(self::CHANGED);
        $navigator->status(sprintf('The %s was deleted', $this->definition->label));
    }

    /**
     * The type of a content, from its document or from the command line.
     *
     * @param array<mixed> $document
     * @return array<mixed>|null the document of the type (maybe only its id), null when the content has no type
     */
    private function typeOf(array $document, ?Payload $prefill): ?array
    {
        if (!$this->definition->hasParts) {
            return null;
        }

        if (null !== $prefill && array_key_exists(self::TYPE_FIELD, $prefill->fields)) {
            $id = $prefill->fields[self::TYPE_FIELD];

            return is_string($id) && '' !== $id ? ['id' => $id] : null;
        }

        $type = $document[self::TYPE_FIELD] ?? null;
        if (is_string($type) && '' !== $type) {
            return ['id' => $type];
        }

        return is_array($type) ? $type : null;
    }

    /**
     * Blocks of a type. Its document is fetched when the document of the content does not embed its blocks.
     *
     * @param array<mixed>|null $type
     * @return list<array{name: string, kind: string}>
     */
    private function blocksOf(?array $type): array
    {
        if (null === $type) {
            return [];
        }

        if (is_array($type['blocks'] ?? null)) {
            return $this->mapper->blocks($type);
        }

        $id = $type['id'] ?? null;
        $definition = $this->session->registry->resource(self::TYPE_FIELD);
        if (!is_string($id) || '' === $id || null === $definition) {
            return [];
        }

        $navigator = $this->session->navigator;
        try {
            $response = $navigator->busy('Loading the type…', fn (): ApiResponse => $this->session->gateway->send(
                $this->session->connection,
                $this->session->gateway->getRequest($this->session->connection, $definition, ['id' => $id]),
            ));
        } catch (ApiException $error) {
            $navigator->status('The blocks of the type can not be loaded: ' . $error->getMessage(), true);

            return [];
        }

        $data = $response->data();

        return is_array($data) ? $this->mapper->blocks($data) : [];
    }
}
