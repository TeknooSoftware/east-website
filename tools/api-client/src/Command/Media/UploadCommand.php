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

namespace Teknoo\East\Website\Tools\Command\Media;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Runtime;

use function basename;

/**
 * Uploads a file as a media (multipart request, the only way to create a media). The HTTP client detects the type of
 * the file from its content with symfony/mime, and the server stores it as the type of the media (sent back when the
 * media is served). A media can not be updated: delete it and create it again.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class UploadCommand extends AbstractCommand
{
    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:media:create');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Upload a file as a media and print it');
        $this->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path of the file to upload (required)');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the media, the file name by default');
        $this->addOption('alternative', null, InputOption::VALUE_REQUIRED, 'Alternative text of the media');
        $this->addDryRunOption();
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $file = InputReader::string($input, 'file');
        if (null === $file || '' === $file) {
            throw ApiException::usage('The option --file is required');
        }

        $fields = ['media[name]' => InputReader::string($input, 'name') ?? basename($file)];
        $alternative = InputReader::string($input, 'alternative');
        if (null !== $alternative) {
            $fields['media[alternative]'] = $alternative;
        }

        $request = ApiRequest::multipart($connection->endpoints->admin('media/new'), $fields, 'media[image]', $file);
        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$request]);

            return;
        }

        $this->emit($input, $output, $this->runtime->client->create($connection, $request));
    }
}
