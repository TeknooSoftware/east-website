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

namespace Teknoo\East\Website\Tools\Tui\Widget;

use Symfony\Component\Tui\Input\Key;

/**
 * Actions of a table other than moving its selection. The value is the name of the key binding: the names are
 * prefixed, because the bindings of the TUI component are merged by name between the widgets.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
enum TableAction: string
{
    case Open = 'table_open';
    case Edit = 'table_edit';
    case Create = 'table_create';
    case Delete = 'table_delete';
    case PreviousPage = 'table_previous_page';
    case NextPage = 'table_next_page';
    case Reload = 'table_reload';
    case Toggle = 'table_toggle';
    case Clear = 'table_clear';
    case Back = 'table_back';
    case Quit = 'table_quit';

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return match ($this) {
            self::Open => [Key::ENTER],
            self::Edit => ['e'],
            self::Create => ['n'],
            self::Delete => ['d'],
            self::PreviousPage => [Key::LEFT],
            self::NextPage => [Key::RIGHT],
            self::Reload => ['r'],
            self::Toggle => [Key::SPACE],
            self::Clear => [Key::BACKSPACE],
            self::Back => [Key::ESCAPE],
            self::Quit => ['q'],
        };
    }
}
