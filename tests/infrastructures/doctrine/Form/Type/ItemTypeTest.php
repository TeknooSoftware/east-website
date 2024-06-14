<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Doctrine\Form\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Website\Doctrine\Form\Type\ItemType;
use Teknoo\East\Website\Doctrine\Form\Type\TranslatableTrait;
use Teknoo\Tests\East\WebsiteBundle\Form\Type\FormTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversTrait(TranslatableTrait::class)]
#[CoversClass(ItemType::class)]
class ItemTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new ItemType();
    }

    public function testConfigureOptions()
    {
        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->configureOptions(
                $this->createMock(OptionsResolver::class)
            )
        );
    }
}
