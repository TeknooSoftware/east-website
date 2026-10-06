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
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Runtime;

/**
 * Deletes the configuration file written by the login: the next commands have no JWT anymore. The API has no logout
 * endpoint, so the JWT stays valid on the server until its expiration.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LogoutCommand extends AbstractCommand
{
    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:auth:logout');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription(
            'Delete the configuration file of the CLI (the JWT stays valid on the server until its expiration)'
        );
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $deleted = (new ConfigFile($connection->configFile))->delete();

        $this->emit(
            $input,
            $output,
            ['meta' => ['error' => false], 'data' => ['configFile' => $connection->configFile, 'deleted' => $deleted]],
        );
    }
}
