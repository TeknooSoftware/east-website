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

use Psr\Clock\ClockInterface;
use RuntimeException;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Warnings;

use function is_array;
use function is_string;

/**
 * Provides the JWT of the configuration file written by the login. When it is expired, or rejected by the server,
 * the CLI logs in again with the username and the API key of this file, and stores the new JWT in it. The JWT is
 * reused between the calls, because hosts can throttle the login.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Authenticator
{
    public const string USERNAME_HINT = "The username must be '<keyName>:<email>' (the name of the API key, a colon, "
        . "then the email of its owner) and the secret must be the API key itself";

    /**
     * Credentials obtained by the last login of this process, used by the next requests of the same command.
     */
    private ?Credentials $renewed = null;

    public function __construct(
        private readonly Transport $transport,
        private readonly ClockInterface $clock,
        private readonly Warnings $warnings,
    ) {
    }

    public function bearer(Connection $connection): ?string
    {
        if ($connection->anonymous || !$connection->configured) {
            return null;
        }

        $now = $this->clock->now()->getTimestamp();
        foreach ([$this->renewed, $connection->credentials] as $credentials) {
            if (null !== $credentials && $credentials->isValidAt($now)) {
                return $credentials->token;
            }
        }

        if ($connection->credentials->canLogin()) {
            return $this->relogin($connection)->credentials->token;
        }

        return null;
    }

    /**
     * A new login is allowed after a 401 only once by process, and only with the API key of the configuration.
     */
    public function canRelogin(Connection $connection): bool
    {
        return null === $this->renewed && $connection->configured && $connection->credentials->canLogin();
    }

    /**
     * Logs in again with the credentials of the configuration file, and stores the new JWT in this file.
     */
    public function relogin(Connection $connection): Connection
    {
        $connection = $this->login($connection);
        $this->persist($connection);

        return $connection;
    }

    /**
     * Logs in with the username and the API key of the connection, and returns the connection with the new JWT.
     */
    public function login(Connection $connection): Connection
    {
        $credentials = $connection->credentials;
        $username = $credentials->username;
        $apiKey = $credentials->apiKey();
        if (!$credentials->canLogin() || null === $username || null === $apiKey) {
            throw ApiException::usage('A username and its API key are required to login. ' . self::USERNAME_HINT);
        }

        $response = $this->transport->send(
            $connection,
            ApiRequest::json(
                'POST',
                $connection->endpoints->login(),
                [$connection->usernameField => $username, $connection->tokenField => $apiKey],
            ),
            null,
        );

        if (!$response->isSuccess()) {
            $error = ApiException::fromResponse($response);
            if (ErrorKind::Auth === $error->kind) {
                $error = $error->withExtra(['hint' => self::USERNAME_HINT]);
            }

            throw $error;
        }

        $data = $response->data();
        $token = is_array($data) ? ($data['token'] ?? null) : null;
        if (!is_string($token) || '' === $token) {
            throw new ApiException('The login response does not contain a token', ErrorKind::Server, $response->status);
        }

        $this->renewed = $credentials->withToken($token, Jwt::expiresAt($token));

        return $connection->withCredentials($this->renewed);
    }

    /**
     * Stores the new JWT of an automatic login: when the configuration file can not be written, the command goes
     * on, with a warning.
     */
    public function persist(Connection $connection): void
    {
        try {
            (new ConfigFile($connection->configFile))->write($connection);
        } catch (RuntimeException $error) {
            $this->warnings->add($error->getMessage());
        }
    }
}
