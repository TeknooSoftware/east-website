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

namespace Teknoo\Tests\East\Website\Tools\Tui\Widget;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Input\Key;
use Symfony\Component\Tui\Input\Keybindings;
use Teknoo\East\Website\Tools\Tui\Widget\TableAction;
use Teknoo\Tests\East\Website\Tools\Support\Keys;

/**
 * Tests of the actions of a table: each one has its own keys and a name of binding which can not collide with
 * the bindings of the other widgets
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TableAction::class)]
class TableActionTest extends TestCase
{
    /**
     * @return iterable<string, array{TableAction}>
     */
    public static function provideActions(): iterable
    {
        foreach (TableAction::cases() as $action) {
            yield $action->name => [$action];
        }
    }

    /**
     * @return iterable<string, array{TableAction, string}> an action and the bytes sent by the terminal for its key
     */
    public static function provideKeys(): iterable
    {
        yield 'Open' => [TableAction::Open, Keys::ENTER];
        yield 'Edit' => [TableAction::Edit, 'e'];
        yield 'Create' => [TableAction::Create, 'n'];
        yield 'Delete' => [TableAction::Delete, 'd'];
        yield 'PreviousPage' => [TableAction::PreviousPage, Keys::LEFT];
        yield 'NextPage' => [TableAction::NextPage, Keys::RIGHT];
        yield 'Reload' => [TableAction::Reload, 'r'];
        yield 'Toggle' => [TableAction::Toggle, Keys::SPACE];
        yield 'Clear' => [TableAction::Clear, Keys::BACKSPACE];
        yield 'Back' => [TableAction::Back, Keys::ESCAPE];
        yield 'Quit' => [TableAction::Quit, 'q'];
    }

    #[DataProvider('provideActions')]
    public function testEveryActionHasAtLeastOneKey(TableAction $action): void
    {
        $keys = $action->keys();

        self::assertNotSame([], $keys);
        foreach ($keys as $key) {
            self::assertNotSame('', $key);
        }
    }

    #[DataProvider('provideActions')]
    public function testEveryBindingNameIsPrefixed(TableAction $action): void
    {
        self::assertMatchesRegularExpression('/^table_[a-z]+(_[a-z]+)*$/', $action->value);
    }

    public function testTheBindingNamesAreUnique(): void
    {
        $names = array_map(static fn (TableAction $action): string => $action->value, TableAction::cases());

        self::assertSame($names, array_values(array_unique($names)));
        self::assertCount(11, $names);
    }

    public function testAKeyIsNeverBoundToTwoActions(): void
    {
        $owners = [];
        foreach (TableAction::cases() as $action) {
            foreach ($action->keys() as $key) {
                self::assertArrayNotHasKey($key, $owners, $key . ' is bound to ' . $action->name . ' and another one');
                $owners[$key] = $action;
            }
        }

        self::assertGreaterThanOrEqual(count(TableAction::cases()), count($owners));
    }

    public function testEveryActionIsCheckedWithItsKey(): void
    {
        $checked = [];
        foreach (self::provideKeys() as [$action]) {
            $checked[] = $action;
        }

        self::assertSame(TableAction::cases(), $checked);
    }

    #[DataProvider('provideKeys')]
    public function testTheKeyOfAnActionIsUnderstoodByTheTuiComponent(TableAction $action, string $bytes): void
    {
        $bindings = [];
        foreach (TableAction::cases() as $case) {
            $bindings[$case->value] = $case->keys();
        }

        $keybindings = new Keybindings($bindings);

        foreach (TableAction::cases() as $case) {
            self::assertSame(
                $case === $action,
                $keybindings->matches($bytes, $case->value),
                'Key of ' . $action->name . ' checked against ' . $case->name,
            );
        }
    }

    public function testTheLettersAreNotBoundToTheKeysMovingTheSelection(): void
    {
        $keys = array_merge(...array_map(static fn (TableAction $action): array => $action->keys(), TableAction::cases()));

        // "j" and "k" move the selection of the table, like the arrows
        self::assertNotContains('j', $keys);
        self::assertNotContains('k', $keys);
        self::assertNotContains(Key::UP, $keys);
        self::assertNotContains(Key::DOWN, $keys);
        self::assertNotContains(Key::PAGE_UP, $keys);
        self::assertNotContains(Key::PAGE_DOWN, $keys);
        self::assertNotContains(Key::HOME, $keys);
        self::assertNotContains(Key::END, $keys);
    }
}
