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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Runtime;

/**
 * Logs in with a username and an API key, to get a JWT. The JWT is stored in the session file, to be reused by
 * the next commands, and is printed only with --print-token to not leak it in the transcript of an agent.
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

        $this->setDescription('Login with a username and an API key, and store the JWT');
        $this->setHelp(
            Authenticator::USERNAME_HINT . ".\n\n"
            . "The API key is read from the environment variable EAST_WEBSITE_API_KEY, or from --api-key-file "
            . "(--api-key-file=- reads stdin). It is never accepted as an option value.\n\n"
            . 'The username is given with --username (or EAST_WEBSITE_USERNAME), '
            . 'or composed from --key-name and --email.'
        );
        $this->addOption('key-name', null, InputOption::VALUE_REQUIRED, 'Name of the API key, used with --email');
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email of the owner of the API key');
        $this->addOption('print-token', null, InputOption::VALUE_NONE, 'Print the JWT in the result');
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $keyName = InputReader::string($input, 'key-name');
        $email = InputReader::string($input, 'email');
        if (null !== $keyName && '' !== $keyName && null !== $email && '' !== $email) {
            $connection = $connection->withCredentials($connection->credentials->withUsername($keyName . ':' . $email));
        } elseif ((null !== $keyName && '' !== $keyName) || (null !== $email && '' !== $email)) {
            throw ApiException::usage('The options --key-name and --email must be used together');
        }

        $session = $this->runtime->authenticator->login($connection);

        $data = [
            'baseUrl' => $session->baseUrl,
            'username' => $session->username,
            'expiresAt' => $session->expirationDate(),
            'sessionFile' => $connection->useSession ? $connection->sessionPath : null,
        ];

        if (InputReader::flag($input, 'print-token')) {
            $data['token'] = $session->token;
        }

        $this->emit($input, $output, ['meta' => ['error' => false], 'data' => $data]);
    }
}
