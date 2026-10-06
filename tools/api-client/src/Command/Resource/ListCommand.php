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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Resource\Operation;

use function in_array;
use function sprintf;
use function strtoupper;

/**
 * Lists the objects of a resource. The page size is fixed by the server, and the API has no filter.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ListCommand extends ResourceCommand
{
    protected static function operation(): Operation
    {
        return Operation::List;
    }

    protected function summary(): string
    {
        return sprintf('List the %s objects, page by page', $this->definition->name);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page to display, starting at 1 (fixed size)');
        $this->addOption('order', null, InputOption::VALUE_REQUIRED, 'Field used to sort, id by default');
        $this->addOption('direction', null, InputOption::VALUE_REQUIRED, 'Direction of the sort: ASC or DESC');
        $this->addDryRunOption();
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $query = $this->localeQuery($input);

        $page = InputReader::int($input, 'page');
        if (null !== $page) {
            if ($page < 1) {
                throw ApiException::usage('The page must be greater or equal to 1');
            }

            $query['page'] = $page;
        }

        $order = InputReader::string($input, 'order');
        if (null !== $order && '' !== $order) {
            $query['order'] = $order;
        }

        $direction = InputReader::string($input, 'direction');
        if (null !== $direction) {
            $direction = strtoupper($direction);
            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                throw ApiException::usage('The direction must be ASC or DESC');
            }

            $query['direction'] = $direction;
        }

        $params = $this->params($input);
        $request = $this->runtime->gateway->listRequest($connection, $this->definition, $params, $query);
        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$request]);

            return;
        }

        if ($this->isTui($input)) {
            $this->assertTui($input);
            $first = $this->runtime->client->call($connection, $request);
            $this->runtime->tui->browse($connection, $this->definition, $params, $query, $first);

            return;
        }

        $this->emit($input, $output, $this->runtime->client->call($connection, $request));
    }
}
