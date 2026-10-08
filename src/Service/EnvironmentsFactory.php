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

namespace Teknoo\East\Website\Service;

use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environment\Exception\CyclicEnvironmentException;
use Teknoo\East\Website\Object\Environment\Exception\InvalidEnvironmentNameException;
use Teknoo\East\Website\Object\Environment\Exception\UnknownParentEnvironmentException;
use Teknoo\East\Website\Object\Environments;

use function array_key_exists;
use function get_debug_type;
use function is_array;
use function is_string;

/**
 * Build the environments of the website from the DI definitions:
 * - `teknoo.east.website.definitions.environments`: `['env-name' => 'parent-name or default', ...]`
 * - `teknoo.east.website.definitions.environments_access`: `['env-name' => ['ROLE_1', 'ROLE_2'], ...]`
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class EnvironmentsFactory
{
    /**
     * Define all environments from the definitions, in any order, and return them indexed by their name, with the
     * `default` environment first.
     *
     * @param array<mixed, mixed> $definitions
     *
     * @throws InvalidEnvironmentNameException
     * @throws UnknownParentEnvironmentException
     * @throws CyclicEnvironmentException
     */
    public static function fromDefinitions(array $definitions): Environments
    {
        $parents = [];
        foreach ($definitions as $name => $parent) {
            if (!is_string($name) || !is_string($parent)) {
                throw new InvalidEnvironmentNameException(
                    'Environments definitions must be an array of `string $name => string $parentName`, got `'
                    . get_debug_type($name) . ' => ' . get_debug_type($parent) . '`'
                );
            }

            if (Environment::DEFAULT_NAME === $name) {
                throw new InvalidEnvironmentNameException('The default environment can not be redefined');
            }

            $parents[$name] = $parent;
        }

        $environments = [Environment::DEFAULT_NAME => Environment::default()];
        $resolving = [];
        $resolve = static function (string $name) use (&$resolve, &$environments, &$resolving, $parents): Environment {
            if (isset($environments[$name])) {
                return $environments[$name];
            }

            if (!array_key_exists($name, $parents)) {
                throw new UnknownParentEnvironmentException("The environment `$name` is not defined");
            }

            if (isset($resolving[$name])) {
                throw new CyclicEnvironmentException("The environment `$name` is one of its own ancestors");
            }

            $resolving[$name] = true;
            $environment = Environment::define($name, $resolve($parents[$name]));
            unset($resolving[$name]);

            return $environments[$name] = $environment;
        };

        foreach ($parents as $name => $parent) {
            $resolve($name);
        }

        return new Environments($environments);
    }

    /**
     * Check the access definitions against the defined environments and return them normalized.
     *
     * @param array<mixed, mixed> $access
     * @return array<string, list<string>>
     *
     * @throws InvalidEnvironmentNameException
     * @throws UnknownParentEnvironmentException
     */
    public static function validateAccess(Environments $environments, array $access): array
    {
        $normalized = [];
        foreach ($access as $name => $roles) {
            if (!is_string($name) || !is_array($roles)) {
                throw new InvalidEnvironmentNameException(
                    'Environments access must be an array of `string $name => list<string> $roles`, got `'
                    . get_debug_type($name) . ' => ' . get_debug_type($roles) . '`'
                );
            }

            if (Environment::DEFAULT_NAME === $name) {
                throw new InvalidEnvironmentNameException('The default environment is always public');
            }

            if (!$environments->has($name)) {
                throw new UnknownParentEnvironmentException(
                    "The environment `$name` in the access definitions is not defined"
                );
            }

            $list = [];
            foreach ($roles as $role) {
                if (!is_string($role) || '' === $role) {
                    throw new InvalidEnvironmentNameException(
                        "Roles of the environment `$name` must be non empty strings, got `"
                        . get_debug_type($role) . '`'
                    );
                }

                $list[] = $role;
            }

            $normalized[$name] = $list;
        }

        return $normalized;
    }
}
