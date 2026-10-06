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

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Version;

use function fclose;
use function is_array;
use function is_resource;

/**
 * Low level transport of the CLI. It never follows redirections and never throws on HTTP errors, and sends the
 * bearer only to the configured server. The HTTP client is injected, so tests use a MockHttpClient.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Transport
{
    private const int MAX_DURATION = 120;

    public function __construct(
        private readonly HttpClientInterface $http,
    ) {
    }

    public function send(Connection $connection, ApiRequest $request, ?string $bearer): ApiResponse
    {
        $url = $request->fromOrigin()
            ? $connection->originUrl($request->path(), $request->query())
            : $connection->url($request->path(), $request->query());

        $built = $request->httpOptions();
        $headers = $built['headers'] + [
            'Accept' => 'application/json',
            'User-Agent' => 'east-website-cli/' . Version::VERSION,
        ];

        if (null !== $bearer) {
            $headers['Authorization'] = 'Bearer ' . $bearer;
        }

        $options = [
            'headers' => $headers,
            'max_redirects' => 0,
            'timeout' => (float) $connection->timeout,
            'max_duration' => (float) self::MAX_DURATION,
            'verify_peer' => !$connection->insecure,
            'verify_host' => !$connection->insecure,
        ];

        if (isset($built['body'])) {
            $options['body'] = $built['body'];
        }

        try {
            $response = $this->http->request($request->method(), $url, $options);
            $status = $response->getStatusCode();
            $responseHeaders = $response->getHeaders(false);
            $raw = $response->getContent(false);
        } catch (TransportExceptionInterface $error) {
            throw new ApiException(
                'Unable to reach the server: ' . $error->getMessage(),
                ErrorKind::Transport,
                0,
                [],
                [],
                $error,
            );
        } finally {
            $this->closeStreams($built['body'] ?? null);
        }

        return new ApiResponse($status, $responseHeaders, $raw, Json::decode($raw));
    }

    private function closeStreams(mixed $body): void
    {
        if (!is_array($body)) {
            return;
        }

        foreach ($body as $part) {
            if (is_resource($part)) {
                fclose($part);
            }
        }
    }
}
