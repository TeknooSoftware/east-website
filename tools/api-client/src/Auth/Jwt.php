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

namespace Teknoo\East\Website\Tools\Auth;

use Teknoo\East\Website\Tools\Http\Json;

use function base64_decode;
use function count;
use function explode;
use function intdiv;
use function is_int;
use function is_numeric;
use function str_pad;
use function strtr;

/**
 * Reads the claims of a JWT without verifying its signature: the CLI only needs the expiration date, to reuse a
 * token or report its state. The signature is verified by the server.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Jwt
{
    /**
     * @return array<mixed>
     */
    public static function payload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (3 !== count($parts)) {
            return [];
        }

        $encoded = strtr($parts[1], '-_', '+/');
        $encoded = str_pad($encoded, (intdiv(\strlen($encoded) + 3, 4)) * 4, '=');
        $json = base64_decode($encoded, true);
        if (false === $json) {
            return [];
        }

        return Json::decode($json) ?? [];
    }

    public static function expiresAt(string $jwt): ?int
    {
        $exp = self::payload($jwt)['exp'] ?? null;
        if (is_int($exp)) {
            return $exp;
        }

        return is_numeric($exp) ? (int) $exp : null;
    }
}
