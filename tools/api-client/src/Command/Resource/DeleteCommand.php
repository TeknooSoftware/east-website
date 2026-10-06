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

namespace Teknoo\East\Website\Tools\Command\Resource;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Resource\Operation;

use function defined;
use function sprintf;
use function stream_isatty;

/**
 * Deletes an object (a soft deletion on the server). A confirmation is asked only on an interactive terminal,
 * --yes or --no-interaction skip it: a script or an agent is never blocked.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DeleteCommand extends ResourceCommand
{
    protected static function operation(): Operation
    {
        return Operation::Delete;
    }

    protected function summary(): string
    {
        return sprintf('Delete a %s from its id', $this->definition->label);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Do not ask any confirmation');
        $this->addDryRunOption();
    }

    protected function isTerminal(): bool
    {
        return defined('STDIN') && stream_isatty(STDIN);
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $params = $this->params($input);
        $request = $this->runtime->gateway->deleteRequest($connection, $this->definition, $params);
        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$request]);

            return;
        }

        if (!InputReader::flag($input, 'yes') && $input->isInteractive() && $this->isTerminal()) {
            $question = new ConfirmationQuestion(
                sprintf('Delete the %s "%s"? [y/N] ', $this->definition->label, $params['id'] ?? ''),
                false,
            );

            $helper = new QuestionHelper();
            if (true !== $helper->ask($input, $this->errorOutput($output), $question)) {
                $this->emit($input, $output, ['meta' => ['error' => false, 'deleted' => false], 'data' => null]);

                return;
            }
        }

        $this->emit($input, $output, $this->runtime->client->call($connection, $request));
    }
}
