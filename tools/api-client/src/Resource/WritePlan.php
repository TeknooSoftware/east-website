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

use Closure;
use Teknoo\East\Website\Tools\Http\ApiRequest;

/**
 * Requests of a creation or of an update. The second one exists only when the blocks of a content must be sent
 * apart, it needs the id of the object, known only after the first request for a creation.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class WritePlan
{
    /**
     * @param (Closure(string): ApiRequest)|null $second request sending the blocks, from the id of the object
     * @param string|null $id id of the object, null when it is known only after the first request
     */
    public function __construct(
        public readonly ApiRequest $first,
        public readonly ?Closure $second,
        public readonly bool $creation,
        public readonly ?string $id = null,
    ) {
    }

    public function hasTwoSteps(): bool
    {
        return null !== $this->second;
    }

    /**
     * @param string $placeholder id used in the second request when the id of the object is not yet known
     * @return list<ApiRequest>
     */
    public function requests(string $placeholder): array
    {
        if (null === $this->second) {
            return [$this->first];
        }

        return [$this->first, ($this->second)($this->id ?? $placeholder)];
    }
}
