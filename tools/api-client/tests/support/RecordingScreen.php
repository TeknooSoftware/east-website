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

namespace Teknoo\Tests\East\Website\Tools\Support;

use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Teknoo\East\Website\Tools\Tui\Screen\ScreenInterface;
use Teknoo\East\Website\Tools\Tui\Widget\ConfirmWidget;

/**
 * A screen recording the results of the screens opened above it, to test what a screen returns when it is closed.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class RecordingScreen implements ScreenInterface
{
    /**
     * @var list<mixed>
     */
    public array $results = [];

    private readonly ConfirmWidget $widget;

    public function __construct()
    {
        $this->widget = new ConfirmWidget('Recording screen');
    }

    public function title(): string
    {
        return 'Recording screen';
    }

    public function hints(): string
    {
        return '';
    }

    public function widget(): AbstractWidget&FocusableInterface
    {
        return $this->widget;
    }

    public function resume(mixed $result): void
    {
        $this->results[] = $result;
    }
}
