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
use Teknoo\East\Website\Tools\Http\ApiRequest;
use Teknoo\East\Website\Tools\Resource\WritePlan;

/**
 * Tests of the requests of a creation or of an update
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(WritePlan::class)]
class WritePlanTest extends TestCase
{
    public function testASingleStepPlanHasOnlyItsFirstRequest(): void
    {
        $first = ApiRequest::json('POST', '/admin/tag/new', ['name' => 'php']);
        $plan = new WritePlan($first, null, true);

        self::assertFalse($plan->hasTwoSteps());
        self::assertTrue($plan->creation);
        self::assertNull($plan->id);
        self::assertSame([$first], $plan->requests('PLACEHOLDER'));
    }

    public function testTheSecondRequestOfACreationUsesThePlaceholder(): void
    {
        $first = ApiRequest::json('POST', '/admin/content/new', ['title' => 'T']);
        $second = static fn (string $id): ApiRequest => ApiRequest::json('PUT', '/admin/content/' . $id, ['block_a' => 'x']);
        $plan = new WritePlan($first, $second, true);

        self::assertTrue($plan->hasTwoSteps());

        $requests = $plan->requests('PLACEHOLDER');
        self::assertCount(2, $requests);
        self::assertSame($first, $requests[0]);
        self::assertSame('/admin/content/PLACEHOLDER', $requests[1]->path());
    }

    public function testTheSecondRequestOfAnUpdateUsesTheKnownId(): void
    {
        $first = ApiRequest::json('PUT', '/admin/content/c1', ['type' => 't1']);
        $second = static fn (string $id): ApiRequest => ApiRequest::json('PUT', '/admin/content/' . $id, ['block_a' => 'x']);
        $plan = new WritePlan($first, $second, false, 'c1');

        self::assertFalse($plan->creation);
        self::assertSame('c1', $plan->id);
        self::assertSame('/admin/content/c1', $plan->requests('PLACEHOLDER')[1]->path());
    }
}
