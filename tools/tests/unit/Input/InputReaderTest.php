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

namespace Teknoo\Tests\East\Website\Tools\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\InputReader;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function fopen;
use function fwrite;
use function rewind;

/**
 * Tests of the typed reading of the console input
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(InputReader::class)]
class InputReaderTest extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    private function input(array $parameters): ArrayInput
    {
        return new ArrayInput($parameters, new InputDefinition([
            new InputOption('name', null, InputOption::VALUE_REQUIRED),
            new InputOption('count', null, InputOption::VALUE_REQUIRED),
            new InputOption('flag', null, InputOption::VALUE_NONE),
            new InputOption('hidden', null, InputOption::VALUE_NEGATABLE),
            new InputOption('tag', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
            new InputArgument('id', InputArgument::OPTIONAL),
        ]));
    }

    public function testStringReturnsTheProvidedValue(): void
    {
        self::assertSame('x', InputReader::string($this->input(['--name' => 'x']), 'name'));
    }

    public function testStringKeepsAnEmptyValue(): void
    {
        self::assertSame('', InputReader::string($this->input(['--name' => '']), 'name'));
    }

    public function testStringIsNullWhenNotProvidedOrNotDefined(): void
    {
        self::assertNull(InputReader::string($this->input([]), 'name'));
        self::assertNull(InputReader::string($this->input([]), 'unknown'));
    }

    public function testStringIsNullForAnArrayOption(): void
    {
        self::assertNull(InputReader::string($this->input(['--tag' => ['a']]), 'tag'));
    }

    public function testFlag(): void
    {
        self::assertTrue(InputReader::flag($this->input(['--flag' => true]), 'flag'));
        self::assertFalse(InputReader::flag($this->input([]), 'flag'));
        self::assertFalse(InputReader::flag($this->input([]), 'unknown'));
    }

    public function testBoolIsATriState(): void
    {
        self::assertTrue(InputReader::bool($this->input(['--hidden' => true]), 'hidden'));
        self::assertFalse(InputReader::bool($this->input(['--no-hidden' => true]), 'hidden'));
        self::assertNull(InputReader::bool($this->input([]), 'hidden'));
        self::assertNull(InputReader::bool($this->input([]), 'unknown'));
    }

    public function testBoolIsNullForAnOptionWhichIsNotABoolean(): void
    {
        self::assertNull(InputReader::bool($this->input(['--name' => 'x']), 'name'));
    }

    public function testInt(): void
    {
        self::assertSame(12, InputReader::int($this->input(['--count' => '12']), 'count'));
        self::assertSame(-5, InputReader::int($this->input(['--count' => '-5']), 'count'));
        self::assertNull(InputReader::int($this->input([]), 'count'));
    }

    public function testIntRejectsANonInteger(): void
    {
        try {
            InputReader::int($this->input(['--count' => 'abc']), 'count');
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('--count expects an integer', $error->getMessage());
        }
    }

    public function testListKeepsTheEmptyValueToClearTheList(): void
    {
        self::assertSame(['a', 'b'], InputReader::list($this->input(['--tag' => ['a', 'b']]), 'tag'));
        self::assertSame([''], InputReader::list($this->input(['--tag' => ['']]), 'tag'));
        self::assertSame([], InputReader::list($this->input([]), 'tag'));
        self::assertSame([], InputReader::list($this->input([]), 'unknown'));
    }

    public function testListIsEmptyForAnOptionWhichIsNotAnArray(): void
    {
        self::assertSame([], InputReader::list($this->input(['--name' => 'x']), 'name'));
    }

    public function testArgument(): void
    {
        self::assertSame('abc', InputReader::argument($this->input(['id' => 'abc']), 'id'));
        self::assertNull(InputReader::argument($this->input([]), 'id'));
        self::assertNull(InputReader::argument($this->input([]), 'unknown'));
    }

    public function testContentReadsAFile(): void
    {
        $temp = new TempDir();
        $file = $temp->write('secret.txt', "value\n");

        self::assertSame("value\n", InputReader::content($this->input([]), $file));
        $temp->remove();
    }

    public function testContentReadsTheStreamOfTheInputForADash(): void
    {
        $input = $this->input([]);
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, 'from stdin');
        rewind($stream);
        $input->setStream($stream);

        self::assertSame('from stdin', InputReader::content($input, '-'));
    }

    public function testContentRejectsAMissingFile(): void
    {
        try {
            InputReader::content($this->input([]), '/nonexistent/file.txt');
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString('does not exist or is not readable', $error->getMessage());
        }
    }

    public function testContentRejectsADirectory(): void
    {
        $temp = new TempDir();

        try {
            InputReader::content($this->input([]), $temp->path());
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
        } finally {
            $temp->remove();
        }
    }
}
