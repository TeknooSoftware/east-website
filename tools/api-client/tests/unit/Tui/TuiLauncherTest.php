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

namespace Teknoo\Tests\East\Website\Tools\Tui;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Http\Json;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\TuiLauncher;
use Teknoo\Tests\East\Website\Tools\Support\ApiStub;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\ScriptedDriver;

/**
 * Tests of the opening of the interactive mode on the screen of a command
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TuiLauncher::class)]
class TuiLauncherTest extends TestCase
{
    private const array TAG = ['id' => 't1', 'name' => 'PHP', 'slug' => 'php', 'isHighlighted' => false];

    private function definition(string $name): ResourceDefinition
    {
        $definition = (new Registry())->resource($name);
        self::assertInstanceOf(ResourceDefinition::class, $definition);

        return $definition;
    }

    /**
     * @param array<mixed> $body
     */
    private static function response(array $body): ApiResponse
    {
        return new ApiResponse(200, [], Json::encode($body), $body);
    }

    /**
     * @param list<string> $keys
     * @return array{TuiLauncher, ScriptedDriver, ApiStub}
     */
    private function launcher(array $keys, bool $interactive = true): array
    {
        $driver = new ScriptedDriver($keys, $interactive, 70, 12);
        $api = new ApiStub();

        return [new TuiLauncher($driver, $api->gateway, new Registry()), $driver, $api];
    }

    public function testTheInteractiveModeNeedsATerminal(): void
    {
        [$launcher] = $this->launcher([]);
        $launcher->assertInteractive();

        [$launcher] = $this->launcher([], false);
        try {
            $launcher->assertInteractive();
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
        }
    }

    public function testAListIsOpenedOnItsTableAndCtrlCStopsTheInterface(): void
    {
        [$launcher, $driver, $api] = $this->launcher([Keys::RIGHT, Keys::CTRL_C]);
        $api->queue(200, ['meta' => ['page' => 2, 'totalPages' => 2, 'count' => 2], 'data' => [self::TAG]]);
        $first = self::response(['meta' => ['page' => 1, 'totalPages' => 2, 'count' => 2], 'data' => [self::TAG]]);

        $launcher->browse($api->connection, $this->definition('tag'), [], ['order' => 'name'], $first);

        self::assertSame(1, $driver->runs);
        self::assertStringStartsWith('tag · list', $driver->screens[0]);
        self::assertStringContainsString('page 1/2 · 2 item(s)', $driver->screens[0]);
        self::assertStringContainsString('page 2/2 · 2 item(s)', $driver->screens[1]);
        self::assertSame(['GET /api/v1/admin/tags?page=2&order=name'], $api->calls());
    }

    public function testAnObjectIsOpenedReadOnly(): void
    {
        [$launcher, $driver, $api] = $this->launcher(['q']);

        $launcher->view(
            $api->connection,
            $this->definition('tag'),
            ['id' => 't1'],
            [],
            self::response(['meta' => ['id' => 't1'], 'data' => self::TAG]),
        );

        self::assertStringStartsWith('tag · PHP', $driver->screens[0]);
        self::assertStringContainsString('e edit · d delete', $driver->screens[0]);
        self::assertSame([], $api->calls());
    }

    public function testAnObjectIsOpenedInAFormFilledByTheCommandLine(): void
    {
        [$launcher, $driver, $api] = $this->launcher([Keys::CTRL_S, Keys::CTRL_C]);
        $api->queue(200, ['meta' => ['id' => 't1'], 'data' => ['name' => 'PHP 8'] + self::TAG]);

        $launcher->edit(
            $api->connection,
            $this->definition('tag'),
            ['id' => 't1'],
            ['locale' => 'fr'],
            self::response(['meta' => ['id' => 't1'], 'data' => self::TAG]),
            new Payload(['name' => 'PHP 8'], [], false),
        );

        self::assertStringStartsWith('tag · edit PHP', $driver->screens[0]);
        self::assertStringContainsString('> name           PHP 8', $driver->screens[0]);
        self::assertSame(['PUT /api/v1/admin/tag/t1?locale=fr'], $api->calls());
        self::assertSame(['name' => 'PHP 8'], $api->requests[0]['body']);
        self::assertStringContainsString('The tag was saved', $driver->screens[1]);
    }

    public function testANewObjectIsOpenedInAnEmptyForm(): void
    {
        // The keys moving the focus between the widgets of the component are disabled: F6 changes nothing
        [$launcher, $driver, $api] = $this->launcher([Keys::F6, 'A', Keys::ESCAPE, Keys::ESCAPE]);

        $launcher->create($api->connection, $this->definition('tag'), [], [], new Payload([], [], false));

        self::assertStringStartsWith('tag · new', $driver->screens[0]);
        self::assertStringContainsString('> name           A', $driver->screens[2]);
        self::assertStringContainsString('Unsaved changes', $driver->screens[3]);
        self::assertSame([], $api->calls());
    }

    public function testAResponseWithoutObjectOpensAnEmptyForm(): void
    {
        [$launcher, $driver, $api] = $this->launcher(['q']);

        $launcher->view($api->connection, $this->definition('tag'), ['id' => 't1'], [], new ApiResponse(200, [], '', null));

        self::assertStringStartsWith('tag · t1', $driver->screens[0]);
    }
}
