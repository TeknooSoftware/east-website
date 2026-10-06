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

namespace Teknoo\East\Website\Tools\Tui\Form;

/**
 * Way a row of a form is displayed and edited.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
enum RowKind
{
    /** A text on a single line, the value is a string */
    case Text;

    /** A text on several lines, the value is a string */
    case Multiline;

    /** A checkbox, the value is a boolean */
    case Bool;

    /** Checkboxes, the value is the list of the checked choices */
    case Choices;

    /** An object chosen in the table of its resource, the value is null or {id, label} */
    case Relation;

    /** Objects chosen in the table of their resource, the value is a list of {id, label} */
    case RelationList;

    /** A text which can not be changed, the value is a string */
    case ReadOnly;
}
