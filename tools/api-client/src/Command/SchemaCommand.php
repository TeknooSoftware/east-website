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

namespace Teknoo\East\Website\Tools\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Resource\BlockTypes;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FrontArgument;
use Teknoo\East\Website\Tools\Resource\FrontEndpoint;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Runtime;

use function array_map;
use function sprintf;

/**
 * Describes the resources of the API (commands, paths, fields, enumerations) as JSON, for agents. The description
 * of the commands and of their options is given by the built-in commands: "list --format=json" and "help
 * <command> --format=json".
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SchemaCommand extends AbstractCommand
{
    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:schema');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Describe the resources of the API, their fields and their commands, as JSON');
        $this->addArgument('resource', InputArgument::OPTIONAL, 'Name of a resource, all by default');
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $registry = $this->runtime->registry;
        $name = InputReader::argument($input, 'resource');

        $resources = [];
        foreach ($registry->resources() as $resource) {
            if (null === $name || $resource->name === $name) {
                $resources[$resource->name] = $this->describe($resource, $connection);
            }
        }

        if ([] === $resources) {
            throw ApiException::usage(sprintf('The resource "%s" does not exist', $name ?? ''));
        }

        $data = ['resources' => $resources];
        if (null === $name) {
            $data['front'] = array_map(fn (FrontEndpoint $endpoint): array => [
                'command' => 'website:front:' . $endpoint->name,
                'description' => $endpoint->description,
                'path' => $connection->endpoints->apiPrefix() . '/' . $endpoint->template,
                'arguments' => array_map(
                    static fn (FrontArgument $argument): array => [
                        'name' => $argument->name,
                        'required' => null === $argument->default,
                        'default' => $argument->default,
                    ],
                    $endpoint->arguments,
                ),
                'paginated' => $endpoint->paginated,
            ], $registry->front());
            $data['blockKinds'] = BlockTypes::INDEXES;
        }

        $this->emit($input, $output, ['meta' => ['error' => false], 'data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(ResourceDefinition $resource, Connection $connection): array
    {
        $commands = [];
        foreach ($resource->operations as $operation) {
            $commands[$operation->value] = 'website:' . $resource->name . ':' . $operation->value;
        }

        if ('media' === $resource->name) {
            $commands[Operation::Create->value] = 'website:media:create';
        }

        return [
            'label' => $resource->label,
            'commands' => $commands,
            'parents' => $resource->parents,
            'translatable' => $resource->translatable,
            'parts' => $resource->hasParts,
            'paths' => [
                'list' => $connection->endpoints->adminPrefix() . '/' . $resource->listPath,
                'item' => $connection->endpoints->adminPrefix() . '/' . $resource->itemPath(),
                'create' => $connection->endpoints->adminPrefix() . '/' . $resource->createPath(),
                'delete' => $connection->endpoints->adminPrefix() . '/' . $resource->deletePath(),
            ],
            'fields' => array_map(
                static fn (FieldDefinition $field): array => [
                    'name' => $field->name,
                    'option' => '--' . $field->optionName(),
                    'kind' => $field->kind->value,
                    'description' => $field->description,
                    'choices' => $field->choices,
                ],
                $resource->fields,
            ),
        ];
    }
}
