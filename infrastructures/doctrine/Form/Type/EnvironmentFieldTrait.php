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
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environments;

/**
 * Add to a form the dropdown `environment`, listing the environments defined in the DI (service
 * `teknoo.east.website.environments`). The value submitted is the name of the environment, also by the JSON API.
 * A form submitted without the field selects the default environment.
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
        $builder->add(
            'environment',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => ($environments ?? new Environments())->toArray(),
                'choice_label' => static fn (Environment $environment): string => $environment->getName(),
                'choice_value' => static fn (?Environment $environment): string => $environment?->getName() ?? '',
                'empty_data' => Environment::DEFAULT_NAME,
            ]
        );
    }
}
