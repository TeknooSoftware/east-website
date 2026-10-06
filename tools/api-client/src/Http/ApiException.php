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

use RuntimeException;
use Throwable;

use function is_array;
use function is_string;
use function mb_strimwidth;
use function sprintf;
use function trim;

/**
 * Failure of the CLI or of the remote API. Its kind defines the exit code, and it is rendered on stderr like an
 * API error envelope.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ApiException extends RuntimeException
{
    /**
     * @param array<mixed> $fields validation errors, indexed by form path
     * @param array<string, mixed> $extra additional entries exposed in the error document (hint, partial state, ...)
     */
    public function __construct(
        string $message,
        public readonly ErrorKind $kind,
        public readonly int $status = 0,
        public readonly array $fields = [],
        public readonly array $extra = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $kind->exitCode(), $previous);
    }

    public static function usage(string $message): self
    {
        return new self($message, ErrorKind::Usage);
    }

    public static function fromResponse(ApiResponse $response): self
    {
        $kind = ErrorKind::fromStatus($response->status);
        $data = $response->data();
        $message = is_array($data) && is_string($data['message'] ?? null) ? $data['message'] : null;
        $fields = [];
        $extra = [];

        if (ErrorKind::Validation === $kind) {
            if (true === ($response->meta()['errors'] ?? false) && is_array($data)) {
                $fields = $data;
                $message = 'Validation failed';
            } else {
                $message ??= 'The server rejected the submitted data without detailing the errors';
                $extra['rejected'] = $data;
            }
        }

        if (null === $message) {
            $excerpt = trim(mb_strimwidth($response->raw, 0, 200, '...'));
            $detail = '' !== $excerpt ? ': ' . $excerpt : '';
            $message = sprintf('Unexpected HTTP status %d%s', $response->status, $detail);
        }

        return new self($message, $kind, $response->status, $fields, $extra);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function withExtra(array $extra): self
    {
        return new self(
            $this->getMessage(),
            $this->kind,
            $this->status,
            $this->fields,
            $extra + $this->extra,
            $this->getPrevious(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'code' => $this->status,
            'kind' => $this->kind->value,
            'message' => $this->getMessage(),
        ];

        if ([] !== $this->fields) {
            $data['fields'] = $this->fields;
        }

        return ['meta' => ['error' => true], 'data' => $data + $this->extra];
    }
}
