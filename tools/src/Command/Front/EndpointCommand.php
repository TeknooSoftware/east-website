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

namespace Teknoo\East\Website\Tools\Command\Front;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Resource\FrontEndpoint;
use Teknoo\East\Website\Tools\Runtime;

use function sprintf;

/**
 * Reads a public endpoint of the API (published contents and posts). The JWT is sent when available, because the
 * application can protect the whole API.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class EndpointCommand extends AbstractCommand
{
    public function __construct(
        Runtime $runtime,
        private readonly FrontEndpoint $endpoint,
    ) {
        parent::__construct($runtime, 'website:front:' . $endpoint->name);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription($this->endpoint->description);
        foreach ($this->endpoint->arguments as $argument) {
            $this->addArgument(
                $argument->name,
                null === $argument->default ? InputArgument::REQUIRED : InputArgument::OPTIONAL,
                $argument->description,
                $argument->default,
            );
        }

        if ($this->endpoint->paginated) {
            $this->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page to display, starting at 1');
        }

        $this->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale of the request (?locale=)');
        $this->addDryRunOption();
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $params = [];
        foreach ($this->endpoint->arguments as $argument) {
            $params[$argument->name] = InputReader::argument($input, $argument->name) ?? '';
        }

        $query = [];
        $locale = InputReader::string($input, 'locale');
        if (null !== $locale && '' !== $locale) {
            $query['locale'] = $locale;
        }

        $page = InputReader::int($input, 'page');
        if (null !== $page) {
            if ($page < 1) {
                throw ApiException::usage(sprintf('The page must be greater or equal to 1, %d given', $page));
            }

            $query['page'] = $page;
        }

        $request = ApiRequest::get($connection->endpoints->api($this->endpoint->template, $params), $query);
        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$request], !$connection->anonymous);

            return;
        }

        $this->emit($input, $output, $this->runtime->client->call($connection, $request, false));
    }
}
