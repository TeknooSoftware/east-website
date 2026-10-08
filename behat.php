<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\PHPUnitAssertionsExtension\BehatPHPUnitAssertionsExtension;
use Behat\PHPUnitAssertionsExtension\PHPUnitExceptionStringer;
use Teknoo\Tests\East\Website\Behat\FeatureContext;

// behat/phpunit-assertions-extension 1.0.0 imports a class removed in Behat 4.0 : to remove when fixed upstream
class_alias(PHPUnitExceptionStringer::class, 'Behat\Testwork\Exception\Stringer\PHPUnitExceptionStringer');

// Each Symfony scenario builds and dumps its own container (random class name), kept in memory until the end
ini_set('memory_limit', '256M');

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withExtension(new Extension(BehatPHPUnitAssertionsExtension::class))
            ->withSuite(
                (new Suite('default'))
                    ->withContexts(FeatureContext::class)
            )
    );
