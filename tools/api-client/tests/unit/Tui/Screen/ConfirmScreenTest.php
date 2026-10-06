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

namespace Teknoo\Tests\East\Website\Tools\Tui\Screen;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Teknoo\East\Website\Tools\Tui\Screen\ConfirmScreen;
use Teknoo\East\Website\Tools\Tui\Screen\ScreenInterface;
use Teknoo\East\Website\Tools\Tui\Widget\ConfirmWidget;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the screen asking a confirmation
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfirmScreen::class)]
class ConfirmScreenTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function answers(): iterable
    {
        yield 'yes' => ['y', true];
        yield 'no' => ['n', false];
        yield 'enter' => [Keys::ENTER, false];
        yield 'escape' => [Keys::ESCAPE, false];
    }

    #[DataProvider('answers')]
    public function testTheAnswerIsTheResultOfTheScreen(string $key, bool $expected): void
    {
        $harness = new TuiHarness(50, 8);
        $below = new class implements ScreenInterface {
            /**
             * @var list<mixed>
             */
            public array $results = [];

            public function title(): string
            {
                return 'Below';
            }

            public function hints(): string
            {
                return '';
            }

            public function widget(): AbstractWidget&FocusableInterface
            {
                return new ConfirmWidget('Below');
            }

            public function resume(mixed $result): void
            {
                $this->results[] = $result;
            }
        };

        $screen = new ConfirmScreen($harness->navigator, 'tag · delete', 'Delete the tag "PHP"?');
        $harness->open($below)->open($screen);

        $lines = $harness->lines();
        self::assertSame('tag · delete', $lines[0]);
        self::assertSame('Delete the tag "PHP"?', $lines[2]);
        self::assertSame('y: yes · n, Enter, Esc: no', $lines[7]);
        self::assertSame('tag · delete', $screen->title());

        $screen->resume('ignored');
        self::assertSame([], $below->results);

        $harness->keys($key);

        self::assertSame([$expected], $below->results);
        self::assertSame($below, $harness->navigator->current());
    }
}
