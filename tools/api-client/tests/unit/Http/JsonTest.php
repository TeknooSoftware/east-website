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

namespace Teknoo\Tests\East\Website\Tools\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Http\Json;

/**
 * Tests of the strict JSON helpers
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Json::class)]
class JsonTest extends TestCase
{
    public function testDecodeObject(): void
    {
        self::assertSame(['a' => 1, 'b' => ['c' => true]], Json::decode('{"a":1,"b":{"c":true}}'));
    }

    public function testDecodeList(): void
    {
        self::assertSame([1, 2, 3], Json::decode('[1,2,3]'));
    }

    public function testDecodeEmptyAndBlankStringsGiveNull(): void
    {
        self::assertNull(Json::decode(''));
        self::assertNull(Json::decode("  \n "));
    }

    public function testDecodeInvalidJsonGivesNull(): void
    {
        self::assertNull(Json::decode('{"a":'));
        self::assertNull(Json::decode('<html>Bad gateway</html>'));
    }

    public function testDecodeScalarGivesNull(): void
    {
        self::assertNull(Json::decode('3'));
        self::assertNull(Json::decode('"text"'));
        self::assertNull(Json::decode('null'));
    }

    public function testEncodeKeepsUnicodeAndSlashes(): void
    {
        self::assertSame('{"path":"/api/v1","name":"Déloge"}', Json::encode(['path' => '/api/v1', 'name' => 'Déloge']));
    }

    public function testEncodePretty(): void
    {
        self::assertSame("{\n    \"a\": 1\n}", Json::encode(['a' => 1], true));
    }

    public function testEncodeSubstitutesInvalidUtf8(): void
    {
        self::assertSame("\"a\u{FFFD}b\"", Json::encode("a\xFFb"));
    }

    public function testEncodeObjectWithEmptyArrayGivesAnEmptyObject(): void
    {
        self::assertSame('{}', Json::encodeObject([]));
    }

    public function testEncodeObjectWithData(): void
    {
        self::assertSame('{"a":"b"}', Json::encodeObject(['a' => 'b']));
    }
}
