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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\DataSource;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function fopen;
use function fwrite;
use function rewind;

/**
 * Tests of the raw JSON payload of the commands (--data, --data-file)
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DataSource::class)]
class DataSourceTest extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    private function input(array $parameters): ArrayInput
    {
        $command = new Command('test');
        DataSource::configure($command);

        return new ArrayInput($parameters, $command->getDefinition());
    }

    public function testConfigureAddsTheOptions(): void
    {
        $command = new Command('test');
        DataSource::configure($command);

        self::assertTrue($command->getDefinition()->hasOption('data'));
        self::assertTrue($command->getDefinition()->hasOption('data-file'));
    }

    public function testReadReturnsAnEmptyPayloadWithoutData(): void
    {
        self::assertSame([], DataSource::read($this->input([])));
    }

    public function testReadInlineJson(): void
    {
        self::assertSame(
            ['title' => 'T', 'count' => 2],
            DataSource::read($this->input(['--data' => '{"title":"T","count":2}'])),
        );
    }

    public function testReadStdin(): void
    {
        $input = $this->input(['--data' => '-']);
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, '{"title":"from stdin"}');
        rewind($stream);
        $input->setStream($stream);

        self::assertSame(['title' => 'from stdin'], DataSource::read($input));
    }

    public function testReadFile(): void
    {
        $temp = new TempDir();
        $file = $temp->write('payload.json', '{"title":"from file"}');

        self::assertSame(['title' => 'from file'], DataSource::read($this->input(['--data-file' => $file])));
        $temp->remove();
    }

    public function testReadAnEmptyObjectAndAnEmptyList(): void
    {
        self::assertSame([], DataSource::read($this->input(['--data' => '{}'])));
        self::assertSame([], DataSource::read($this->input(['--data' => '[]'])));
    }

    public function testReadCastsNumericKeysToStrings(): void
    {
        self::assertSame(['1' => 'a'], DataSource::read($this->input(['--data' => '{"1":"a"}'])));
    }

    public function testDataAndDataFileAreMutuallyExclusive(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('mutually exclusive');

        DataSource::read($this->input(['--data' => '{}', '--data-file' => 'file.json']));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'invalid json' => ['{"title":'];
        yield 'scalar' => ['"text"'];
        yield 'number' => ['12'];
        yield 'list' => ['["a","b"]'];
        yield 'blank' => ['   '];
    }

    #[DataProvider('invalidPayloads')]
    public function testReadRejectsAnInvalidPayload(string $payload): void
    {
        try {
            DataSource::read($this->input(['--data' => $payload]));
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertSame('The payload must be a valid JSON object', $error->getMessage());
        }
    }

    public function testReadRejectsAMissingFile(): void
    {
        $this->expectException(ApiException::class);

        DataSource::read($this->input(['--data-file' => '/nonexistent/payload.json']));
    }
}
