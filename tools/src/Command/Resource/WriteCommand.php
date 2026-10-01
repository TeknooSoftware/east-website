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

namespace Teknoo\East\Website\Tools\Command\Resource;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Runtime;

/**
 * Base of the creation and of the update. The API ignores the blocks of a content sent with its creation or with
 * a change of type, so they are sent with a second request when needed.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class WriteCommand extends ResourceCommand
{
    private const string PLACEHOLDER_ID = 'ID_FROM_STEP_1';

    public function __construct(
        Runtime $runtime,
        ResourceDefinition $definition,
        protected readonly PayloadBuilder $builder = new PayloadBuilder(),
    ) {
        parent::__construct($runtime, $definition);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->builder->configure($this, $this->definition);
        $this->addDryRunOption();
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    abstract protected function firstRequest(
        Connection $connection,
        array $params,
        array $query,
        Payload $payload,
        bool $twoSteps,
    ): ApiRequest;

    abstract protected function isCreation(): bool;

    abstract protected function sendFirst(Connection $connection, ApiRequest $request): ApiResponse;

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $payload = $this->builder->build($this->definition, $input);
        $params = $this->params($input);
        $query = $this->localeQuery($input);
        $twoSteps = $payload->needsTwoSteps($this->isCreation());

        $first = $this->firstRequest($connection, $params, $query, $payload, $twoSteps);
        if (!$twoSteps) {
            if ($this->isDryRun($input)) {
                $this->dryRun($input, $output, $connection, [$first]);

                return;
            }

            $this->emit($input, $output, $this->sendFirst($connection, $first));

            return;
        }

        $second = fn (string $id): ApiRequest => ApiRequest::json(
            'PUT',
            $connection->endpoints->admin($this->definition->itemPath(), $params + ['id' => $id]),
            $payload->second(),
            $query,
        );

        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$first, $second(self::PLACEHOLDER_ID)]);

            return;
        }

        $response = $this->sendFirst($connection, $first);
        $id = $params['id'] ?? $response->id();
        if (null === $id) {
            throw new ApiException(
                'The first request succeeded but the id of the object is unknown: the blocks have not been sent',
                ErrorKind::Server,
                $response->status,
            );
        }

        try {
            $response = $this->runtime->client->call($connection, $second($id));
        } catch (ApiException $error) {
            throw $error->withExtra(['partial' => ['id' => $id, 'failedStep' => 2, 'appliedStep' => 1]]);
        }

        $this->emit($input, $output, $response);
    }
}
