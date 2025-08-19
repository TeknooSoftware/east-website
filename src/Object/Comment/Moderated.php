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

namespace Teknoo\East\Website\Object\Comment;

use Teknoo\East\Website\Object\Comment;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * Comment's state representing a published moderated comment instance. Public author, title and comment represents
 * moderated values.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @mixin Comment
 */
class Moderated implements StateInterface
{
    use StateTrait;

    public function isModerated(): callable
    {
        return function (): bool {
            return true;
        };
    }

    public function getPublicAuthor(): callable
    {
        return function (): string {
            return (string) $this->getModeratedAuthor();
        };
    }

    public function getPublicTitle(): callable
    {
        return function (): string {
            return (string) $this->getModeratedTitle();
        };
    }

    public function getPublicContent(): callable
    {
        return function (): string {
            return (string) $this->getModeratedContent();
        };
    }
}
