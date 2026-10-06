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

namespace Teknoo\East\Website\Tools\Command\Auth;

use RuntimeException;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Runtime;

use function defined;
use function is_string;
use function rtrim;
use function sprintf;
use function stream_isatty;
use function trim;

/**
 * Logs in with a username and an API key, to get a JWT, and writes the configuration file read by all the other
 * commands (./east-website.json by default, or the file of --config): the URL, the options of the connection, the
 * username, the API key (to login again when the JWT expires) and the JWT. It is the only command configuring the
 * connection. The API key is asked on a terminal without being displayed, or read from a file or from stdin, it is
 * never accepted as an option value. The JWT is printed only with --print-token, to not leak it in the transcript of
 * an agent.
 *
 * Run again without options, the login reuses the settings of the existing configuration file. Its API key is
 * reused only for the same URL and the same username: it is never sent to another server or for another user.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoginCommand extends AbstractCommand
{
    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:auth:login');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Login with a username and an API key, and write the configuration file of the CLI');
        $this->setHelp(
            Authenticator::USERNAME_HINT . ".\n\n"
            . 'The configuration (URL, options, username, API key and JWT) is written in ./' . ConfigFile::DEFAULT_NAME
            . " (or in the file of --config), with the mode 0600, and read by all the other commands.\n\n"
            . 'The API key is asked without being displayed on a terminal, or read from --api-key-file '
            . "(--api-key-file=- reads stdin). It is never accepted as an option value.\n\n"
            . 'Run again without options, the login reuses the settings of the existing configuration file for the '
            . 'same URL, and its API key for the same URL and the same username.'
        );
        $this->addOption('url', null, InputOption::VALUE_REQUIRED, 'Base URL of the website, like https://example.com');
        $this->addOption('username', null, InputOption::VALUE_REQUIRED, 'Login, as <keyName>:<email>');
        $this->addOption('key-name', null, InputOption::VALUE_REQUIRED, 'Name of the API key, used with --email');
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email of the owner of the API key');
        $this->addOption(
            'api-key-file',
            null,
            InputOption::VALUE_REQUIRED,
            'File containing the API key, --api-key-file=- reads stdin (else it is asked on a terminal)',
        );
        $this->addOption(
            'insecure',
            null,
            InputOption::VALUE_NEGATABLE,
            'Do not verify the TLS certificate (--no-insecure to verify it again)',
        );
        $this->addOption(
            'allow-http',
            null,
            InputOption::VALUE_NEGATABLE,
            'Allow plain http to a remote host (--no-allow-http to refuse it again)',
        );
        $this->addOption(
            'timeout',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf('Timeout of a request, in seconds (%d by default)', Connection::DEFAULT_TIMEOUT),
        );
        $this->addOption(
            'api-prefix',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf('Prefix of the public routes (%s by default)', Endpoints::DEFAULT_API_PREFIX),
        );
        $this->addOption(
            'admin-prefix',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf('Prefix of the admin routes (%s by default)', Endpoints::DEFAULT_ADMIN_PREFIX),
        );
        $this->addOption(
            'login-path',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf('Login route (%s by default)', Endpoints::DEFAULT_LOGIN_PATH),
        );
        $this->addOption(
            'login-username-field',
            null,
            InputOption::VALUE_REQUIRED,
            'Field of the username in the body of the login (username by default)',
        );
        $this->addOption(
            'login-secret-field',
            null,
            InputOption::VALUE_REQUIRED,
            'Field of the API key in the body of the login (token by default)',
        );
        $this->addOption('print-token', null, InputOption::VALUE_NONE, 'Print the JWT in the result');
    }

    protected function isTerminal(): bool
    {
        return defined('STDIN') && stream_isatty(STDIN);
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $previous = $connection->configured ? $connection : null;

        $url = rtrim($this->setting($input, 'url') ?? $previous->baseUrl ?? '', '/');
        if ('' === $url) {
            throw ApiException::usage('The option --url is required, like --url=https://example.com');
        }

        // The settings of the configuration file are reused only for the same server, and its API key only for the
        // same server and the same user
        $settings = null !== $previous && $previous->baseUrl === $url ? $previous : null;

        $username = $this->username($input) ?? $settings?->credentials->username;
        if (null === $username) {
            throw ApiException::usage(
                'The option --username (or --key-name with --email) is required. ' . Authenticator::USERNAME_HINT
            );
        }

        $storedApiKey = null !== $settings && $settings->credentials->username === $username
            ? $settings->credentials->apiKey()
            : null;

        $connection = new Connection(
            baseUrl: $url,
            endpoints: new Endpoints(
                $this->setting($input, 'api-prefix') ?? $settings?->endpoints->apiPrefix()
                    ?? Endpoints::DEFAULT_API_PREFIX,
                $this->setting($input, 'admin-prefix') ?? $settings?->endpoints->adminPrefix()
                    ?? Endpoints::DEFAULT_ADMIN_PREFIX,
                $this->setting($input, 'login-path') ?? $settings?->endpoints->login()
                    ?? Endpoints::DEFAULT_LOGIN_PATH,
            ),
            credentials: new Credentials(
                $username,
                $this->apiKey($input, $output, $storedApiKey),
            ),
            configFile: $connection->configFile,
            configured: true,
            insecure: InputReader::bool($input, 'insecure') ?? $settings->insecure ?? false,
            allowHttp: InputReader::bool($input, 'allow-http') ?? $settings->allowHttp ?? false,
            timeout: $this->timeout($input) ?? $settings->timeout ?? Connection::DEFAULT_TIMEOUT,
            usernameField: $this->setting($input, 'login-username-field') ?? $settings->usernameField ?? 'username',
            tokenField: $this->setting($input, 'login-secret-field') ?? $settings->tokenField ?? 'token',
        );

        $connection = $this->runtime->authenticator->login($connection);

        try {
            (new ConfigFile($connection->configFile))->write($connection);
        } catch (RuntimeException $error) {
            throw new ApiException($error->getMessage(), ErrorKind::Usage, 0, [], [], $error);
        }

        $credentials = $connection->credentials;
        $data = [
            'configFile' => $connection->configFile,
            'url' => $connection->baseUrl,
            'username' => $credentials->username,
            'expiresAt' => $credentials->expirationDate(),
        ];

        if (InputReader::flag($input, 'print-token')) {
            $data['token'] = $credentials->token;
        }

        $this->emit($input, $output, ['meta' => ['error' => false], 'data' => $data]);
    }

    private function setting(InputInterface $input, string $option): ?string
    {
        $value = InputReader::string($input, $option);

        return null !== $value && '' !== $value ? $value : null;
    }

    private function username(InputInterface $input): ?string
    {
        $keyName = $this->setting($input, 'key-name');
        $email = $this->setting($input, 'email');
        if (null !== $keyName && null !== $email) {
            return $keyName . ':' . $email;
        }

        if (null !== $keyName || null !== $email) {
            throw ApiException::usage('The options --key-name and --email must be used together');
        }

        return $this->setting($input, 'username');
    }

    /**
     * The API key of --api-key-file, else the one of the configuration file for the same account, else it is asked
     * on a terminal, without being displayed.
     */
    private function apiKey(InputInterface $input, OutputInterface $output, ?string $stored): string
    {
        $file = $this->setting($input, 'api-key-file');
        if (null !== $file) {
            $apiKey = trim(InputReader::content($input, $file));
        } elseif (null !== $stored) {
            $apiKey = $stored;
        } elseif ($input->isInteractive() && $this->isTerminal()) {
            $apiKey = $this->ask($input, $output);
        } else {
            throw ApiException::usage(
                'The API key is required: use --api-key-file=<file>, --api-key-file=- to read it from stdin, '
                . 'or run the command in a terminal to type it'
            );
        }

        if ('' === $apiKey) {
            throw ApiException::usage('The API key is empty. ' . Authenticator::USERNAME_HINT);
        }

        return $apiKey;
    }

    private function ask(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('API key: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        try {
            $answer = (new QuestionHelper())->ask($input, $this->errorOutput($output), $question);
        } catch (ConsoleRuntimeException $error) {
            throw ApiException::usage(
                'The API key can not be typed without being displayed on this terminal, use --api-key-file: '
                . $error->getMessage()
            );
        }

        return is_string($answer) ? trim($answer) : '';
    }

    private function timeout(InputInterface $input): ?int
    {
        $timeout = InputReader::int($input, 'timeout');
        if (null !== $timeout && $timeout < 1) {
            throw ApiException::usage(
                sprintf('The timeout must be a positive number of seconds, "%d" given', $timeout)
            );
        }

        return $timeout;
    }
}
