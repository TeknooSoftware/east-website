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

namespace Teknoo\East\Website\Tools\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Non fatal problems met during a command (like a new JWT that can not be written in the configuration file). They
 * are written on stderr after the command, so stdout stays a single JSON document.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Warnings
{
    /**
     * @var list<string>
     */
    private array $messages = [];

    public function add(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * Returns the messages and empties the collector, to attach them to an error document instead of writing them.
     *
     * @return list<string>
     */
    public function drain(): array
    {
        $messages = $this->messages;
        $this->messages = [];

        return $messages;
    }

    public function flush(OutputInterface $errors): void
    {
        foreach ($this->messages as $message) {
            $errors->writeln('warning: ' . $message, OutputInterface::OUTPUT_RAW);
        }

        $this->messages = [];
    }
}
