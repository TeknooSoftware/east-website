Teknoo Software - Website library
=================================

[![Latest Stable Version](https://poser.pugx.org/teknoo/east-website/v/stable)](https://packagist.org/packages/teknoo/east-website)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/east-website/v/unstable)](https://packagist.org/packages/teknoo/east-website)
[![Total Downloads](https://poser.pugx.org/teknoo/east-website/downloads)](https://packagist.org/packages/teknoo/east-website)
[![License](https://poser.pugx.org/teknoo/east-website/license)](https://packagist.org/packages/teknoo/east-website)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Universal package, following the #East programming philosophy, build on Teknoo/East-Foundation (and Teknoo/Recipe),
and implementing a basic CMS to display dynamics pages with different types and templates.

Example with Symfony 
--------------------

    //These operations are not reauired with teknoo/east-website-symfony

    //config/packages/di_bridge.yaml:
    di_bridge:
      definitions:
        - '%kernel.project_dir%/config/di.php'
    
    //config/packages/east_foundation.yaml:
    di_bridge:
      definitions:
        - '%kernel.project_dir%/vendor/teknoo/east-foundation/src/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/di.php'

    //config/packages/east_website_di.yaml:
    di_bridge:
      definitions:
        - '%kernel.project_dir%/vendor/teknoo/east-website/src/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/symfony/Resources/config/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/di.php'
      import:
        Doctrine\Persistence\ObjectManager: 'doctrine_mongodb.odm.default_document_manager'
    
    //bundles.php
    ...
    Teknoo\East\FoundationBundle\EastFoundationBundle::class => ['all' => true],
    Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle::class => ['all' => true],
    Teknoo\DI\SymfonyBridge\DIBridgeBundle::class => ['all' => true],

    //In doctrine config
    doctrine_mongodb:
      document_managers:
        default:
          auto_mapping: true
          mappings:
            TeknooEastWebsite:
              type: 'yml'
              dir: '%kernel.root_dir%/../vendor/teknoo/east-website/src/config/doctrine'
              is_bundle: false
              prefix: 'Teknoo\East\Website\Object'

    //In security.yml
    security:
      //..
      providers:
        main:
          id: 'teknoo.east.website.bundle.user_provider'

    //In routing.yml
    website:
      resource: '@TeknooEastWebsiteBundle/Resources/config/routing.yml'

Support this project
---------------------

This project is free and will remain free, but it is developed on my personal time. 
If you like it and help me maintain it and evolve it, don't hesitate to support me on [Patreon](https://patreon.com/teknoo_software).
Thanks :) Richard. 

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/east-website
    
To start a project with Symfony :

    symfony new your_project_name new
    composer require teknoo/composer-install
    composer require teknoo/east-website-symfony    

This library requires :

    * PHP 7.4+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Immutable.
    * Teknoo/States.
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Optional: Symfony 4.4+ (for administration)

News from Teknoo Website 2.0
----------------------------

This library requires PHP 7.4 or newer and it's only compatible with Symfony 4.4 or newer, Some change causes bc breaks :
- PHP 7.4 is the minimum required
- Replace array_merge by "..." operators
- Remove some PHP useless DockBlocks
- Switch to typed properties
- Most methods have been updated to include type hints where applicable. Please check your extension points to make sure the function signatures are correct.
_ All files use strict typing. Please make sure to not rely on type coercion.
- Set default values for Objects.  
- Set dependencies defined into PHP-DI used in Symfony as synthetic
  services into Symfony's services definitions to avoid compilation error with Symfony 4.4
- Enable PHPStan in QA Tools and disable PHPMd
- Enable PHPStan extension dedicated to support Stated classes

Credits
-------
Richard Déloge - <richarddeloge@gmail.com> - Lead developer.
Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
 sharing knowledge and skills.

License
-------
East Website is licensed under the MIT License - see the licenses folder for details

Contribute :)
-------------

You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)
