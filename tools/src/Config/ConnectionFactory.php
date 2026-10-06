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
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\East\Website\Tools\Input\InputReader;

/**
 * Builds the connection of a command from the configuration file written by the login (website:auth:login): it is
 * the only source of the URL, of the options and of the credentials. Without this file, the connection is not
 * configured, and no request can be sent.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConnectionFactory
{
    public function __construct(
        private readonly string $workingDirectory,
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
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'Configuration file written by website:auth:login (./' . ConfigFile::DEFAULT_NAME . ' by default)',
            ),
        ];
    }

    public function file(InputInterface $input): ConfigFile
    {
        return ConfigFile::resolve($this->workingDirectory, InputReader::string($input, 'config'));
    }

    public function create(InputInterface $input): Connection
    {
        $file = $this->file($input);
        $connection = $file->read() ?? new Connection('', new Endpoints(), new Credentials(), $file->path());

        return InputReader::flag($input, 'anonymous') ? $connection->asAnonymous() : $connection;
    }
}
