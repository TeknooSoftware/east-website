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

use Generator;
use JsonSerializable;
use Stringable;
use Teknoo\East\Website\Object\Environment\Exception\CyclicEnvironmentException;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentAlreadyDefinedException;
use Teknoo\East\Website\Object\Environment\Exception\InvalidEnvironmentNameException;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function preg_match;

/**
 * Value object representing an environment of the website (like `default`, `validation`, `testing`...), to publish
 * new versions of contents, posts and items on the same instance, visible only when the environment is selected.
 *
 * `default` is the root environment, without parent, always available. All other environments have a parent and are
 * declared in the DI under the key `teknoo.east.website.definitions.environments`. The chain of an environment is the
 * environment itself and all its ancestors until `default`.
 *
 * Instances are flyweights: a name always returns the same instance. Because objects are hydrated from the database
 * without access to the DI, an unknown name returns an instance too, with `default` as parent until its definition is
 * loaded; such an environment is never selectable by a visitor (the selection is checked against the DI definitions).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class Environment implements ImmutableInterface, Stringable, JsonSerializable
{
    use ImmutableTrait;

    public const string DEFAULT_NAME = 'default';

    private const string NAME_PATTERN = '/^[a-z0-9][a-z0-9_-]*$/';

    /**
     * @var array<string, self>
     */
    private static array $instances = [];

    /*
     * Set once, by `define()`. Null for the default environment, and for an environment not defined yet (hydrated
     * from the database before the definitions are loaded), whose parent is then the default environment.
     */
    private ?self $parent = null;

    private function __construct(
        private readonly string $name,
    ) {
        $this->uniqueConstructorCheck();
    }

    public static function default(): self
    {
        return self::get(self::DEFAULT_NAME);
    }

    /**
     * Return the environment instance for this name, create it when it does not exist yet.
     *
     * @throws InvalidEnvironmentNameException
     */
    public static function get(string $name): self
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        if (1 !== preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidEnvironmentNameException(
                "The environment name `$name` is not valid, it must match " . self::NAME_PATTERN
            );
        }

        return self::$instances[$name] = new self($name);
    }

    /**
     * Define an environment with its parent. Idempotent when the environment is already defined with the same parent.
     *
     * @throws InvalidEnvironmentNameException
     * @throws EnvironmentAlreadyDefinedException
     * @throws CyclicEnvironmentException
     */
    public static function define(string $name, self $parent): self
    {
        if (self::DEFAULT_NAME === $name) {
            throw new InvalidEnvironmentNameException('The default environment can not have a parent');
        }

        $environment = self::get($name);

        if (null !== $environment->parent) {
            if ($parent !== $environment->parent) {
                throw new EnvironmentAlreadyDefinedException(
                    "The environment `$name` is already defined with the parent `{$environment->parent->name}`"
                );
            }

            return $environment;
        }

        foreach ($parent->getChain() as $ancestor) {
            if ($ancestor === $name) {
                throw new CyclicEnvironmentException(
                    "The environment `$name` can not have `{$parent->name}` as parent, `$name` is one of its ancestors"
                );
            }
        }

        $environment->parent = $parent;

        return $environment;
    }

    /**
     * An environment is defined when it is `default` or when it has been declared with a parent via `define()`.
     */
    public static function isDefined(string $name): bool
    {
        return self::DEFAULT_NAME === $name || null !== (self::$instances[$name] ?? null)?->parent;
    }

    /**
     * Forget all instances and definitions. Only for tests: live objects would keep their instances, breaking the
     * identity of the flyweights.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$instances = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return self::DEFAULT_NAME === $this->name;
    }

    public function getParent(): ?self
    {
        if ($this->isDefault()) {
            return null;
        }

        return $this->parent ?? self::default();
    }

    /**
     * Names of this environment and of all its ancestors, from this environment to `default`.
     *
     * @return Generator<int, string>
     */
    public function getChain(): Generator
    {
        yield $this->name;

        $parent = $this->getParent();
        if (null !== $parent) {
            yield from $parent->getChain();
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
