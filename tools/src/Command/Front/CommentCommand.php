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
use Teknoo\East\Website\Tools\Runtime;

use function sprintf;

/**
 * Posts a comment on a published blog post. The API answers with a redirection that is not followed (its target
 * is the post), the id of the new comment is read from it.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CommentCommand extends AbstractCommand
{
    private const array REQUIRED = ['author', 'title', 'content'];

    public function __construct(Runtime $runtime)
    {
        parent::__construct($runtime, 'website:front:comment:create');
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Post a comment on a published blog post');
        $this->addArgument('post-slug', InputArgument::REQUIRED, 'Slug of the blog post');
        $this->addOption('author', null, InputOption::VALUE_REQUIRED, 'Author of the comment (required)');
        $this->addOption('title', null, InputOption::VALUE_REQUIRED, 'Title of the comment (required)');
        $this->addOption('content', null, InputOption::VALUE_REQUIRED, 'Content of the comment (required)');
        $this->addDryRunOption();
    }

    protected function perform(InputInterface $input, OutputInterface $output, Connection $connection): void
    {
        $body = [];
        foreach (self::REQUIRED as $field) {
            $value = InputReader::string($input, $field);
            if (null === $value || '' === $value) {
                throw ApiException::usage(sprintf('The option --%s is required', $field));
            }

            $body[$field] = $value;
        }

        $slug = InputReader::argument($input, 'post-slug') ?? '';
        $path = $connection->endpoints->api('post/{slug}/comment', ['slug' => $slug]);
        $request = ApiRequest::json('POST', $path, $body);

        if ($this->isDryRun($input)) {
            $this->dryRun($input, $output, $connection, [$request], !$connection->anonymous);

            return;
        }

        $this->emit($input, $output, $this->runtime->client->create($connection, $request, false, false));
    }
}
