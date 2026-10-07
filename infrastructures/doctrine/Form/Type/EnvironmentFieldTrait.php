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

namespace Teknoo\East\Website\Doctrine\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Teknoo\East\Website\Object\Environments;

use function array_combine;
use function array_keys;

/**
 * Add to a form the dropdown `environment`, listing the names of the environments defined in the DI (service
 * `teknoo.east.website.environments`). The form handles only names (also for the JSON API): `Environment` is
 * stringable, and the objects accept a name in `setEnvironment()`, there is no conversion in the form.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait EnvironmentFieldTrait
{
    /**
     * @param FormBuilderInterface<mixed> $builder
     */
    private function addEnvironmentField(FormBuilderInterface $builder, ?Environments $environments): void
    {
        $names = array_keys(($environments ?? new Environments())->toArray());

        $builder->add(
            'environment',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => array_combine($names, $names),
            ]
        );
    }
}
