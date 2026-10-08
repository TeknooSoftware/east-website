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

namespace Teknoo\East\Website\Doctrine\Types;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use Teknoo\East\Website\Object\Environment;

use function get_debug_type;
use function is_string;

/**
 * Doctrine ODM type to store an `Environment` value object as a plain string (its name) in MongoDB, and to restore
 * the flyweight instance from the name. In queries, the type also accepts names (strings) and `null`, used to match
 * documents created before the environments feature.
 *
 * Registered under the name `environment` by the Symfony bundle (`doctrine_mongodb.types`) or by the repositories
 * factories of `infrastructures/doctrine/di.php`.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class EnvironmentType extends Type
{
    use ClosureToPHP;

    final public const string NAME = 'environment';

    /**
     * Register this type in Doctrine ODM when it is not already registered
     */
    public static function register(): void
    {
        if (!Type::hasType(self::NAME)) {
            Type::addType(self::NAME, self::class);
        }
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function convertToDatabaseValue($value)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Environment) {
            return $value->getName();
        }

        if (is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            'An environment must be an instance of ' . Environment::class . ' or a name, got ' . get_debug_type($value)
        );
    }

    /**
     * @param mixed $value
     * @return Environment|null
     */
    public function convertToPHPValue($value)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Environment) {
            return $value;
        }

        if (is_string($value)) {
            return Environment::get($value);
        }

        throw new InvalidArgumentException(
            'An environment must be stored as a string in the database, got ' . get_debug_type($value)
        );
    }
}
