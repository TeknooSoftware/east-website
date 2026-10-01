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

namespace Teknoo\Tests\East\Website\Tools\Resource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Resource\BlockTypes;

use function array_keys;
use function array_map;
use function dirname;
use function file_get_contents;
use function is_file;
use function preg_match;
use function preg_match_all;
use function strtolower;

/**
 * Tests of the types of blocks of a Type, which are the indexes of the choices of the Symfony form and not the values of the PHP enum
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(BlockTypes::class)]
class BlockTypesTest extends TestCase
{
    public function testKinds(): void
    {
        self::assertSame(['textarea', 'raw', 'text', 'numeric', 'image'], BlockTypes::kinds());
    }

    public function testIndex(): void
    {
        self::assertSame('0', BlockTypes::index('textarea'));
        self::assertSame('1', BlockTypes::index('raw'));
        self::assertSame('2', BlockTypes::index('text'));
        self::assertSame('3', BlockTypes::index('numeric'));
        self::assertSame('4', BlockTypes::index('image'));
        self::assertNull(BlockTypes::index('video'));
        self::assertNull(BlockTypes::index('Text'));
    }

    /**
     * The API expects the index of the choice of the Symfony form BlockType, the order of the PHP enum BlockType
     * (textarea, raw, numeric, text, image) is different: this guard fails if the form changes.
     */
    public function testIndexesMatchTheChoicesOfTheSymfonyForm(): void
    {
        $file = dirname(__DIR__, 4) . '/infrastructures/symfony/Form/Type/BlockType.php';
        if (!is_file($file)) {
            self::markTestSkipped('The form of the library is not available next to the CLI');
        }

        $source = (string) file_get_contents($file);
        self::assertSame(1, preg_match("/'choices'\\s*=>\\s*\\[(.*?)\\]/s", $source, $choices), 'choices not found');

        self::assertGreaterThan(
            0,
            preg_match_all('/=>\s*BlockTypeEnum::(\w+)/', $choices[1], $matches),
            'no choice found in the form',
        );

        $expected = [];
        foreach (array_map(strtolower(...), $matches[1]) as $index => $kind) {
            $expected[$kind] = (string) $index;
        }

        self::assertSame($expected, BlockTypes::INDEXES);
        self::assertSame(array_keys($expected), BlockTypes::kinds());
    }
}
