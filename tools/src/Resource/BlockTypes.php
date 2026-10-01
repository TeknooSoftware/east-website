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

use function array_keys;

/**
 * Types of blocks of a Type. The API expects the index of the choice of the Symfony form (BlockType), not the
 * value of the PHP enum: the order of the choices of the form is the reference, guarded by a test.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class BlockTypes
{
    public const array INDEXES = [
        'textarea' => '0',
        'raw' => '1',
        'text' => '2',
        'numeric' => '3',
        'image' => '4',
    ];

    /**
     * @return list<string>
     */
    public static function kinds(): array
    {
        return array_keys(self::INDEXES);
    }

    public static function index(string $kind): ?string
    {
        return self::INDEXES[$kind] ?? null;
    }
}
