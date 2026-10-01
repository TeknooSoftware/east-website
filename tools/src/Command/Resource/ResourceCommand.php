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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Runtime;

use function sprintf;

/**
 * Base of the generic commands (website:<resource>:<operation>) of the admin API, configured by the definition of
 * the resource.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class ResourceCommand extends AbstractCommand
{
    public function __construct(
        Runtime $runtime,
        protected readonly ResourceDefinition $definition,
    ) {
        parent::__construct($runtime, 'website:' . $definition->name . ':' . static::operation()->value);
    }

    abstract protected static function operation(): Operation;

    abstract protected function summary(): string;

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription($this->summary());
        foreach ($this->definition->parents as $parent) {
            $this->addArgument($parent, InputArgument::REQUIRED, 'Id of the parent of the ' . $this->definition->label);
        }

        if (Operation::List !== static::operation() && Operation::Create !== static::operation()) {
            $this->addArgument('id', InputArgument::REQUIRED, 'Id of the ' . $this->definition->label);
        }

        if ($this->definition->translatable && Operation::Delete !== static::operation()) {
            $this->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale of the request (?locale=)');
        }
    }

    /**
     * @return array<string, string> values of the placeholders of the paths
     */
    protected function params(InputInterface $input): array
    {
        $params = [];
        foreach ($this->definition->parents as $parent) {
            $params[$parent] = $this->identifier($input, $parent);
        }

        if ($input->hasArgument('id')) {
            $params['id'] = $this->identifier($input, 'id');
        }

        return $params;
    }

    private function identifier(InputInterface $input, string $name): string
    {
        $value = InputReader::argument($input, $name);
        if (null === $value || '' === $value) {
            throw ApiException::usage(sprintf('The argument "%s" can not be empty', $name));
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    protected function localeQuery(InputInterface $input): array
    {
        $locale = InputReader::string($input, 'locale');

        return null !== $locale && '' !== $locale ? ['locale' => $locale] : [];
    }
}
