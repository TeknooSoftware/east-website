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

namespace Teknoo\East\Website\Tools;

use Psr\Clock\ClockInterface;
use Teknoo\East\Website\Tools\Auth\Authenticator;
use Teknoo\East\Website\Tools\Config\ConnectionFactory;
use Teknoo\East\Website\Tools\Http\ApiClient;
use Teknoo\East\Website\Tools\Output\Renderer;
use Teknoo\East\Website\Tools\Output\Warnings;
use Teknoo\East\Website\Tools\Resource\Registry;

/**
 * Services shared by all the commands of the CLI.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Runtime
{
    public function __construct(
        public readonly ApiClient $client,
        public readonly Authenticator $authenticator,
        public readonly ConnectionFactory $connections,
        public readonly Renderer $renderer,
        public readonly Warnings $warnings,
        public readonly Registry $registry,
        public readonly ClockInterface $clock,
    ) {
    }
}
