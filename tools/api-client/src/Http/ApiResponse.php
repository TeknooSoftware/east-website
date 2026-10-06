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

use function is_array;
use function is_string;
use function strtolower;

/**
 * Response of the remote API, with its decoded JSON envelope ({"meta": ..., "data": ...}) when the body is a JSON
 * document.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ApiResponse
{
    /**
     * @param array<string, list<string>> $headers header names are lower cased
     * @param array<mixed>|null $body
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $raw,
        public readonly ?array $body,
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    public function location(): ?string
    {
        return $this->header('location');
    }

    public function isSuccess(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isRedirect(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * @return array<mixed>
     */
    public function meta(): array
    {
        $meta = $this->body['meta'] ?? null;

        return is_array($meta) ? $meta : [];
    }

    public function data(): mixed
    {
        return $this->body['data'] ?? null;
    }

    public function id(): ?string
    {
        $id = $this->meta()['id'] ?? null;
        if (is_string($id) && '' !== $id) {
            return $id;
        }

        $data = $this->data();
        $id = is_array($data) ? ($data['id'] ?? null) : null;

        return is_string($id) && '' !== $id ? $id : null;
    }
}
