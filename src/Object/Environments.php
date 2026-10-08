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

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentNotFoundException;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Traversable;

use function count;

/**
 * Read only collection of the environments defined for the website, indexed by their names, always with the default
 * environment. It is the service `teknoo.east.website.environments`, built from the DI definitions (an object, and not
 * a plain array, because a PHP-DI factory must return an object to be exposed as a Symfony service).
 *
 * @implements IteratorAggregate<string, Environment>
 * @implements ArrayAccess<string, Environment>
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class Environments implements IteratorAggregate, ArrayAccess, Countable, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @var array<string, Environment>
     */
    private readonly array $environments;

    /**
     * @param iterable<Environment> $environments
     */
    public function __construct(iterable $environments = [])
    {
        $this->uniqueConstructorCheck();

        $indexed = [Environment::DEFAULT_NAME => Environment::default()];
        foreach ($environments as $environment) {
            $indexed[$environment->getName()] = $environment;
        }

        $this->environments = $indexed;
    }

    public function has(string $name): bool
    {
        return isset($this->environments[$name]);
    }

    /**
     * @throws EnvironmentNotFoundException
     */
    public function get(string $name): Environment
    {
        return $this->environments[$name]
            ?? throw new EnvironmentNotFoundException("The environment `$name` is not defined", 404);
    }

    /**
     * @return array<string, Environment>
     */
    public function toArray(): array
    {
        return $this->environments;
    }

    /**
     * @return Traversable<string, Environment>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->environments);
    }

    public function count(): int
    {
        return count($this->environments);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->environments[$offset]);
    }

    public function offsetGet(mixed $offset): Environment
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('Environments are read only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Environments are read only');
    }
}
