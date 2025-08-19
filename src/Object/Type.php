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

namespace Teknoo\East\Website\Object;

use Stringable;
use Teknoo\East\Common\Contracts\Object\DeletableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Object\ObjectTrait;

use function array_keys;
use function array_map;
use function array_values;

/**
 * Class to define persisted types of dynamics contents and parts of this pages. A type is defined by a name, a template
 * to use to render the dynamic content and a list of Block instance to define each part
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Type implements IdentifiedObjectInterface, DeletableInterface, TimestampableInterface, Stringable
{
    use ObjectTrait;

    private string $name = '';

    private string $template = '';

    /**
     * @var array<string, string>
     */
    private array $blocks = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return array<Block>
     */
    public function getBlocks(): array
    {
        return array_map(
            static fn ($key, $value): Block => new Block($key, BlockType::from($value)),
            array_keys($this->blocks),
            array_values($this->blocks)
        );
    }

    /**
     * @param Block[] $blocks
     */
    public function setBlocks(array $blocks): self
    {
        $this->blocks = [];

        foreach ($blocks as $block) {
            $this->blocks[$block->getName()] = $block->getType()->value;
        }

        return $this;
    }
}
