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

use LogicException;

use function ltrim;
use function preg_replace_callback;
use function rawurlencode;
use function rtrim;
use function sprintf;

/**
 * Builds the paths of the remote API. The prefixes and the login path are defined by the application hosting East
 * Website, so they are configurable.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Endpoints
{
    public const string DEFAULT_API_PREFIX = '/api/v1';

    public const string DEFAULT_ADMIN_PREFIX = '/api/v1/admin';

    public const string DEFAULT_LOGIN_PATH = '/api/v1/login';

    private readonly string $apiPrefix;

    private readonly string $adminPrefix;

    private readonly string $loginPath;

    public function __construct(
        string $apiPrefix = self::DEFAULT_API_PREFIX,
        string $adminPrefix = self::DEFAULT_ADMIN_PREFIX,
        string $loginPath = self::DEFAULT_LOGIN_PATH,
    ) {
        $this->apiPrefix = self::normalize($apiPrefix);
        $this->adminPrefix = self::normalize($adminPrefix);
        $this->loginPath = self::normalize($loginPath);
    }

    private static function normalize(string $path): string
    {
        return rtrim('/' . ltrim($path, '/'), '/');
    }

    public function apiPrefix(): string
    {
        return $this->apiPrefix;
    }

    public function adminPrefix(): string
    {
        return $this->adminPrefix;
    }

    public function login(): string
    {
        return $this->loginPath;
    }

    public function renew(): string
    {
        return $this->api('jwt/create-token');
    }

    /**
     * @param array<string, string> $params values of the {placeholders}, url encoded by this method
     */
    public function admin(string $template, array $params = []): string
    {
        return $this->adminPrefix . '/' . ltrim(self::expand($template, $params), '/');
    }

    /**
     * @param array<string, string> $params values of the {placeholders}, url encoded by this method
     */
    public function api(string $template, array $params = []): string
    {
        return $this->apiPrefix . '/' . ltrim(self::expand($template, $params), '/');
    }

    /**
     * @param array<string, string> $params
     */
    private static function expand(string $template, array $params): string
    {
        return (string) preg_replace_callback(
            '/\{([a-z0-9-]+)}/i',
            static fn (array $match): string => rawurlencode(
                $params[$match[1]] ?? throw new LogicException(sprintf('Missing path parameter "%s"', $match[1]))
            ),
            $template,
        );
    }
}
