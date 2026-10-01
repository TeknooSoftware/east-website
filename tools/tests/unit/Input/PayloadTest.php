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
use Teknoo\East\Website\Tools\Input\Payload;

/**
 * Tests of the body of a creation or of an update, and of the split of its blocks
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Payload::class)]
class PayloadTest extends TestCase
{
    public function testBodyContainsTheFieldsTheBlocksAndThePublication(): void
    {
        $payload = new Payload(['title' => 'T'], ['intro' => 'Hello', 'count' => 3], true);

        self::assertSame(
            ['title' => 'T', 'block_intro' => 'Hello', 'block_count' => 3, 'publish' => true],
            $payload->body(),
        );
    }

    public function testBodyDoesNotContainThePublicationWhenNotRequested(): void
    {
        $payload = new Payload(['title' => 'T'], [], false);

        self::assertSame(['title' => 'T'], $payload->body());
        self::assertSame([], $payload->second());
    }

    public function testFirstAndSecond(): void
    {
        $payload = new Payload(['title' => 'T', 'type' => 'ty'], ['intro' => 'Hello'], true);

        self::assertSame(['title' => 'T', 'type' => 'ty'], $payload->first());
        self::assertSame(['block_intro' => 'Hello', 'publish' => true], $payload->second());
    }

    public function testPublicationWithoutBlocksIsKeptInTheSecondBody(): void
    {
        $payload = new Payload([], [], true);

        self::assertFalse($payload->hasParts());
        self::assertSame(['publish' => true], $payload->second());
        self::assertSame(['publish' => true], $payload->body());
    }

    public function testFieldsWinOverTheSecondBodyInTheBody(): void
    {
        $payload = new Payload(['block_intro' => 'from fields'], ['intro' => 'from parts'], false);

        self::assertSame(['block_intro' => 'from fields'], $payload->body());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>, bool, bool}>
     */
    public static function twoStepsMatrix(): iterable
    {
        yield 'creation with parts' => [['title' => 'T'], ['intro' => 'x'], true, true];
        yield 'creation without parts' => [['title' => 'T'], [], true, false];
        yield 'update with parts and without type' => [['title' => 'T'], ['intro' => 'x'], false, false];
        yield 'update with parts and a type' => [['type' => 'ty'], ['intro' => 'x'], false, true];
        yield 'update with a type and without parts' => [['type' => 'ty'], [], false, false];
        yield 'update with a null type and parts' => [['type' => null], ['intro' => 'x'], false, true];
        yield 'update without anything' => [[], [], false, false];
        yield 'creation with a type and parts' => [['type' => 'ty'], ['intro' => 'x'], true, true];
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $parts
     */
    #[DataProvider('twoStepsMatrix')]
    public function testNeedsTwoSteps(array $fields, array $parts, bool $creation, bool $expected): void
    {
        self::assertSame($expected, (new Payload($fields, $parts, false))->needsTwoSteps($creation));
    }
}
