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

namespace Teknoo\East\Website\Tools\Config;

use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\Endpoints;

use function http_build_query;
use function in_array;
use function is_string;
use function parse_url;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;

use const PHP_QUERY_RFC3986;

/**
 * Immutable description of the remote API to reach: base URL, endpoints, credentials and transport options. It is
 * stored in the configuration file by the login, and read from it by all the other commands.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Connection
{
    private const array LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]'];

    public const int DEFAULT_TIMEOUT = 30;

    /**
     * @param string $configFile path of the configuration file of this connection
     * @param bool $configured true when the connection was read from its configuration file
     */
    public function __construct(
        public readonly string $baseUrl,
        public readonly Endpoints $endpoints,
        public readonly Credentials $credentials,
        public readonly string $configFile = '',
        public readonly bool $configured = false,
        public readonly bool $insecure = false,
        public readonly bool $allowHttp = false,
        public readonly bool $anonymous = false,
        public readonly int $timeout = self::DEFAULT_TIMEOUT,
        public readonly string $usernameField = 'username',
        public readonly string $tokenField = 'token',
    ) {
    }

    public function withCredentials(Credentials $credentials): self
    {
        return new self(
            $this->baseUrl,
            $this->endpoints,
            $credentials,
            $this->configFile,
            $this->configured,
            $this->insecure,
            $this->allowHttp,
            $this->anonymous,
            $this->timeout,
            $this->usernameField,
            $this->tokenField,
        );
    }

    /**
     * Sends no JWT (option --anonymous of the commands of the public API), without changing the configuration.
     */
    public function asAnonymous(): self
    {
        return new self(
            $this->baseUrl,
            $this->endpoints,
            $this->credentials,
            $this->configFile,
            $this->configured,
            $this->insecure,
            $this->allowHttp,
            true,
            $this->timeout,
            $this->usernameField,
            $this->tokenField,
        );
    }

    /**
     * @return array{scheme: string, host: string, port: int|null, path: string}
     */
    private function parts(): array
    {
        if ('' === $this->baseUrl) {
            throw ApiException::usage(
                'No base URL configured, login first with website:auth:login --url=<base url>'
            );
        }

        $parts = parse_url($this->baseUrl);
        $scheme = is_string($parts['scheme'] ?? null) ? strtolower($parts['scheme']) : '';
        $host = is_string($parts['host'] ?? null) ? strtolower($parts['host']) : '';
        if (!in_array($scheme, ['http', 'https'], true) || '' === $host) {
            throw ApiException::usage('The base URL must be an absolute http(s) URL, like https://example.com');
        }

        if ('http' === $scheme && !$this->allowHttp && !$this->isLoopback($host)) {
            throw ApiException::usage(
                'Refusing to send credentials over plain http to a remote host, use https or the --allow-http option'
            );
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $parts['port'] ?? null,
            'path' => rtrim($parts['path'] ?? '', '/'),
        ];
    }

    private function isLoopback(string $host): bool
    {
        return in_array($host, self::LOOPBACK_HOSTS, true) || str_ends_with($host, '.localhost');
    }

    public function origin(): string
    {
        $parts = $this->parts();

        return $parts['scheme'] . '://' . $parts['host'] . (null !== $parts['port'] ? ':' . $parts['port'] : '');
    }

    /**
     * @param array<string, scalar> $query
     */
    public function url(string $path, array $query = []): string
    {
        $this->parts();

        return rtrim($this->baseUrl, '/') . $path . self::queryString($query);
    }

    /**
     * @param array<string, scalar> $query
     */
    public function originUrl(string $path, array $query = []): string
    {
        return $this->origin() . $path . self::queryString($query, str_contains($path, '?') ? '&' : '?');
    }

    /**
     * @param array<string, scalar> $query
     */
    public function displayUrl(string $path, array $query, bool $fromOrigin): string
    {
        if ('' === $this->baseUrl) {
            return '<base-url>' . $path . self::queryString($query);
        }

        return $fromOrigin ? $this->originUrl($path, $query) : $this->url($path, $query);
    }

    /**
     * @param array<string, scalar> $query
     */
    private static function queryString(array $query, string $separator = '?'): string
    {
        return [] === $query ? '' : $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Converts the Location header of an API redirection to a path relative to the origin. Returns null when the
     * redirection does not target the same origin, or does not stay under the API prefixes of this connection.
     */
    public function pathFromLocation(string $location): ?string
    {
        $parts = parse_url($location);
        if (false === $parts) {
            return null;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            $absolute = ($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '') . $port;
            if (strtolower($absolute) !== $this->origin()) {
                return null;
            }
        }

        $path = $parts['path'] ?? '';
        if ('' === $path || '/' !== $path[0]) {
            return null;
        }

        $basePath = $this->parts()['path'];
        foreach ([$this->endpoints->apiPrefix(), $this->endpoints->adminPrefix()] as $prefix) {
            $prefix = $basePath . $prefix;
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
            }
        }

        return null;
    }
}
