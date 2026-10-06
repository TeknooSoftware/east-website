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

namespace Teknoo\East\Website\Tools\Resource;

use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiClient;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\Payload;

/**
 * Requests of the admin API for a resource, shared by the commands and by the interactive mode. The API ignores
 * the blocks of a content sent with its creation or with a change of type, so they are sent with a second request
 * when needed.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ResourceGateway
{
    public function __construct(
        private readonly ApiClient $client,
    ) {
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    public function listRequest(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query = [],
    ): ApiRequest {
        return ApiRequest::get($connection->endpoints->admin($definition->listPath, $params), $query);
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    public function getRequest(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query = [],
    ): ApiRequest {
        return ApiRequest::get($connection->endpoints->admin($definition->itemPath(), $params), $query);
    }

    /**
     * @param array<string, string> $params
     */
    public function deleteRequest(Connection $connection, ResourceDefinition $definition, array $params): ApiRequest
    {
        return ApiRequest::delete($connection->endpoints->admin($definition->deletePath(), $params));
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    public function plan(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query,
        Payload $payload,
        bool $creation,
    ): WritePlan {
        $twoSteps = $payload->needsTwoSteps($creation);

        $first = ApiRequest::json(
            $creation ? 'POST' : 'PUT',
            $connection->endpoints->admin($creation ? $definition->createPath() : $definition->itemPath(), $params),
            $twoSteps ? $payload->first() : $payload->body(),
            $query,
        );

        $second = null;
        if ($twoSteps) {
            $second = static fn (string $id): ApiRequest => ApiRequest::json(
                'PUT',
                $connection->endpoints->admin($definition->itemPath(), $params + ['id' => $id]),
                $payload->second(),
                $query,
            );
        }

        return new WritePlan($first, $second, $creation, $params['id'] ?? null);
    }

    /**
     * @throws ApiException with the extra "partial" when the first request was applied but not the second one
     */
    public function write(Connection $connection, WritePlan $plan): ApiResponse
    {
        $response = $plan->creation
            ? $this->client->create($connection, $plan->first)
            : $this->client->call($connection, $plan->first);

        if (null === $plan->second) {
            return $response;
        }

        $id = $plan->id ?? $response->id();
        if (null === $id) {
            throw new ApiException(
                'The first request succeeded but the id of the object is unknown: the blocks have not been sent',
                ErrorKind::Server,
                $response->status,
            );
        }

        try {
            return $this->client->call($connection, ($plan->second)($id));
        } catch (ApiException $error) {
            throw $error->withExtra(['partial' => ['id' => $id, 'failedStep' => 2, 'appliedStep' => 1]]);
        }
    }

    public function send(Connection $connection, ApiRequest $request): ApiResponse
    {
        return $this->client->call($connection, $request);
    }
}
