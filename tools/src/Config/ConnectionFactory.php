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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Teknoo\East\Website\Tools\Auth\SessionFile;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Input\InputReader;

use function filter_var;
use function rtrim;
use function sprintf;
use function trim;

use const FILTER_VALIDATE_INT;

/**
 * Builds the connection from the global options and from the environment (the option wins). The secrets are
 * accepted only from the environment or from a file (or stdin), never as an option value, because it leaks in the
 * processes list, the shell history and the transcripts of agents.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConnectionFactory
{
    public const string ENV_URL = 'EAST_WEBSITE_URL';

    public const string ENV_USERNAME = 'EAST_WEBSITE_USERNAME';

    public const string ENV_API_KEY = 'EAST_WEBSITE_API_KEY';

    public const string ENV_TOKEN = 'EAST_WEBSITE_TOKEN';

    public const string ENV_TIMEOUT = 'EAST_WEBSITE_TIMEOUT';

    public const string ENV_LOGIN_PATH = 'EAST_WEBSITE_LOGIN_PATH';

    public const string ENV_API_PREFIX = 'EAST_WEBSITE_API_PREFIX';

    public const string ENV_ADMIN_PREFIX = 'EAST_WEBSITE_ADMIN_PREFIX';

    public const string ENV_LOGIN_USERNAME_FIELD = 'EAST_WEBSITE_LOGIN_USERNAME_FIELD';

    public const string ENV_LOGIN_SECRET_FIELD = 'EAST_WEBSITE_LOGIN_SECRET_FIELD';

    private const int DEFAULT_TIMEOUT = 30;

    /**
     * @param array<string, string> $env
     */
    public function __construct(
        private readonly array $env,
    ) {
    }

    /**
     * Global options of the application, they can not collide with the options of the fields of the resources.
     *
     * @return list<InputOption>
     */
    public static function options(): array
    {
        return [
            new InputOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                'Base URL of the website (env ' . self::ENV_URL . ')',
            ),
            new InputOption(
                'username',
                null,
                InputOption::VALUE_REQUIRED,
                'Login, as <keyName>:<email> (env ' . self::ENV_USERNAME . ')',
            ),
            new InputOption(
                'api-key-file',
                null,
                InputOption::VALUE_REQUIRED,
                'File containing the API key, or --api-key-file=- for stdin (env ' . self::ENV_API_KEY . ')',
            ),
            new InputOption(
                'token-file',
                null,
                InputOption::VALUE_REQUIRED,
                'File containing a JWT to use as is, or --token-file=- for stdin (env ' . self::ENV_TOKEN . ')',
            ),
            new InputOption(
                'session-file',
                null,
                InputOption::VALUE_REQUIRED,
                'File storing the JWT between two calls (env ' . SessionFile::ENV_PATH . ')',
            ),
            new InputOption('no-session', null, InputOption::VALUE_NONE, 'Do not read nor write the session file'),
            new InputOption('anonymous', null, InputOption::VALUE_NONE, 'Do not send any JWT'),
            new InputOption('insecure', null, InputOption::VALUE_NONE, 'Do not verify the TLS certificate'),
            new InputOption('allow-http', null, InputOption::VALUE_NONE, 'Allow plain http to a remote host'),
            new InputOption(
                'timeout',
                null,
                InputOption::VALUE_REQUIRED,
                'Timeout of a request, in seconds (env ' . self::ENV_TIMEOUT . ')',
            ),
        ];
    }

    public function create(InputInterface $input): Connection
    {
        $useSession = !InputReader::flag($input, 'no-session');
        $sessionPath = $useSession
            ? $this->setting($input, 'session-file', SessionFile::ENV_PATH) ?? SessionFile::defaultPath($this->env)
            : null;

        $username = $this->setting($input, 'username', self::ENV_USERNAME);
        $baseUrl = $this->setting($input, 'url', self::ENV_URL);
        if (null === $baseUrl && null !== $sessionPath) {
            $session = (new SessionFile($sessionPath))->read();
            $baseUrl = null !== $session && $session->matches($session->baseUrl, $username) ? $session->baseUrl : null;
        }

        return new Connection(
            baseUrl: rtrim($baseUrl ?? '', '/'),
            endpoints: new Endpoints(
                $this->env[self::ENV_API_PREFIX] ?? Endpoints::DEFAULT_API_PREFIX,
                $this->env[self::ENV_ADMIN_PREFIX] ?? Endpoints::DEFAULT_ADMIN_PREFIX,
                $this->env[self::ENV_LOGIN_PATH] ?? Endpoints::DEFAULT_LOGIN_PATH,
            ),
            credentials: new Credentials(
                $username,
                $this->secret($input, 'api-key-file', self::ENV_API_KEY),
                $this->secret($input, 'token-file', self::ENV_TOKEN),
            ),
            useSession: $useSession,
            sessionPath: $sessionPath,
            insecure: InputReader::flag($input, 'insecure'),
            allowHttp: InputReader::flag($input, 'allow-http'),
            anonymous: InputReader::flag($input, 'anonymous'),
            timeout: $this->timeout($input),
            usernameField: $this->env[self::ENV_LOGIN_USERNAME_FIELD] ?? 'username',
            tokenField: $this->env[self::ENV_LOGIN_SECRET_FIELD] ?? 'token',
        );
    }

    private function setting(InputInterface $input, string $option, string $env): ?string
    {
        $value = InputReader::string($input, $option);
        if (null !== $value && '' !== $value) {
            return $value;
        }

        $value = $this->env[$env] ?? '';

        return '' !== $value ? $value : null;
    }

    private function secret(InputInterface $input, string $option, string $env): ?string
    {
        $file = InputReader::string($input, $option);
        if (null !== $file && '' !== $file) {
            $secret = trim(InputReader::content($input, $file));

            return '' !== $secret ? $secret : null;
        }

        $secret = trim($this->env[$env] ?? '');

        return '' !== $secret ? $secret : null;
    }

    private function timeout(InputInterface $input): int
    {
        $value = $this->setting($input, 'timeout', self::ENV_TIMEOUT);
        if (null === $value) {
            return self::DEFAULT_TIMEOUT;
        }

        $timeout = filter_var($value, FILTER_VALIDATE_INT);
        if (false === $timeout || $timeout < 1) {
            throw ApiException::usage(sprintf('The timeout must be a positive number of seconds, "%s" given', $value));
        }

        return $timeout;
    }
}
