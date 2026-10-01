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

namespace Teknoo\East\Website\Tools\Auth;

use ErrorException;
use RuntimeException;
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
use function rename;
use function restore_error_handler;
use function rtrim;
use function set_error_handler;
use function sprintf;
use function tempnam;
use function unlink;

use const PHP_OS_FAMILY;

/**
 * Storage of the last session in a private file (0600), written atomically and read tolerantly: any problem when
 * reading is the same as "no session". It lives in the state directory of the user, because a JWT is a state, not
 * a configuration.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SessionFile
{
    public const string ENV_PATH = 'EAST_WEBSITE_SESSION_FILE';

    private const int VERSION = 1;

    public function __construct(
        private readonly string $path,
    ) {
    }

    /**
     * @param array<string, string> $env
     */
    public static function defaultPath(array $env): ?string
    {
        $explicit = $env[self::ENV_PATH] ?? '';
        if ('' !== $explicit) {
            return $explicit;
        }

        $state = $env['XDG_STATE_HOME'] ?? '';
        if ('' === $state && 'Windows' === PHP_OS_FAMILY) {
            $state = $env['LOCALAPPDATA'] ?? '';
        }

        if ('' === $state) {
            $home = $env['HOME'] ?? $env['USERPROFILE'] ?? '';
            if ('' === $home) {
                return null;
            }

            $state = rtrim($home, '/\\') . '/.local/state';
        }

        return rtrim($state, '/\\') . '/east-website-cli/session.json';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function read(): ?Session
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            return null;
        }

        $content = file_get_contents($this->path);
        $data = false !== $content ? Json::decode($content) : null;
        if (!is_array($data) || self::VERSION !== ($data['version'] ?? null)) {
            return null;
        }

        $baseUrl = $data['baseUrl'] ?? null;
        $username = $data['username'] ?? null;
        $token = $data['token'] ?? null;
        $expiresAt = $data['expiresAt'] ?? null;
        if (!is_string($baseUrl) || !is_string($username) || !is_string($token) || '' === $token) {
            return null;
        }

        return new Session($baseUrl, $username, $token, is_int($expiresAt) ? $expiresAt : null);
    }

    /**
     * @throws RuntimeException when the session can not be stored
     */
    public function write(Session $session): void
    {
        $directory = dirname($this->path);
        $json = Json::encode([
            'version' => self::VERSION,
            'baseUrl' => $session->baseUrl,
            'username' => $session->username,
            'token' => $session->token,
            'expiresAt' => $session->expiresAt,
        ]);

        self::guard(
            function () use ($directory, $json): void {
                if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                    throw new ErrorException(sprintf('The directory "%s" can not be created', $directory));
                }

                if (!is_writable($directory)) {
                    throw new ErrorException(sprintf('The directory "%s" is not writable', $directory));
                }

                $temporary = tempnam($directory, 'session');
                if (false === $temporary) {
                    throw new ErrorException(sprintf('A temporary file can not be created in "%s"', $directory));
                }

                try {
                    if ('Windows' !== PHP_OS_FAMILY) {
                        chmod($temporary, 0600);
                    }

                    if (false === file_put_contents($temporary, $json)) {
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
            sprintf('The session file "%s" can not be written', $this->path),
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
