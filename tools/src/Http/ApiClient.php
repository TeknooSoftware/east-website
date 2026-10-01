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

namespace Teknoo\East\Website\Tools\Http;

use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Config\Connection;

use function basename;
use function is_string;
use function parse_str;
use function parse_url;
use function sprintf;
use function str_contains;
use function strtolower;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

/**
 * Client of the remote API: authenticates the requests, replays once a request rejected with a 401 after a new
 * login, follows explicitly the redirection of the creations and converts HTTP errors to exceptions.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ApiClient
{
    public function __construct(
        private readonly Transport $transport,
        private readonly Authenticator $authenticator,
    ) {
    }

    /**
     * Sends a request without checking the status of the response.
     */
    public function send(Connection $connection, ApiRequest $request, bool $authRequired = true): ApiResponse
    {
        $bearer = $this->authenticator->bearer($connection);
        if (null === $bearer && $authRequired && !$connection->anonymous) {
            throw new ApiException(
                'No credentials available: login with "website:auth:login", or set EAST_WEBSITE_TOKEN, or '
                . 'EAST_WEBSITE_USERNAME with EAST_WEBSITE_API_KEY',
                ErrorKind::Auth,
                0,
                [],
                ['hint' => Authenticator::USERNAME_HINT],
            );
        }

        $response = $this->transport->send($connection, $request, $bearer);
        if (401 === $response->status && null !== $bearer && $this->authenticator->canRelogin($connection)) {
            $response = $this->transport->send($connection, $request, $this->authenticator->login($connection)->token);
        }

        return $response;
    }

    /**
     * Sends a request and throws an exception when the API does not answer with a success.
     */
    public function call(Connection $connection, ApiRequest $request, bool $authRequired = true): ApiResponse
    {
        $response = $this->send($connection, $request, $authRequired);
        if (!$response->isSuccess()) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }

    /**
     * Sends a creation. The API answers with a redirection to the created object, without the object in the body:
     * it is fetched with an explicit GET when $fetch is true. When the object exists but can not be fetched, the
     * result is a synthetic document with a warning and not an error, to not push a caller to create a duplicate.
     */
    public function create(
        Connection $connection,
        ApiRequest $request,
        bool $fetch = true,
        bool $authRequired = true,
    ): ApiResponse {
        $response = $this->send($connection, $request, $authRequired);
        if ($response->isSuccess()) {
            return $response;
        }

        if (!$response->isRedirect()) {
            throw ApiException::fromResponse($response);
        }

        $location = $response->location();
        $path = null !== $location ? $connection->pathFromLocation($location) : null;
        if (null === $location || null === $path) {
            throw new ApiException(
                sprintf(
                    'Unexpected redirection to "%s", is the API protected by a session firewall instead of a JWT?',
                    $location ?? '',
                ),
                str_contains(strtolower($location ?? ''), 'login') ? ErrorKind::Auth : ErrorKind::Server,
                $response->status,
            );
        }

        if (!$fetch) {
            return $this->synthetic($location, null);
        }

        try {
            return $this->call($connection, ApiRequest::follow($path, $request->query()), $authRequired);
        } catch (ApiException $error) {
            return $this->synthetic(
                $location,
                sprintf('Created, but the object can not be fetched: %s', $error->getMessage()),
            );
        }
    }

    private function synthetic(string $location, ?string $warning): ApiResponse
    {
        $id = self::idFromLocation($location);
        $meta = ['error' => false, 'id' => $id, 'location' => $location];
        if (null !== $warning) {
            $meta['warning'] = $warning;
        }

        $body = ['meta' => $meta, 'data' => ['id' => $id]];

        return new ApiResponse(200, [], Json::encode($body), $body);
    }

    private static function idFromLocation(string $location): ?string
    {
        $query = parse_url($location, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            if (is_string($params['id'] ?? null) && '' !== $params['id']) {
                return $params['id'];
            }
        }

        $path = parse_url($location, PHP_URL_PATH);
        $id = is_string($path) ? basename($path) : '';

        return '' !== $id ? $id : null;
    }
}
