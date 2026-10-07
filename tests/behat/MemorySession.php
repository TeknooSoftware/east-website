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

namespace Teknoo\Tests\East\Website\Behat;

use DomainException;
use Teknoo\East\Foundation\Session\SessionInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

use function array_key_exists;

/**
 * In memory session, following the East Foundation session interface, to test the front environments selection
 * without Symfony.
 */
class MemorySession implements SessionInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function set(string $key, mixed $value): SessionInterface
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function get(string $key, PromiseInterface $promise): SessionInterface
    {
        if (array_key_exists($key, $this->values)) {
            $promise->success($this->values[$key]);
        } else {
            $promise->fail(new DomainException("$key is not available"));
        }

        return $this;
    }

    public function remove(string $key): SessionInterface
    {
        unset($this->values[$key]);

        return $this;
    }

    public function clear(): SessionInterface
    {
        $this->values = [];

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function value(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}
