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

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\Json;

use function array_is_list;
use function array_keys;
use function is_array;
use function is_bool;
use function is_scalar;
use function sprintf;

/**
 * Writes the documents of the CLI. Everything is written raw, so the content of the website (HTML, [x],
 * <info>...) is never altered by the formatter of the Console component.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Renderer
{
    /**
     * @param array<mixed> $document
     */
    public function render(OutputInterface $output, array $document, OutputFormat $format, bool $compact = false): void
    {
        if (OutputFormat::Table === $format && $this->table($output, $document)) {
            return;
        }

        $output->writeln(Json::encode($document, !$compact), OutputInterface::OUTPUT_RAW);
    }

    public function error(OutputInterface $errors, ApiException $exception): void
    {
        $errors->writeln(Json::encode($exception->toArray()), OutputInterface::OUTPUT_RAW);
    }

    /**
     * @param array<mixed> $document
     * @return bool false when the document can not be displayed as a table
     */
    private function table(OutputInterface $output, array $document): bool
    {
        $data = $document['data'] ?? null;
        if (!is_array($data) || [] === $data) {
            return false;
        }

        $table = new Table($output);
        if (array_is_list($data)) {
            $first = $data[0] ?? null;
            if (!is_array($first)) {
                return false;
            }

            $columns = array_keys($first);
            $table->setHeaders($columns);
            foreach ($data as $row) {
                $cells = [];
                foreach ($columns as $column) {
                    $cells[] = $this->cell(is_array($row) ? ($row[$column] ?? null) : null);
                }

                $table->addRow($cells);
            }
        } else {
            $table->setHeaders(['field', 'value']);
            foreach ($data as $field => $value) {
                $table->addRow([(string) $field, $this->cell($value)]);
            }
        }

        $table->render();

        $meta = $document['meta'] ?? null;
        if (is_array($meta) && isset($meta['page'], $meta['totalPages'], $meta['count'])) {
            $output->writeln(
                sprintf(
                    'page %s/%s, %s item(s)',
                    is_scalar($meta['page']) ? $meta['page'] : '?',
                    is_scalar($meta['totalPages']) ? $meta['totalPages'] : '?',
                    is_scalar($meta['count']) ? $meta['count'] : '?',
                ),
                OutputInterface::OUTPUT_RAW,
            );
        }

        return true;
    }

    private function cell(mixed $value): string
    {
        $text = match (true) {
            null === $value => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => Json::encode($value),
        };

        return OutputFormatter::escape($text);
    }
}
