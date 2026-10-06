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

namespace Teknoo\East\Website\Tools\Resource;

use Symfony\Component\Console\Input\InputOption;

use function preg_replace;
use function strtolower;

/**
 * Field of a resource sent to the API. The name of the console option is the kebab-case of the JSON name, unless
 * defined.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FieldDefinition
{
    /**
     * @param list<string> $choices accepted values, empty when free
     * @param string|null $target name of the resource referenced by an id, to choose it in the interactive mode
     * @param bool $multiline true when the text is edited on several lines in the interactive mode
     */
    public function __construct(
        public readonly string $name,
        public readonly FieldKind $kind,
        public readonly string $description,
        private readonly ?string $option = null,
        public readonly array $choices = [],
        public readonly ?string $target = null,
        public readonly bool $multiline = false,
    ) {
    }

    public function optionName(): string
    {
        return $this->option ?? strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $this->name));
    }

    public function inputOption(): InputOption
    {
        $description = $this->description;

        return match ($this->kind) {
            FieldKind::Bool => new InputOption($this->optionName(), null, InputOption::VALUE_NEGATABLE, $description),
            FieldKind::IdList, FieldKind::StringList, FieldKind::Blocks => new InputOption(
                $this->optionName(),
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $description . ' (repeatable, use the option with an empty value to clear the list)',
            ),
            default => new InputOption($this->optionName(), null, InputOption::VALUE_REQUIRED, $description),
        };
    }
}
