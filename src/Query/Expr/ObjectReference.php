<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Query\Expr;

use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Operator to define a reference to an object (Aka foreign key in SQL, or references in document databases).
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ObjectReference implements ExprInterface, ImmutableInterface
{
    use ImmutableTrait;

    private ObjectInterface $object;

    public function __construct(ObjectInterface $object)
    {
        $this->uniqueConstructorCheck();

        $this->object = $object;
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }
}
