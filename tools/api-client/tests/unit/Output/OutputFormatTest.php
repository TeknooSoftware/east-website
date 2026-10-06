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
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Output\OutputFormat;

/**
 * Tests of the output formats of the commands
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(OutputFormat::class)]
class OutputFormatTest extends TestCase
{
    public function testJsonIsTheDefaultFormat(): void
    {
        self::assertSame(OutputFormat::Json, OutputFormat::fromOption(null));
    }

    public function testKnownFormats(): void
    {
        self::assertSame(OutputFormat::Json, OutputFormat::fromOption('json'));
        self::assertSame(OutputFormat::Table, OutputFormat::fromOption('table'));
        self::assertSame(OutputFormat::Tui, OutputFormat::fromOption('tui'));
    }

    public function testUnknownFormatIsAUsageError(): void
    {
        try {
            OutputFormat::fromOption('yaml');
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertSame('The format "yaml" is not supported, use one of: json, table, tui', $error->getMessage());
        }
    }
}
