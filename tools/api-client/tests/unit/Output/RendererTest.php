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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Json;
use Teknoo\East\Website\Tools\Output\OutputFormat;
use Teknoo\East\Website\Tools\Output\Renderer;

/**
 * Tests of the rendering of the documents of the CLI: JSON by default, tables for humans, and never altered by the formatter
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Renderer::class)]
class RendererTest extends TestCase
{
    /**
     * @param array<mixed> $document
     */
    private function render(array $document, OutputFormat $format, bool $compact = false, bool $decorated = false): string
    {
        $output = new BufferedOutput();
        $output->setDecorated($decorated);
        (new Renderer())->render($output, $document, $format, $compact);

        return $output->fetch();
    }

    public function testJsonIsPrettyPrintedByDefault(): void
    {
        $text = $this->render(['meta' => ['error' => false], 'data' => ['id' => 'a']], OutputFormat::Json);

        self::assertStringContainsString("\n    \"meta\": {", $text);
        self::assertSame(['meta' => ['error' => false], 'data' => ['id' => 'a']], Json::decode($text));
    }

    public function testCompactJsonIsOnASingleLine(): void
    {
        $text = $this->render(['meta' => ['error' => false], 'data' => ['id' => 'a']], OutputFormat::Json, true);

        self::assertSame("{\"meta\":{\"error\":false},\"data\":{\"id\":\"a\"}}\n", $text);
    }

    public function testContentIsNeverAlteredByTheFormatter(): void
    {
        $document = ['data' => ['html' => '<info>bold</info> [x] <comment>c</comment> \\<escaped> é / slash']];

        $text = $this->render($document, OutputFormat::Json, true, true);

        self::assertSame($document, Json::decode($text));
        self::assertStringContainsString('<info>bold</info> [x]', $text);
        self::assertStringContainsString('é / slash', $text);
        self::assertStringNotContainsString("\033", $text);
    }

    public function testATableIsDisplayedForAList(): void
    {
        $text = $this->render(
            [
                'meta' => ['page' => 2, 'totalPages' => 3, 'count' => 41],
                'data' => [
                    ['id' => 'a', 'name' => 'First', 'tags' => ['x', 'y'], 'hidden' => true, 'parent' => null],
                    ['id' => 'b', 'name' => 'Second', 'tags' => [], 'hidden' => false, 'parent' => 'a'],
                ],
            ],
            OutputFormat::Table,
        );

        self::assertStringContainsString('| id ', $text);
        self::assertStringContainsString('| name ', $text);
        self::assertStringContainsString('First', $text);
        self::assertStringContainsString('["x","y"]', $text);
        self::assertStringContainsString('true', $text);
        self::assertStringContainsString('false', $text);
        self::assertStringContainsString('page 2/3, 41 item(s)', $text);
        self::assertStringNotContainsString('"meta"', $text);
    }

    public function testATableIsDisplayedForASingleObject(): void
    {
        $text = $this->render(
            ['meta' => ['id' => 'a'], 'data' => ['id' => 'a', 'name' => 'First', 'parts' => ['intro' => 'x']]],
            OutputFormat::Table,
        );

        self::assertStringContainsString('| field', $text);
        self::assertStringContainsString('| value', $text);
        self::assertStringContainsString('| name', $text);
        self::assertStringContainsString('First', $text);
        self::assertStringContainsString('{"intro":"x"}', $text);
        self::assertStringNotContainsString('page ', $text);
    }

    public function testTheTableShowsTheContentWithoutInterpretingIt(): void
    {
        $text = $this->render(['data' => ['title' => '<info>Hello</info>']], OutputFormat::Table, false, true);

        self::assertStringContainsString('| <info>Hello</info> |', $text);
    }

    public function testTheFooterToleratesUnexpectedMeta(): void
    {
        $text = $this->render(
            ['meta' => ['page' => [1], 'totalPages' => 1, 'count' => 'many'], 'data' => [['id' => 'a']]],
            OutputFormat::Table,
        );

        self::assertStringContainsString('page ?/1, many item(s)', $text);
    }

    public function testARowWhichIsNotAnArrayHasEmptyCells(): void
    {
        $text = $this->render(['data' => [['id' => 'a'], 'scalar']], OutputFormat::Table);

        self::assertStringContainsString('| id ', $text);
        self::assertStringContainsString('| a  |', $text);
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function documentsWhichAreNotTables(): iterable
    {
        yield 'no data' => [['meta' => ['error' => false]]];
        yield 'null data' => [['meta' => ['error' => false], 'data' => null]];
        yield 'empty data' => [['meta' => ['error' => false], 'data' => []]];
        yield 'scalar data' => [['data' => 'text']];
        yield 'list of scalars' => [['data' => ['a', 'b']]];
    }

    /**
     * @param array<mixed> $document
     */
    #[DataProvider('documentsWhichAreNotTables')]
    public function testJsonIsUsedWhenTheDocumentCanNotBeDisplayedAsATable(array $document): void
    {
        $text = $this->render($document, OutputFormat::Table, true);

        self::assertSame(Json::encode($document) . "\n", $text);
    }

    public function testJsonFormatNeverDisplaysATable(): void
    {
        $text = $this->render(['data' => [['id' => 'a']]], OutputFormat::Json, true);

        self::assertSame("{\"data\":[{\"id\":\"a\"}]}\n", $text);
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function documentsWhichAreTables(): iterable
    {
        yield 'a list' => [[
            'meta' => ['page' => 2, 'totalPages' => 3, 'count' => 41],
            'data' => [
                ['id' => 'a', 'name' => 'First', 'tags' => ['x', 'y'], 'hidden' => true, 'parent' => null],
                ['id' => 'b', 'name' => '<info>Second</info>', 'tags' => [], 'hidden' => false, 'parent' => 'a'],
            ],
        ]];
        yield 'a list without meta' => [['data' => [['id' => 'a'], 'scalar']]];
        yield 'an object' => [[
            'meta' => ['id' => 'a'],
            'data' => ['id' => 'a', 'name' => 'First', 'parts' => ['intro' => 'x'], 'hidden' => false, 'parent' => null],
        ]];
    }

    /**
     * The interactive format has a screen only for some commands, the others print the table.
     *
     * @param array<mixed> $document
     */
    #[DataProvider('documentsWhichAreTables')]
    public function testTheInteractiveFormatRendersExactlyTheTable(array $document): void
    {
        $table = $this->render($document, OutputFormat::Table);

        self::assertStringContainsString('+--', $table);
        self::assertStringNotContainsString('"data"', $table);
        self::assertSame($table, $this->render($document, OutputFormat::Tui));
        self::assertSame($table, $this->render($document, OutputFormat::Tui, true));
        self::assertSame(
            $this->render($document, OutputFormat::Table, false, true),
            $this->render($document, OutputFormat::Tui, false, true),
        );
    }

    /**
     * @param array<mixed> $document
     */
    #[DataProvider('documentsWhichAreNotTables')]
    public function testJsonIsUsedWhenTheDocumentCanNotBeDisplayedByTheInteractiveFormat(array $document): void
    {
        self::assertSame(Json::encode($document) . "\n", $this->render($document, OutputFormat::Tui, true));
        self::assertSame(
            $this->render($document, OutputFormat::Table),
            $this->render($document, OutputFormat::Tui),
        );
        self::assertSame(
            $this->render($document, OutputFormat::Json),
            $this->render($document, OutputFormat::Tui),
        );
    }

    /**
     * @param array<mixed> $document
     */
    #[DataProvider('documentsWhichAreTables')]
    public function testJsonOutputIsUnchangedByTheOtherFormats(array $document): void
    {
        $compact = $this->render($document, OutputFormat::Json, true);
        $pretty = $this->render($document, OutputFormat::Json);

        self::assertSame(Json::encode($document) . "\n", $compact);
        self::assertSame(Json::encode($document, true) . "\n", $pretty);
        self::assertSame($document, Json::decode($compact));
        self::assertSame($document, Json::decode($pretty));
        self::assertStringNotContainsString('+--', $pretty);
        self::assertNotSame($pretty, $this->render($document, OutputFormat::Tui));
    }

    public function testErrorsAreRenderedAsACompactJsonDocument(): void
    {
        $output = new BufferedOutput();
        $exception = new ApiException('Validation failed', ErrorKind::Validation, 400, ['.name' => 'Invalid <info>']);

        (new Renderer())->error($output, $exception);

        self::assertSame(
            [
                'meta' => ['error' => true],
                'data' => [
                    'code' => 400,
                    'kind' => 'validation',
                    'message' => 'Validation failed',
                    'fields' => ['.name' => 'Invalid <info>'],
                ],
            ],
            Json::decode($output->fetch()),
        );
    }
}
