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

namespace Teknoo\East\Website\Tools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Output\OutputFormat;
use Teknoo\East\Website\Tools\Runtime;

use function array_map;

/**
 * Base of all the commands of the CLI. The result is a JSON document on stdout, the failure a JSON document on
 * stderr with a stable exit code (0 ok, 1 server or transport, 2 usage or validation, 3 authentication, 4 not
 * found).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractCommand extends Command
{
    public function __construct(
        protected readonly Runtime $runtime,
        string $name,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: json (default, machine readable) or table',
            OutputFormat::Json->value,
        );
        $this->addOption('compact', null, InputOption::VALUE_NONE, 'Print the JSON document on a single line');
    }

    protected function addDryRunOption(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Print the HTTP requests that would be sent, without sending any of them',
        );
    }

    /**
     * Executes the command, the ApiException are rendered on stderr and converted to the exit code of their kind.
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = $this->errorOutput($output);

        try {
            OutputFormat::fromOption(InputReader::string($input, 'format'));
            $this->perform($input, $output, $this->runtime->connections->create($input));
            $this->runtime->warnings->flush($errors);

            return self::SUCCESS;
        } catch (ApiException $error) {
            // stderr stays one JSON document: the warnings are part of it
            $warnings = $this->runtime->warnings->drain();
            $this->runtime->renderer->error(
                $errors,
                [] !== $warnings ? $error->withExtra(['warnings' => $warnings]) : $error,
            );

            return $error->kind->exitCode();
        }
    }

    /**
     * @throws ApiException
     */
    abstract protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void;

    protected function errorOutput(OutputInterface $output): OutputInterface
    {
        return $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }

    /**
     * @param ApiResponse|array<mixed> $result
     */
    protected function emit(InputInterface $input, OutputInterface $output, ApiResponse|array $result): void
    {
        $document = $result instanceof ApiResponse
            ? ($result->body ?? ['meta' => ['error' => false], 'data' => '' !== $result->raw ? $result->raw : null])
            : $result;

        $this->runtime->renderer->render(
            $output,
            $document,
            OutputFormat::fromOption(InputReader::string($input, 'format')),
            InputReader::flag($input, 'compact'),
        );
    }

    protected function isDryRun(InputInterface $input): bool
    {
        return InputReader::flag($input, 'dry-run');
    }

    /**
     * @param list<ApiRequest> $requests
     */
    protected function dryRun(
        InputInterface $input,
        OutputInterface $output,
        Connection $connection,
        array $requests,
        bool $authenticated = true,
    ): void {
        $this->runtime->renderer->render(
            $output,
            [
                'dryRun' => true,
                'requests' => array_map(
                    static fn (ApiRequest $request): array => $request->describe($connection, $authenticated),
                    $requests,
                ),
            ],
            OutputFormat::Json,
            InputReader::flag($input, 'compact'),
        );
    }
}
