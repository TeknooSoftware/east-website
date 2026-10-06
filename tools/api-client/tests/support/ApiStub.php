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

namespace Teknoo\Tests\East\Website\Tools\Support;

use LogicException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiClient;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\Json;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Warnings;
use Teknoo\East\Website\Tools\Resource\ResourceGateway;

use function array_shift;
use function is_string;
use function parse_url;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

/**
 * A real client of the API on a MockHttpClient, with an authenticated connection: the responses are queued in the
 * order of the requests, each request is recorded. For the tests of the classes working below the commands.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ApiStub
{
    public const string URL = 'https://site.test';

    /**
     * @var list<array{method: string, path: string, query: string, body: mixed}>
     */
    public array $requests = [];

    /**
     * @var list<MockResponse>
     */
    private array $responses = [];

    public readonly ApiClient $client;

    public readonly ResourceGateway $gateway;

    public readonly Connection $connection;

    public function __construct()
    {
        $http = new MockHttpClient($this->handle(...), self::URL);
        $transport = new Transport($http);
        $clock = new FixedClock();

        $this->client = new ApiClient($transport, new Authenticator($transport, $clock, new Warnings()));
        $this->gateway = new ResourceGateway($this->client);
        $this->connection = new Connection(
            self::URL,
            new Endpoints(),
            new Credentials('key:me@site.test', null, 'jwt', $clock->now()->getTimestamp() + 3600),
            configFile: '/work/east-website.json',
            configured: true,
        );
    }

    /**
     * @param array<mixed> $body
     * @param array<string, string> $headers
     */
    public function queue(int $status, array $body = [], array $headers = []): self
    {
        $responseHeaders = [];
        foreach ($headers as $name => $value) {
            $responseHeaders[] = $name . ': ' . $value;
        }

        $this->responses[] = new MockResponse(
            [] === $body ? '' : Json::encode($body),
            ['http_code' => $status, 'response_headers' => $responseHeaders],
        );

        return $this;
    }

    /**
     * @return list<string> "<METHOD> <path>[?<query>]" of each request, in their order
     */
    public function calls(): array
    {
        $calls = [];
        foreach ($this->requests as $request) {
            $calls[] = $request['method'] . ' ' . $request['path'] . ('' !== $request['query'] ? '?' . $request['query'] : '');
        }

        return $calls;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function handle(string $method, string $url, array $options): MockResponse
    {
        $body = $options['body'] ?? null;

        $this->requests[] = [
            'method' => $method,
            'path' => (string) parse_url($url, PHP_URL_PATH),
            'query' => (string) parse_url($url, PHP_URL_QUERY),
            'body' => is_string($body) && '' !== $body ? Json::decode($body) : null,
        ];

        $response = array_shift($this->responses);
        if (null === $response) {
            throw new LogicException('Unexpected request ' . $method . ' ' . $url);
        }

        return $response;
    }
}
