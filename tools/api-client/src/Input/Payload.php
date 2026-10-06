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

namespace Teknoo\East\Website\Tools\Input;

use function array_key_exists;

/**
 * Body to send to create or update a resource. The parts (fields block_<name> of a Content or a Post) can be
 * accepted by the API only when the type of the object already exists, so they can be separated from the other
 * fields.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Payload
{
    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $parts value of the blocks, indexed by their name
     */
    public function __construct(
        public readonly array $fields,
        public readonly array $parts,
        public readonly bool $publish,
    ) {
    }

    public function hasParts(): bool
    {
        return [] !== $this->parts;
    }

    /**
     * The API ignores the parts in the request creating the object, or changing its type: two requests are needed.
     */
    public function needsTwoSteps(bool $creation): bool
    {
        return $this->hasParts() && ($creation || array_key_exists('type', $this->fields));
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->fields + $this->second();
    }

    /**
     * @return array<string, mixed>
     */
    public function first(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function second(): array
    {
        $body = [];
        foreach ($this->parts as $name => $value) {
            $body['block_' . $name] = $value;
        }

        if ($this->publish) {
            $body['publish'] = true;
        }

        return $body;
    }
}
