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

namespace Teknoo\East\Website\Recipe\Step;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Common\Contracts\User\UserInterface;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Session\SessionInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadEnvironmentInterface;
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environment\Exception\EnvironmentNotFoundException;
use Teknoo\East\Website\Object\Environments;
use Teknoo\East\Website\Service\MenuGenerator;
use Teknoo\Recipe\Promise\Promise;

use function in_array;
use function is_array;
use function is_string;

/**
 * Step to select the environment of the website for the request:
 * - from the parameter `website-env`, in the parsed body (POST) or in the query (GET), when it is present. The
 *   environment must be defined and accessible to the current user, else the request fails with an error 404 (the
 *   session is not updated). When it is valid, it is stored into the session.
 * - else from the session: a value that is no longer valid (environment removed from the definitions, user without
 *   the required roles anymore) is removed from the session and the default environment is used.
 * - else the default environment.
 *
 * `default` and environments absent from the access definitions are public. An environment listed in the access
 * definitions requires the current user to own at least one of the listed roles, anonymous visitors have no role.
 *
 * The selected environment is put into the workplan under the keys `environment` and `Environment::class` (both,
 * to overwrite a value injected from the request by the Processor) and into the view parameters bag, with a
 * `menuGenerator` scoped to this environment.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoadEnvironment implements LoadEnvironmentInterface
{
    final public const string PARAMETER_NAME = 'website-env';

    final public const string SESSION_KEY = 'website-env';

    /**
     * @param array<string, list<string>> $environmentsAccess
     */
    public function __construct(
        private readonly Environments $environments,
        private readonly array $environmentsAccess,
        private readonly MenuGenerator $menuGenerator,
    ) {
    }

    private function extractRequestedName(ServerRequestInterface $request): ?string
    {
        $body = $request->getParsedBody();
        if (
            is_array($body)
            && isset($body[self::PARAMETER_NAME])
            && is_string($body[self::PARAMETER_NAME])
            && '' !== $body[self::PARAMETER_NAME]
        ) {
            return $body[self::PARAMETER_NAME];
        }

        $query = $request->getQueryParams();
        if (
            isset($query[self::PARAMETER_NAME])
            && is_string($query[self::PARAMETER_NAME])
            && '' !== $query[self::PARAMETER_NAME]
        ) {
            return $query[self::PARAMETER_NAME];
        }

        return null;
    }

    private function extractSessionName(SessionInterface $session): ?string
    {
        $name = null;
        /** @var Promise<mixed, mixed, mixed> $promise */
        $promise = new Promise(
            static function (mixed $value) use (&$name): void {
                if (is_string($value)) {
                    $name = $value;
                }
            },
        );

        $session->get(self::SESSION_KEY, $promise);

        return $name;
    }

    private function isAccessible(string $name, ?UserInterface $currentUser): bool
    {
        if (Environment::DEFAULT_NAME === $name || !isset($this->environmentsAccess[$name])) {
            return true;
        }

        if (null === $currentUser) {
            return false;
        }

        foreach ($currentUser->getRoles() as $role) {
            if (in_array($role, $this->environmentsAccess[$name], true)) {
                return true;
            }
        }

        return false;
    }

    private function findEnvironment(string $name, ?UserInterface $currentUser): ?Environment
    {
        if (!$this->environments->has($name) || !$this->isAccessible($name, $currentUser)) {
            return null;
        }

        return $this->environments->get($name);
    }

    public function __invoke(
        ManagerInterface $manager,
        ServerRequestInterface $request,
        ParametersBag $bag,
        ?SessionInterface $session = null,
        ?UserInterface $currentUser = null,
    ): LoadEnvironmentInterface {
        $environment = null;

        $requestedName = $this->extractRequestedName($request);
        if (null !== $requestedName) {
            $environment = $this->findEnvironment($requestedName, $currentUser);
            if (null === $environment) {
                $manager->error(new EnvironmentNotFoundException('Environment not found', 404));

                return $this;
            }

            $session?->set(self::SESSION_KEY, $requestedName);
        } elseif (null !== $session) {
            $sessionName = $this->extractSessionName($session);
            if (null !== $sessionName) {
                $environment = $this->findEnvironment($sessionName, $currentUser);
                if (null === $environment) {
                    $session->remove(self::SESSION_KEY);
                }
            }
        }

        $environment ??= Environment::default();

        $manager->updateWorkPlan([
            'environment' => $environment,
            Environment::class => $environment,
        ]);

        $bag->set('environment', $environment);
        $bag->set('menuGenerator', $this->menuGenerator->withEnvironment($environment));

        return $this;
    }
}
