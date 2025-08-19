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

    //config/packages/east_website_di.yaml:
    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-website/src/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/di.php'
    
    //bundles.php
    ...
    Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle::class => ['all' => true],

    //In doctrine config (east_website_doctrine_mongodb.yaml)
    doctrine_mongodb:
        document_managers:
            default:
                auto_mapping: true
                mappings:
                    TeknooEastCommon:
                        type: 'xml'
                        dir: '%kernel.project_dir%/vendor/teknoo/east-common/infrastructures/doctrine/config/universal'
                        is_bundle: false
                        prefix: 'Teknoo\East\Common\Object'
                    TeknooEastWebsite:
                        type: 'xml'
                        dir: '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/config/universal'
                        is_bundle: false
                        prefix: 'Teknoo\East\Website\Object'
                    TeknooEastWebsiteDoctrine:
                        type: 'xml'
                        dir: '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/config/doctrine'
                        is_bundle: false
                        prefix: 'Teknoo\East\Website\Doctrine\Object'

    //In security.yaml
    security:
        providers:
            with_password:
                id: 'Teknoo\East\CommonBundle\Provider\PasswordAuthenticatedUserProvider'
    
        password_hashers:
            Teknoo\East\CommonBundle\Object\PasswordAuthenticatedUser:
                algorithm: '%teknoo.east.common.bundle.password_authenticated_user_provider.default_algo%'


    //In routes/website.yaml
    admin_website:
        resource: '@TeknooEastWebsiteBundle/Resources/config/admin_routing.yaml'
        prefix: '/admin'

    admin_common:
        resource: '@TeknooEastCommonBundle/Resources/config/admin_routing.yaml'
        prefix: '/admin'
    
    website:
        resource: '@TeknooEastWebsiteBundle/Resources/config/routing.yaml'

Support this project
---------------------
This project is free and will remain free. It is fully supported by the activities of the EIRL.
If you like it and help me maintain it and evolve it, don't hesitate to support me on
[Patreon](https://patreon.com/teknoo_software) or [Github](https://github.com/sponsors/TeknooSoftware).

Thanks :) Richard.

Credits
-------
EIRL Richard Déloge - <https://deloge.io> - Lead developer.
SASU Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge, as part of EIRL Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
sharing knowledge and skills.

License
-------
East Website is licensed under the 3-Clause BSD License - see the licenses folder for details.

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/east-website
    
To start a project with Symfony :

    symfony new your_project_name new
    composer require teknoo/east-website-symfony    

This library requires :

    * PHP 8.1+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Immutable.
    * Teknoo/States.
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Optional: Symfony 6.3+ (for administration)

News from Teknoo Website 9.x
----------------------------
This library requires PHP 8.1 or newer and it's only compatible with Symfony 6.3 or newer.
- Support last version of PHP DI 7 et Diactoros 3
- Automatic cleaning of rendered HTML thanks to tidy 

News from Teknoo Website 8.x
----------------------------
This library requires PHP 8.1 or newer and it's only compatible with Symfony 6.2 or newer.

- Users and Media are migrated to East Common
- Dynamic texts can be sanitized thanks to Symfony Sanitizer (if you use Symfony Form)
  - You can persists directly sanitized contents
  - Add helpers to fetch directly these sanitized contents
- Add `ReadOnlyArray` to simulate a read only array'
- Optimization of Menu Generator

News from Teknoo Website 7.x
----------------------------
This library requires PHP 8.1 or newer and it's only compatible with Symfony 6.0 or newer.

- Support Recipe 4.1.1+
- Support East Foundation 6.0.1+
- Public constant are final
- Block's types are Enums
- Direction are Enums
- Use readonly properties behaviors on Immutables
- Remove support of deprecated features removed in `Symfony 6.0` (`Salt`, `LegacyUser`)
- Use `(...)` notation instead array notation for callable
- Enable fiber support in front endpoint
- `QueryInterface` has been splitted to `QueryElementInterface` and `QueryCollectionInterface` to differentiate
  queries fetching only one element, or a scalar value, and queries for collections of objects.
- `LoaderInterface::query` method is only dedicated for `QueryCollectionInterface` queries.
- a new method `LoaderInterface::fetch` is dedicated for `QueryElementInterface` queries.

* Warning * : All legacy user are not supported from this version. User's salt are also
  not supported, all users' passwords must be converted before switching to this version.

News from Teknoo Website 6.x
----------------------------
This library requires PHP 8.0 or newer and it's only compatible with Symfony 5.3 or newer
- Add `UserInterface` to represent and User in a Eastt Website / WebApp.
- Add `AuthDataInterface` to represent any data/credentials, able to authenticate an user
- Update `User` class to following the previeous interface
- Split authentications data from `User` class to a dedicated class `StoredPassword`
- Support password already hashed into `StoredPassword`
- Update Doctrine ODM mappingg about `User` ans add `StoredPassword`
- Support third-party authentication.
- Add `ThirdPartyAuth` to store ids data from thrid party needed to authenticate an user.
- Add `AbstractPassordAuthUser` to wrap password logic in Symfony User for `LegacyUser` and `PasswordAuthenticatedUser`.
- `AbstractUser` can be also used for non password authenticated user.
- Create `PasswordAuthenticatedUser` to implements new Symfony's interface `PasswordAuthenticatedUserInterface`
- Update `SymfonyUserWriter` implementation in Symfony to hash password only when its needed.
- Rework `UserProvider` to `PasswordAuthenticatedUserProvider` to return a `LegacyUser` if the user use the legacy Symfony behavior with a slug
  or a `PasswordAuthenticatedUser`. It is able to migrate logged user to the new behavior, update the hashed ppassword passed by Symfony and
  remove salt.
- Some QA fixes on PHPDoc
- Remove deprecated `ViewParameterInterface`
- Remove deprecated Symfony `User` class
- Create `StoredPasswordType` to manage new user in a Symfony Form.
- Fix some bug in admin routes.
- Update annd fix some minor bug in Doctrinemapping
- Create `OAuth2Authenticator`, built on KNPU OAuth2 client bundle to authenticate user thanks to a OAuth2 provider.

News from Teknoo Website 5.x
----------------------------
This library requires PHP 8.0 or newer and it's only compatible with Symfony 5.2 or newer
- Migrate to PHP 8.0
- Writers services, Deleting services, and interfaces use also `Teknoo\East\Common\Contracts\Object\ObjectInterface`.
- Create `Teknoo\East\Common\Contracts\Object\ObjectInterface`, `Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface` extends it
  dedicated to non persisted object, manipulable by other components
- Update steps and forms interface to use this new interface
- Replace ServerRequestInterface to MessageInterface for ListObjectAccessControlInterface and ObjectAccessControlInterface
- Switch Render steps to MessageInterface
- Add `ExprConversionTrait::addExprMappingConversion` to allow your custom evaluation of expression
- Add `ObjectReference` expression to filter on reference
- CreateObject step has a new parameter `$workPlanKey` to custom the key to use to store the
  new object in the workplan
- CreateObject, DeleteObject, LoadObject, SaveObject and SlugPreparation use `Teknoo\East\Common\Contracts\Object\ObjectInterface`
  instead `Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface`. SaveObject pass the id only if the object implements
  this last object
  
News from Teknoo Website 4.x
----------------------------
This library requires PHP 7.4 or newer and it's only compatible with Symfony 4.4 or newer
- Migrate to Recipe 2.3+ and Tekno 3.3
- Migrate all classics services endpoints to Plan and Recipe.
- Remove all traits in main namespace with implementation in infrastructures namespaces.
- All plans and recipes, and majors of step are defined in the main namespace, only specialized steps are defined in infrastructures namespace.
- Remove AdminEditEndPoint, AdminListEndPoint, AdminNewEndPoint, ContentEndPointTrait and MediaEndPointTrait.
- Update Symfony configuration to manage this new architecture. Remove all services dedicated for each objects in Website, replaced by only agnostic endpoint. All configuration is pass in route.

News from Teknoo Website 3.x
----------------------------
This library requires PHP 7.4 or newer and it's only compatible with Symfony 4.4 or newer
- Migrate to Doctrine ODM 2
- Migrate to new GridFS Repository
- Migrate Gedmo's Timestamp to intern function and service
- Migrate Gedmo's Slug to intern function and service
- Migrate to Doctrine XML Mapping
- Reworking Translation : Fork Gedmo Translation, clean, simplify, rework, in East philosophy
- Remove Gedmo
- Create new Translation configuration
- Pagination Query support countable
- ContentType and ItemType are not hardcoded to use DocumentType, but a Type passed in options via the EndPoint
- Optimize menu to limit requests
- Expr In Agnostic support
- Change Doctrine Repository behavior to create classes dedicated to ODM
- Create Common repository for non ODM with fallback feature
- Autoselect Good Repository in DI
- Migrate MediaEndPoint into ODM namespace
- Add ProxyDetectorInterface and a snippet into DI to detect if an object is behind a proxy agnosticaly
- Require to East Foundation 3.0.0
- Fix errors in services definitions
- Change exception management into MediaEndPoint

News from Teknoo Website 2.x
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

Contribute :)
-------------
You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)
