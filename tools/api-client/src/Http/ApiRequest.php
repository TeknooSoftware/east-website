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

use Teknoo\East\Website\Tools\Config\Connection;

use function fopen;
use function is_file;
use function is_readable;
use function sprintf;

/**
 * Immutable description of a request to the remote API. The HTTP options are rebuilt for each attempt, because an
 * uploaded stream can not be replayed.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ApiRequest
{
    /**
     * @param array<string, scalar> $query
     * @param array<mixed>|null $payload JSON payload, null for requests without body
     * @param array<string, string> $fields multipart fields
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly ?array $payload,
        private readonly array $fields,
        private readonly ?string $file,
        private readonly string $fileField,
        private readonly bool $fromOrigin,
    ) {
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function get(string $path, array $query = []): self
    {
        return new self('GET', $path, $query, null, [], null, '', false);
    }

    /**
     * @param array<string, scalar> $query
     */
    public static function delete(string $path, array $query = []): self
    {
        return new self('DELETE', $path, $query, null, [], null, '', false);
    }

    /**
     * @param array<mixed> $payload an empty payload is sent as an empty JSON object
     * @param array<string, scalar> $query
     */
    public static function json(string $method, string $path, array $payload, array $query = []): self
    {
        return new self($method, $path, $query, $payload, [], null, '', false);
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, scalar> $query
     */
    public static function multipart(
        string $path,
        array $fields,
        string $fileField,
        string $file,
        array $query = [],
    ): self {
        return new self('POST', $path, $query, null, $fields, $file, $fileField, false);
    }

    /**
     * Request to a path relative to the origin of the server, like the Location header of a redirection.
     *
     * @param array<string, scalar> $query
     */
    public static function follow(string $pathAndQuery, array $query = []): self
    {
        return new self('GET', $pathAndQuery, $query, null, [], null, '', true);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, scalar>
     */
    public function query(): array
    {
        return $this->query;
    }

    public function fromOrigin(): bool
    {
        return $this->fromOrigin;
    }

    /**
     * @return array<mixed>|null
     */
    public function payload(): ?array
    {
        return $this->payload;
    }

    /**
     * Options for the HTTP client. The JSON body is encoded here and the Content-Type is set to the bare media
     * type, because the API ignores silently a body sent with "application/json; charset=utf-8".
     *
     * @return array{headers: array<string, string>, body?: string|array<string, mixed>}
     */
    public function httpOptions(): array
    {
        if (null !== $this->payload) {
            return [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => Json::encodeObject($this->payload),
            ];
        }

        if (null !== $this->file) {
            if (!is_file($this->file) || !is_readable($this->file)) {
                throw ApiException::usage(sprintf('The file "%s" does not exist or is not readable', $this->file));
            }

            $handle = fopen($this->file, 'r');
            if (false === $handle) {
                throw ApiException::usage(sprintf('The file "%s" can not be opened', $this->file));
            }

            return ['headers' => [], 'body' => $this->fields + [$this->fileField => $handle]];
        }

        return ['headers' => []];
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(Connection $connection, bool $authenticated): array
    {
        $description = [
            'method' => $this->method,
            'url' => $connection->displayUrl($this->path, $this->query, $this->fromOrigin),
            'headers' => $this->headerDescription($authenticated),
        ];

        if (null !== $this->payload) {
            $description['body'] = [] === $this->payload ? (object) [] : $this->payload;
        }

        if (null !== $this->file) {
            $description['multipart'] = $this->fields + [$this->fileField => '@' . $this->file];
        }

        return $description;
    }

    /**
     * @return array<string, string>
     */
    private function headerDescription(bool $authenticated): array
    {
        $headers = ['Accept' => 'application/json'];
        if (null !== $this->payload) {
            $headers['Content-Type'] = 'application/json';
        }

        if (null !== $this->file) {
            $headers['Content-Type'] = 'multipart/form-data';
        }

        if ($authenticated) {
            $headers['Authorization'] = 'Bearer ***';
        }

        return $headers;
    }
}
