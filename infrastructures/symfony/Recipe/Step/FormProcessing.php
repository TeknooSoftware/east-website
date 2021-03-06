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

namespace Teknoo\East\WebsiteBundle\Recipe\Step;

use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\FormProcessingInterface;

/**
 * Recipe step to define step to use into a HTTP EndPoint Recipe to skip some recipe's steps if the form is not validate
 * by rules.
 * Symfony implementation for `FormProcessingInterface`.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class FormProcessing implements FormProcessingInterface
{
    public function __invoke(
        FormInterface $form,
        ManagerInterface $manager,
        string $nextStep
    ): FormProcessingInterface {
        if (!$form->isSubmitted() || !$form->isValid()) {
            $manager->continue([], $nextStep);
        }

        return $this;
    }
}
