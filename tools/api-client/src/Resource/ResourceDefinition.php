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

use function in_array;

/**
 * Description of a resource of the admin API: its paths, its fields, and the operations it supports. The generic
 * commands are built from it.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ResourceDefinition
{
    /**
     * @param list<string> $parents names of the arguments identifying the parent of the resource, used in the paths
     * @param list<FieldDefinition> $fields
     * @param list<Operation> $operations
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $basePath,
        public readonly string $listPath,
        public readonly array $fields,
        public readonly array $operations,
        public readonly array $parents = [],
        public readonly bool $translatable = false,
        public readonly bool $hasParts = false,
    ) {
    }

    public function supports(Operation $operation): bool
    {
        return in_array($operation, $this->operations, true);
    }

    public function createPath(): string
    {
        return $this->basePath . '/new';
    }

    public function itemPath(): string
    {
        return $this->basePath . '/{id}';
    }

    public function deletePath(): string
    {
        return $this->basePath . '/{id}/delete';
    }
}
