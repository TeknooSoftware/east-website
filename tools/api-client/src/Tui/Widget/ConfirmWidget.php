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
use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Widget\AbstractWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Symfony\Component\Tui\Widget\FocusableTrait;
use Symfony\Component\Tui\Widget\KeybindingsTrait;
use Symfony\Component\Tui\Widget\VerticallyExpandableInterface;
use Teknoo\East\Website\Tools\Tui\Text\Ansi;

use function array_slice;
use function count;
use function max;

/**
 * A question answered by yes or no. Everything else than an explicit yes is a no.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfirmWidget extends AbstractWidget implements FocusableInterface, VerticallyExpandableInterface
{
    use FocusableTrait;
    use KeybindingsTrait;

    private bool $expanded = true;

    /**
     * @var (callable(bool): void)|null
     */
    private $onAnswer = null;

    public function __construct(
        private readonly string $question,
    ) {
    }

    /**
     * @param callable(bool): void $callback
     */
    public function onAnswer(callable $callback): static
    {
        $this->onAnswer = $callback;

        return $this;
    }

    public function expandVertically(bool $expand): static
    {
        $this->expanded = $expand;
        $this->invalidate();

        return $this;
    }

    public function isVerticallyExpanded(): bool
    {
        return $this->expanded;
    }

    public function handleInput(string $data): void
    {
        if (null === $this->onAnswer) {
            return;
        }

        $keys = $this->getKeybindings();
        if ($keys->matches($data, 'confirm_yes')) {
            ($this->onAnswer)(true);
        } elseif ($keys->matches($data, 'confirm_no')) {
            ($this->onAnswer)(false);
        }
    }

    public function render(RenderContext $context): array
    {
        $width = $context->getColumns();
        $height = max(1, $context->getRows());

        $lines = array_slice(
            ['', Ansi::fit($this->question, $width), '', Ansi::dim(Ansi::fit('[y] yes   [n] no', $width))],
            0,
            $height,
        );
        if ($this->expanded) {
            while (count($lines) < $height) {
                $lines[] = '';
            }
        }

        return $lines;
    }

    /**
     * @return array<string, string[]>
     */
    protected static function getDefaultKeybindings(): array
    {
        return [
            'confirm_yes' => ['y', 'shift+y'],
            'confirm_no' => ['n', 'shift+n', Key::ENTER, Key::ESCAPE],
        ];
    }
}
