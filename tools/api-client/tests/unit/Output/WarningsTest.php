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

namespace Teknoo\Tests\East\Website\Tools\Output;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Teknoo\East\Website\Tools\Output\Warnings;

/**
 * Tests of the collector of the non fatal problems
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Warnings::class)]
class WarningsTest extends TestCase
{
    public function testItStartsEmpty(): void
    {
        self::assertSame([], (new Warnings())->all());
    }

    public function testMessagesAreKeptInOrder(): void
    {
        $warnings = new Warnings();
        $warnings->add('first');
        $warnings->add('second');

        self::assertSame(['first', 'second'], $warnings->all());
    }

    public function testDrainReturnsTheMessagesAndEmptiesTheCollector(): void
    {
        $warnings = new Warnings();
        $warnings->add('first');
        $warnings->add('second');

        self::assertSame(['first', 'second'], $warnings->drain());
        self::assertSame([], $warnings->all());
        self::assertSame([], $warnings->drain());
    }

    public function testFlushWritesTheMessagesOnTheOutputAndEmptiesTheCollector(): void
    {
        $warnings = new Warnings();
        $warnings->add('the session can not be stored');
        $warnings->add('<info>raw</info>');
        $output = new BufferedOutput();
        $output->setDecorated(true);

        $warnings->flush($output);

        self::assertSame(
            "warning: the session can not be stored\nwarning: <info>raw</info>\n",
            $output->fetch(),
        );
        self::assertSame([], $warnings->all());

        $warnings->flush($output);
        self::assertSame('', $output->fetch());
    }
}
