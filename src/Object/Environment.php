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

use JsonSerializable;
use Stringable;
use Teknoo\East\Website\Object\Environment\Exception\CyclicEnvironmentException;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentAlreadyDefinedException;
use Teknoo\East\Website\Object\Environment\Exception\InvalidEnvironmentNameException;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

use function array_keys;
use function preg_match;

/**
 * Value object representing an environment of the website (like `default`, `validation`, `testing`...), to publish
 * new versions of contents, posts and items on the same instance, visible only when the environment is selected.
 *
 * `default` is the root environment, without parent, always available. All other environments have a parent and are
 * declared in the DI under the key `teknoo.east.website.definitions.environments`. The chain of an environment is the
 * environment itself and all its ancestors until `default`.
 *
 * Instances are flyweights: an environment name always returns the same instance. Because objects are hydrated from
 * the database without access to the DI, an unknown name returns an instance too, with `default` as implicit parent,
 * but such an environment is never selectable by a visitor (the selection is checked against the DI definitions).
 * The parent relation is kept in the static registry, so the definitions can be loaded after a first hydration.
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

    /**
     * Parents explicitly defined, indexed by the name of the child
     *
     * @var array<string, string>
     */
    private static array $parents = [];

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
     * Return the environment instance for this name, create it when it does not exist yet. An environment created
     * here, without an explicit definition, has `default` as parent.
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

        if (isset(self::$parents[$name])) {
            if ($parent->name !== self::$parents[$name]) {
                throw new EnvironmentAlreadyDefinedException(
                    "The environment `$name` is already defined with the parent `" . self::$parents[$name] . '`'
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

        self::$parents[$name] = $parent->name;

        return $environment;
    }

    /**
     * An environment is defined when it is `default` or when it has been declared with a parent via `define()`.
     */
    public static function isDefined(string $name): bool
    {
        return self::DEFAULT_NAME === $name || isset(self::$parents[$name]);
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
        self::$parents = [];
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

        return self::get(self::$parents[$this->name] ?? self::DEFAULT_NAME);
    }

    /**
     * Names of this environment and of all its ancestors, from this environment to `default`.
     *
     * @return list<string>
     */
    public function getChain(): array
    {
        $chain = [];
        $current = $this;
        while (null !== $current) {
            if (isset($chain[$current->name])) {
                throw new CyclicEnvironmentException("The environment `{$current->name}` is one of its own ancestors");
            }

            $chain[$current->name] = true;
            $current = $current->getParent();
        }

        return array_keys($chain);
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
