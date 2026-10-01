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

use DateTimeImmutable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Auth\Jwt;
use Teknoo\East\Website\Tools\Auth\Session;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Runtime;

use function is_array;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * Gets a new JWT from the current one (there is no refresh token). Without expiration date, the server grants its
 * maximum lifetime.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class RenewCommand extends AbstractCommand
{
    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:auth:renew');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Get a new JWT from the current one, and store it');
        $this->addOption('expiration-date', null, InputOption::VALUE_REQUIRED, 'Expiration date, as YYYY-MM-DD');
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Lifetime of the JWT in days, instead of a date');
        $this->addOption('print-token', null, InputOption::VALUE_NONE, 'Print the JWT in the result');
        $this->addDryRunOption();
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $body = [];
        $date = $this->expirationDate($input);
        if (null !== $date) {
            $body['expirationDate'] = $date;
        }

        $request = ApiRequest::json('POST', $connection->endpoints->renew(), $body);
        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$request]);

            return;
        }

        $response = $this->runtime->client->call($connection, $request);
        $data = $response->data();
        $token = is_array($data) ? ($data['token'] ?? null) : null;
        if (!is_string($token) || '' === $token) {
            throw new ApiException('The response does not contain a token', ErrorKind::Server, $response->status);
        }

        $stored = $this->runtime->authenticator->stored($connection);
        $username = $connection->credentials->username ?? $stored->username ?? '';
        $session = new Session($connection->baseUrl, $username, $token, Jwt::expiresAt($token));
        if (!$connection->anonymous) {
            $this->runtime->authenticator->persist($connection, $session);
        }

        $result = [
            'baseUrl' => $session->baseUrl,
            'username' => $session->username,
            'expiresAt' => $session->expirationDate(),
            'sessionFile' => $connection->useSession ? $connection->sessionPath : null,
        ];

        if (InputReader::flag($input, 'print-token')) {
            $result['token'] = $token;
        }

        $this->emit($input, $output, ['meta' => ['error' => false], 'data' => $result]);
    }

    private function expirationDate(InputInterface $input): ?string
    {
        $date = InputReader::string($input, 'expiration-date');
        $days = InputReader::int($input, 'days');
        if (null !== $date && null !== $days) {
            throw ApiException::usage('The options --expiration-date and --days are mutually exclusive');
        }

        if (null !== $days) {
            if ($days < 1) {
                throw ApiException::usage('The number of days must be greater or equal to 1');
            }

            return $this->runtime->clock->now()->modify(sprintf('+%d days', $days))->format('Y-m-d');
        }

        if (null === $date) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $valid = 1 === preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && false !== $parsed
            && $parsed->format('Y-m-d') === $date;
        if (!$valid) {
            throw ApiException::usage(
                sprintf('The expiration date must be a valid date as YYYY-MM-DD, "%s" given', $date)
            );
        }

        return $date;
    }
}
