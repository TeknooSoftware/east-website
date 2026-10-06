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
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Resource\ResourceGateway;
use Teknoo\Tests\East\Website\Tools\Support\ApiStub;

/**
 * Tests of the requests of the admin API shared by the commands and by the interactive mode
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ResourceGateway::class)]
class ResourceGatewayTest extends TestCase
{
    private function definition(string $name): ResourceDefinition
    {
        $definition = (new Registry())->resource($name);
        self::assertInstanceOf(ResourceDefinition::class, $definition);

        return $definition;
    }

    public function testListGetAndDeleteRequests(): void
    {
        $api = new ApiStub();
        $comment = $this->definition('comment');

        $list = $api->gateway->listRequest($api->connection, $comment, ['post-id' => 'p1'], ['page' => 2]);
        self::assertSame('GET', $list->method());
        self::assertSame('/api/v1/admin/post/p1/comments', $list->path());
        self::assertSame(['page' => 2], $list->query());

        $get = $api->gateway->getRequest($api->connection, $comment, ['post-id' => 'p1', 'id' => 'c1']);
        self::assertSame('GET', $get->method());
        self::assertSame('/api/v1/admin/post/p1/comment/c1', $get->path());
        self::assertSame([], $get->query());

        $delete = $api->gateway->deleteRequest($api->connection, $comment, ['post-id' => 'p1', 'id' => 'c1']);
        self::assertSame('DELETE', $delete->method());
        self::assertSame('/api/v1/admin/post/p1/comment/c1/delete', $delete->path());
    }

    public function testSendCallsTheApiAndConvertsTheErrors(): void
    {
        $api = new ApiStub();
        $api->queue(200, ['meta' => [], 'data' => [['id' => 't1']]])->queue(404, ['meta' => ['error' => true]]);
        $tag = $this->definition('tag');

        $response = $api->gateway->send($api->connection, $api->gateway->listRequest($api->connection, $tag, []));
        self::assertSame([['id' => 't1']], $response->data());

        try {
            $api->gateway->send($api->connection, $api->gateway->getRequest($api->connection, $tag, ['id' => 'x']));
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::NotFound, $error->kind);
        }

        self::assertSame(['GET /api/v1/admin/tags', 'GET /api/v1/admin/tag/x'], $api->calls());
    }

    public function testACreationWithoutBlocksIsASinglePostFollowedToTheCreatedObject(): void
    {
        $api = new ApiStub();
        $api->queue(302, [], ['Location' => '/api/v1/admin/tag/t9'])
            ->queue(200, ['meta' => ['id' => 't9'], 'data' => ['id' => 't9', 'name' => 'php']]);

        $plan = $api->gateway->plan(
            $api->connection,
            $this->definition('tag'),
            [],
            [],
            new Payload(['name' => 'php'], [], false),
            true,
        );

        self::assertFalse($plan->hasTwoSteps());
        self::assertSame('t9', $api->gateway->write($api->connection, $plan)->id());
        self::assertSame(['POST /api/v1/admin/tag/new', 'GET /api/v1/admin/tag/t9'], $api->calls());
        self::assertSame(['name' => 'php'], $api->requests[0]['body']);
    }

    public function testAnUpdateWithoutChangeOfTypeSendsTheBlocksInTheSameRequest(): void
    {
        $api = new ApiStub();
        $api->queue(200, ['meta' => ['id' => 'c1'], 'data' => ['id' => 'c1']]);

        $plan = $api->gateway->plan(
            $api->connection,
            $this->definition('content'),
            ['id' => 'c1'],
            ['locale' => 'fr'],
            new Payload(['title' => 'T'], ['intro' => 'Hello'], true),
            false,
        );

        self::assertFalse($plan->hasTwoSteps());
        self::assertSame('c1', $plan->id);

        $api->gateway->write($api->connection, $plan);
        self::assertSame(['PUT /api/v1/admin/content/c1?locale=fr'], $api->calls());
        self::assertSame(['title' => 'T', 'block_intro' => 'Hello', 'publish' => true], $api->requests[0]['body']);
    }

    public function testACreationWithBlocksNeedsASecondRequestOnTheCreatedObject(): void
    {
        $api = new ApiStub();
        $api->queue(302, [], ['Location' => '/api/v1/admin/content/c7'])
            ->queue(200, ['meta' => ['id' => 'c7'], 'data' => ['id' => 'c7']])
            ->queue(200, ['meta' => ['id' => 'c7'], 'data' => ['id' => 'c7', 'parts' => ['intro' => 'Hello']]]);

        $plan = $api->gateway->plan(
            $api->connection,
            $this->definition('content'),
            [],
            [],
            new Payload(['title' => 'T', 'type' => 't1'], ['intro' => 'Hello'], false),
            true,
        );

        self::assertTrue($plan->hasTwoSteps());

        $response = $api->gateway->write($api->connection, $plan);
        self::assertSame(['id' => 'c7', 'parts' => ['intro' => 'Hello']], $response->data());
        self::assertSame(
            ['POST /api/v1/admin/content/new', 'GET /api/v1/admin/content/c7', 'PUT /api/v1/admin/content/c7'],
            $api->calls(),
        );
        self::assertSame(['title' => 'T', 'type' => 't1'], $api->requests[0]['body']);
        self::assertSame(['block_intro' => 'Hello'], $api->requests[2]['body']);
    }

    public function testAFailureOfTheSecondRequestIsReportedAsPartial(): void
    {
        $api = new ApiStub();
        $api->queue(200, ['meta' => ['id' => 'c1'], 'data' => ['id' => 'c1']])
            ->queue(400, ['meta' => ['errors' => true], 'data' => ['.block_intro' => 'Too long']]);

        $plan = $api->gateway->plan(
            $api->connection,
            $this->definition('content'),
            ['id' => 'c1'],
            [],
            new Payload(['type' => 't2'], ['intro' => 'Hello'], false),
            false,
        );

        try {
            $api->gateway->write($api->connection, $plan);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Validation, $error->kind);
            self::assertSame(
                ['id' => 'c1', 'failedStep' => 2, 'appliedStep' => 1],
                $error->toArray()['data']['partial'] ?? null,
            );
        }
    }

    public function testTheBlocksAreNotSentWhenTheIdOfTheCreatedObjectIsUnknown(): void
    {
        $api = new ApiStub();
        $api->queue(200, ['meta' => [], 'data' => []]);

        $plan = $api->gateway->plan(
            $api->connection,
            $this->definition('content'),
            [],
            [],
            new Payload(['title' => 'T'], ['intro' => 'Hello'], false),
            true,
        );

        try {
            $api->gateway->write($api->connection, $plan);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Server, $error->kind);
            self::assertStringContainsString('the blocks have not been sent', $error->getMessage());
        }

        self::assertCount(1, $api->requests);
    }
}
