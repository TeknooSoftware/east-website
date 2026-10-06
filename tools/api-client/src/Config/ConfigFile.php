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

use ErrorException;
use RuntimeException;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\Json;
use Throwable;

use function chmod;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_readable;
use function is_string;
use function is_writable;
use function mkdir;
use function preg_match;
use function rename;
use function restore_error_handler;
use function rtrim;
use function set_error_handler;
use function sprintf;
use function tempnam;
use function unlink;

use const PHP_OS_FAMILY;

/**
 * Configuration file of the CLI, written by the login (website:auth:login) and read by all the other commands: the
 * base URL, the options of the connection, the username with its API key and the last JWT. Without this file, there
 * is no JWT. It contains the API key, so it is private (0600) and written atomically; a missing or an invalid file
 * is read as "not configured".
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfigFile
{
    public const string DEFAULT_NAME = 'east-website.json';

    private const int VERSION = 1;

    public function __construct(
        private readonly string $path,
    ) {
    }

    /**
     * The file given by the option --config, else the default file of the working directory. A relative path is
     * relative to the working directory.
     */
    public static function resolve(string $workingDirectory, ?string $path): self
    {
        if (null === $path || '' === $path) {
            $path = self::DEFAULT_NAME;
        }

        if (!self::isAbsolute($path)) {
            $path = rtrim($workingDirectory, '/\\') . '/' . $path;
        }

        return new self($path);
    }

    private static function isAbsolute(string $path): bool
    {
        return '/' === $path[0] || '\\' === $path[0] || 1 === preg_match('#^[a-zA-Z]:[/\\\\]#', $path);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function read(): ?Connection
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            return null;
        }

        $content = file_get_contents($this->path);
        $data = false !== $content ? Json::decode($content) : null;
        if (!is_array($data) || self::VERSION !== ($data['version'] ?? null)) {
            return null;
        }

        $url = $data['url'] ?? null;
        if (!is_string($url) || '' === $url) {
            return null;
        }

        $timeout = $data['timeout'] ?? null;

        return new Connection(
            baseUrl: $url,
            endpoints: new Endpoints(
                self::string($data, 'apiPrefix') ?? Endpoints::DEFAULT_API_PREFIX,
                self::string($data, 'adminPrefix') ?? Endpoints::DEFAULT_ADMIN_PREFIX,
                self::string($data, 'loginPath') ?? Endpoints::DEFAULT_LOGIN_PATH,
            ),
            credentials: new Credentials(
                self::string($data, 'username'),
                self::string($data, 'apiKey'),
                self::string($data, 'token'),
                is_int($data['expiresAt'] ?? null) ? $data['expiresAt'] : null,
            ),
            configFile: $this->path,
            configured: true,
            insecure: true === ($data['insecure'] ?? false),
            allowHttp: true === ($data['allowHttp'] ?? false),
            timeout: is_int($timeout) && $timeout > 0 ? $timeout : Connection::DEFAULT_TIMEOUT,
            usernameField: self::string($data, 'usernameField') ?? 'username',
            tokenField: self::string($data, 'tokenField') ?? 'token',
        );
    }

    /**
     * @param array<mixed> $data
     */
    private static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && '' !== $value ? $value : null;
    }

    /**
     * @throws RuntimeException when the file can not be written
     */
    public function write(Connection $connection): void
    {
        $directory = dirname($this->path);
        $credentials = $connection->credentials;
        $json = Json::encode(
            [
                'version' => self::VERSION,
                'url' => $connection->baseUrl,
                'username' => $credentials->username,
                'apiKey' => $credentials->apiKey(),
                'token' => $credentials->token,
                'expiresAt' => $credentials->expiresAt,
                'insecure' => $connection->insecure,
                'allowHttp' => $connection->allowHttp,
                'timeout' => $connection->timeout,
                'apiPrefix' => $connection->endpoints->apiPrefix(),
                'adminPrefix' => $connection->endpoints->adminPrefix(),
                'loginPath' => $connection->endpoints->login(),
                'usernameField' => $connection->usernameField,
                'tokenField' => $connection->tokenField,
            ],
            true,
        );

        self::guard(
            function () use ($directory, $json): void {
                if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                    throw new ErrorException(sprintf('The directory "%s" can not be created', $directory));
                }

                if (!is_writable($directory)) {
                    throw new ErrorException(sprintf('The directory "%s" is not writable', $directory));
                }

                $temporary = tempnam($directory, 'east-website');
                if (false === $temporary) {
                    throw new ErrorException(sprintf('A temporary file can not be created in "%s"', $directory));
                }

                try {
                    if ('Windows' !== PHP_OS_FAMILY) {
                        chmod($temporary, 0600);
                    }

                    if (false === file_put_contents($temporary, $json . "\n")) {
                        throw new ErrorException('The content can not be written');
                    }

                    if (!rename($temporary, $this->path)) {
                        throw new ErrorException('The file can not be moved');
                    }
                } catch (Throwable $error) {
                    if (is_file($temporary)) {
                        unlink($temporary);
                    }

                    throw $error;
                }
            },
            sprintf('The configuration file "%s" can not be written', $this->path),
        );
    }

    public function delete(): bool
    {
        return is_file($this->path) && unlink($this->path);
    }

    /**
     * Converts the PHP warnings of the file system functions to an exception handled by the caller, instead of
     * letting them be printed on the output of a CLI used by scripts and agents.
     *
     * @param callable(): void $operation
     */
    private static function guard(callable $operation, string $message): void
    {
        set_error_handler(static function (int $severity, string $text): never {
            throw new ErrorException($text, 0, $severity);
        });

        try {
            $operation();
        } catch (Throwable $error) {
            throw new RuntimeException($message . ': ' . $error->getMessage(), 0, $error);
        } finally {
            restore_error_handler();
        }
    }
}
