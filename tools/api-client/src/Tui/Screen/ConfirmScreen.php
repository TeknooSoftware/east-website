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

namespace Teknoo\East\Website\Tools\Tui\Screen;

use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Teknoo\East\Website\Tools\Tui\Navigator;
use Teknoo\East\Website\Tools\Tui\Widget\ConfirmWidget;

/**
 * Asks a confirmation, its result is true only after an explicit yes.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfirmScreen implements ScreenInterface
{
    private readonly ConfirmWidget $widget;

    public function __construct(
        Navigator $navigator,
        private readonly string $title,
        string $question,
    ) {
        $this->widget = (new ConfirmWidget($question))->onAnswer(
            static function (bool $answer) use ($navigator): void {
                $navigator->pop($answer);
            },
        );
    }

    public function title(): string
    {
        return $this->title;
    }

    public function hints(): string
    {
        return 'y: yes · n, Enter, Esc: no';
    }

    public function widget(): AbstractWidget&FocusableInterface
    {
        return $this->widget;
    }

    public function resume(mixed $result): void
    {
    }
}
