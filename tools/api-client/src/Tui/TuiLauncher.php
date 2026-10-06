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

namespace Teknoo\East\Website\Tools\Tui;

use Symfony\Component\Tui\Event\InputEvent;
use Symfony\Component\Tui\Input\Keybindings;
use Symfony\Component\Tui\Tui;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Resource\ResourceGateway;
use Teknoo\East\Website\Tools\Tui\Driver\DriverInterface;
use Teknoo\East\Website\Tools\Tui\Screen\FormMode;
use Teknoo\East\Website\Tools\Tui\Screen\FormScreen;
use Teknoo\East\Website\Tools\Tui\Screen\ListMode;
use Teknoo\East\Website\Tools\Tui\Screen\ListScreen;
use Teknoo\East\Website\Tools\Tui\Screen\ScreenInterface;

use function is_array;

/**
 * Opens the interactive mode (--format=tui) on the screen of a command: the table of a list, or the form of an
 * object. The command sends its first request itself before, so its failure follows the usual contract of the
 * CLI; the later errors are displayed in the interface.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TuiLauncher
{
    public function __construct(
        private readonly DriverInterface $driver,
        private readonly ResourceGateway $gateway,
        private readonly Registry $registry,
        private readonly PayloadBuilder $builder = new PayloadBuilder(),
    ) {
    }

    /**
     * @throws ApiException a usage error when the interactive mode can not be opened
     */
    public function assertInteractive(): void
    {
        $this->driver->assertInteractive();
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    public function browse(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query,
        ApiResponse $first,
    ): void {
        $this->run($connection, static fn (Session $session): ScreenInterface => new ListScreen(
            $session,
            $definition,
            $params,
            $query,
            ListMode::Browse,
            [],
            $first,
        ));
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    public function view(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query,
        ApiResponse $document,
    ): void {
        $this->form($connection, $definition, $params, $query, FormMode::View, $document, null);
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     * @param Payload $prefill values given on the command line
     */
    public function edit(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query,
        ApiResponse $document,
        Payload $prefill,
    ): void {
        $this->form($connection, $definition, $params, $query, FormMode::Edit, $document, $prefill);
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     * @param Payload $prefill values given on the command line
     */
    public function create(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query,
        Payload $prefill,
    ): void {
        $this->form($connection, $definition, $params, $query, FormMode::Create, null, $prefill);
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     */
    private function form(
        Connection $connection,
        ResourceDefinition $definition,
        array $params,
        array $query,
        FormMode $mode,
        ?ApiResponse $document,
        ?Payload $prefill,
    ): void {
        $data = $document?->data();

        $this->run($connection, static fn (Session $session): ScreenInterface => new FormScreen(
            $session,
            $definition,
            $params,
            $query,
            $mode,
            is_array($data) ? $data : [],
            $prefill,
        ));
    }

    /**
     * @param callable(Session): ScreenInterface $first
     */
    private function run(Connection $connection, callable $first): void
    {
        // A single widget has the focus, the screen: the keys moving the focus between the widgets are disabled
        $tui = new Tui(
            terminal: $this->driver->terminal(),
            keybindings: new Keybindings(['focus_next' => [], 'focus_previous' => []]),
        );

        $navigator = new Navigator($tui);
        $quit = new Keybindings(['app_quit' => ['ctrl+c']]);

        // The terminal is in raw mode: Ctrl+C is a key like the others, it must be handled
        $tui->addListener(static function (InputEvent $event) use ($navigator, $quit): void {
            if ($quit->matches($event->getData(), 'app_quit')) {
                $event->stopPropagation();
                $navigator->quit();
            }
        });

        $navigator->push($first(new Session($connection, $this->gateway, $this->registry, $this->builder, $navigator)));

        $this->driver->loop($tui);
    }
}
