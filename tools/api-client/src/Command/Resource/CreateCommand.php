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

use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Resource\Operation;

use function sprintf;

/**
 * Creates an object. The API answers with a redirection to the created object, the command fetches it and prints
 * it, so the id of the new object is available in the result.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CreateCommand extends WriteCommand
{
    protected static function operation(): Operation
    {
        return Operation::Create;
    }

    protected function summary(): string
    {
        return sprintf('Create a %s and print it', $this->definition->label);
    }

    protected function isCreation(): bool
    {
        return true;
    }

    protected function openForm(Connection $connection, array $params, array $query, Payload $payload): void
    {
        $this->runtime->tui->create($connection, $this->definition, $params, $query, $payload);
    }
}
