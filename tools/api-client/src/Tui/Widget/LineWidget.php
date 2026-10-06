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

use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;

/**
 * A single line of text, always one line high even when it is empty: the title, the status and the key hints
 * around the screens keep a stable height.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LineWidget extends AbstractWidget
{
    public function __construct(
        private string $text = '',
    ) {
    }

    public function setText(string $text): static
    {
        if ($this->text !== $text) {
            $this->text = $text;
            $this->invalidate();
        }

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function render(RenderContext $context): array
    {
        return [Ansi::clip($this->text, $context->getColumns())];
    }
}
