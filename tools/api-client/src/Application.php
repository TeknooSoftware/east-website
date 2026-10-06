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

namespace Teknoo\East\Website\Tools;

use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Auth\SystemClock;
use Teknoo\East\Website\Tools\Command\Auth\LoginCommand;
use Teknoo\East\Website\Tools\Command\Auth\LogoutCommand;
use Teknoo\East\Website\Tools\Command\Auth\RenewCommand;
use Teknoo\East\Website\Tools\Command\Auth\StatusCommand;
use Teknoo\East\Website\Tools\Command\Front\CommentCommand;
use Teknoo\East\Website\Tools\Command\Front\EndpointCommand;
use Teknoo\East\Website\Tools\Command\Media\UploadCommand;
use Teknoo\East\Website\Tools\Command\Resource\CreateCommand;
use Teknoo\East\Website\Tools\Command\Resource\DeleteCommand;
use Teknoo\East\Website\Tools\Command\Resource\GetCommand;
use Teknoo\East\Website\Tools\Command\Resource\ListCommand;
use Teknoo\East\Website\Tools\Command\Resource\UpdateCommand;
use Teknoo\East\Website\Tools\Command\SchemaCommand;
use Teknoo\East\Website\Tools\Config\ConnectionFactory;
use Teknoo\East\Website\Tools\Http\ApiClient;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Transport;
use Teknoo\East\Website\Tools\Output\Renderer;
use Teknoo\East\Website\Tools\Output\Warnings;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceGateway;
use Teknoo\East\Website\Tools\Tui\Driver\DriverInterface;
use Teknoo\East\Website\Tools\Tui\Driver\TerminalDriver;
use Teknoo\East\Website\Tools\Tui\TuiLauncher;

use function getcwd;

/**
 * Console application of the East Website CLI: a client of the remote JSON API, usable by humans and by agents.
 * The connection is configured only by the login, in a JSON file (./east-website.json by default) read by the
 * other commands. The HTTP client, the clock, the working directory and the driver of the terminal of the
 * interactive mode are injectable, to test the whole application without any network nor terminal.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Application extends BaseApplication
{
    private Runtime $runtime;

    /**
     * @param string|null $workingDirectory directory of the default configuration file, the current one by default
     * @param DriverInterface|null $driver terminal of the interactive mode (--format=tui), the real one by default
     */
    public static function create(
        ?HttpClientInterface $http = null,
        ?ClockInterface $clock = null,
        ?string $workingDirectory = null,
        ?DriverInterface $driver = null,
    ): self {
        $clock ??= new SystemClock();
        $warnings = new Warnings();
        $transport = new Transport($http ?? HttpClient::create());
        $authenticator = new Authenticator($transport, $clock, $warnings);

        $client = new ApiClient($transport, $authenticator);
        $gateway = new ResourceGateway($client);
        $registry = new Registry();

        $runtime = new Runtime(
            client: $client,
            authenticator: $authenticator,
            connections: new ConnectionFactory($workingDirectory ?? (string) getcwd()),
            renderer: new Renderer(),
            warnings: $warnings,
            registry: $registry,
            clock: $clock,
            gateway: $gateway,
            tui: new TuiLauncher($driver ?? new TerminalDriver(), $gateway, $registry),
        );

        $application = new self(Version::NAME, Version::VERSION);
        $application->runtime = $runtime;
        $application->registerCommands();

        return $application;
    }

    private function registerCommands(): void
    {
        $runtime = $this->runtime;

        foreach ($runtime->registry->resources() as $resource) {
            $this->addCommand(new ListCommand($runtime, $resource));
            $this->addCommand(new GetCommand($runtime, $resource));

            if ($resource->supports(Operation::Create)) {
                $this->addCommand(new CreateCommand($runtime, $resource));
            }

            if ($resource->supports(Operation::Update)) {
                $this->addCommand(new UpdateCommand($runtime, $resource));
            }

            $this->addCommand(new DeleteCommand($runtime, $resource));
        }

        $this->addCommand(new UploadCommand($runtime));

        foreach ($runtime->registry->front() as $endpoint) {
            $this->addCommand(new EndpointCommand($runtime, $endpoint));
        }

        $this->addCommand(new CommentCommand($runtime));
        $this->addCommand(new LoginCommand($runtime));
        $this->addCommand(new RenewCommand($runtime));
        $this->addCommand(new StatusCommand($runtime));
        $this->addCommand(new LogoutCommand($runtime));
        $this->addCommand(new SchemaCommand($runtime));
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOptions(ConnectionFactory::options());

        return $definition;
    }

    /**
     * The usage errors of the Console component (unknown command or option, missing argument) follow the same
     * contract than the other failures: a JSON document on stderr, and the exit code 2.
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::doRun($input, $output);
        } catch (ExceptionInterface $error) {
            $errors = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            $this->runtime->renderer->error($errors, ApiException::usage($error->getMessage()));

            return ErrorKind::Usage->exitCode();
        }
    }
}
