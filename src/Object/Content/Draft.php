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

namespace Teknoo\East\Website\Object\Content;

use DateTimeInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * Content's state representing a content instance not published, aka a draft. The methode "setPublishedAt" is provided
 * only in this state. So, a published content can not be republished (but can be updated).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @mixin Content
 */
class Draft implements StateInterface
{
    use StateTrait;

    public function publishingAt(): callable
    {
        return function (DateTimeInterface $dateTime): self {
            $this->publishedAt = $dateTime;

            $this->updateStates();

            return $this;
        };
    }
}
