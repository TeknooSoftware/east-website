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
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Runtime;

/**
 * Displays the configuration file written by the login and the state of its JWT, without any network call and
 * without any secret.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class StatusCommand extends AbstractCommand
{
    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:auth:status');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Display the configuration of the CLI and its JWT, offline and without any secret');
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $credentials = $connection->credentials;
        $configured = $connection->configured;

        $this->emit(
            $input,
            $output,
            [
                'meta' => ['error' => false],
                'data' => [
                    'configFile' => $connection->configFile,
                    'configured' => $configured,
                    'url' => $configured ? $connection->baseUrl : null,
                    'username' => $credentials->username,
                    'hasApiKey' => null !== $credentials->apiKey(),
                    'insecure' => $connection->insecure,
                    'expiresAt' => $credentials->expirationDate(),
                    'expired' => $configured
                        ? !$credentials->isValidAt($this->runtime->clock->now()->getTimestamp())
                        : null,
                ],
            ],
        );
    }
}
