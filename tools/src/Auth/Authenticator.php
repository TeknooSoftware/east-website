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
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Warnings;

use function is_array;
use function is_string;

/**
 * Provides the JWT to use, in this order: a JWT given explicitly, a valid stored session, a new login with the
 * username and the API key. The stored session is reused to avoid a login on each call, because hosts can
 * throttle the login.
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

    private bool $loggedIn = false;

    public function __construct(
        private readonly Transport $transport,
        private readonly ClockInterface $clock,
        private readonly Warnings $warnings,
    ) {
    }

    public function bearer(Connection $connection): ?string
    {
        if ($connection->anonymous) {
            return null;
        }

        $credentials = $connection->credentials;
        if (null !== $credentials->token && '' !== $credentials->token) {
            return $credentials->token;
        }

        $session = $this->stored($connection);
        if (null !== $session && $session->isValidAt($this->clock->now()->getTimestamp())) {
            return $session->token;
        }

        if ($credentials->canLogin()) {
            return $this->login($connection)->token;
        }

        return null;
    }

    public function stored(Connection $connection): ?Session
    {
        if (!$connection->useSession || null === $connection->sessionPath) {
            return null;
        }

        $session = (new SessionFile($connection->sessionPath))->read();
        if (null === $session || !$session->matches($connection->baseUrl, $connection->credentials->username)) {
            return null;
        }

        return $session;
    }

    /**
     * A new login is allowed after a 401 only once by process, and only when the JWT was not given explicitly.
     */
    public function canRelogin(Connection $connection): bool
    {
        $credentials = $connection->credentials;

        $explicit = null !== $credentials->token && '' !== $credentials->token;

        return !$this->loggedIn && !$explicit && $credentials->canLogin();
    }

    public function login(Connection $connection): Session
    {
        $credentials = $connection->credentials;
        $username = $credentials->username;
        $apiKey = $credentials->apiKey();
        if (!$credentials->canLogin() || null === $username || null === $apiKey) {
            throw ApiException::usage(
                'A username and its API key are required to login: use --username (or EAST_WEBSITE_USERNAME) and '
                . 'the API key with --api-key-file (or EAST_WEBSITE_API_KEY). ' . self::USERNAME_HINT
            );
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

        $session = new Session($connection->baseUrl, $username, $token, Jwt::expiresAt($token));
        $this->loggedIn = true;
        $this->persist($connection, $session);

        return $session;
    }

    public function persist(Connection $connection, Session $session): void
    {
        if (!$connection->useSession) {
            return;
        }

        if (null === $connection->sessionPath) {
            $this->warnings->add(
                'The session can not be stored, neither HOME nor XDG_STATE_HOME is defined: '
                . 'use --session-file or EAST_WEBSITE_SESSION_FILE'
            );

            return;
        }

        try {
            (new SessionFile($connection->sessionPath))->write($session);
        } catch (RuntimeException $error) {
            $this->warnings->add($error->getMessage());
        }
    }
}
