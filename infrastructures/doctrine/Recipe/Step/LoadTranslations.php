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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Doctrine\Recipe\Step;

use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\LoadTranslationsInterface;

/**
 * Recipe step to load all translation in deferred mod
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class LoadTranslations implements LoadTranslationsInterface
{
    public function __construct(
        private readonly ?TranslationManagerInterface $translationManager,
    ) {
    }

    public function __invoke(): LoadTranslationsInterface
    {
        $this->translationManager?->stopDeferringTranslationsLoading();

        return $this;
    }
}
