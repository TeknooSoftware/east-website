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
 * Updates an object. Only the provided options are sent, the other fields are left untouched.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class UpdateCommand extends WriteCommand
{
    protected static function operation(): Operation
    {
        return Operation::Update;
    }

    protected function summary(): string
    {
        return sprintf('Update a %s, only the provided fields are changed', $this->definition->label);
    }

    protected function isCreation(): bool
    {
        return false;
    }

    protected function openForm(Connection $connection, array $params, array $query, Payload $payload): void
    {
        // The form is filled with the current object: without it nothing is opened, the failure is the usual one
        $document = $this->runtime->client->call(
            $connection,
            $this->runtime->gateway->getRequest($connection, $this->definition, $params, $query),
        );

        $this->runtime->tui->edit($connection, $this->definition, $params, $query, $document, $payload);
    }
}
