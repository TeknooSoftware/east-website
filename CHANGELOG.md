# Teknoo Software - Website - Change Log

## [5.1.4] - 2021-07-18
### Stable Release
- Add option to `DatesService` to prefer real current date instead stored date.

## [5.1.3] - 2021-07-17
### Stable Release
- Fix call promise's fail in repositories loader to avoid double call on exception in promise

## [5.1.2] - 2021-07-03
### Stable Release
- Update documents and dev libs requirements

## [5.1.1] - 2021-06-20
### Stable Release
- Switch to East Foundation 5.3.0+

## [5.1.0] - 2021-06-20
### Stable Release
- Use updateMessage instead of continueExecution to update only message in workplan
- Writing ParametersBag to store view's parameters to avoid to use and update the server's request at each step

## [5.0.7] - 2021-06-04
### Stable Release
- Fix Deprecatuib for Symfony 5.3

## [5.0.6] - 2021-06-02
### Stable Release
- Fix User with Symfony 5.3

## [5.0.5] - 2021-05-31
### Stable Release
- Minor version about libs requirements

## [5.0.4] - 2021-05-27
### Stable Release
- Switch to last version of Recipe

## [5.0.3] - 2021-04-28
### Stable Release
- Some optimisations on array functions to limit O(n)

## [5.0.2] - 2021-03-28
### Stable Release
- CreateObject step has a new parameter `$workPlanKey` to custom the key to use to store the
  new object in the workplan
- CreateObject, DeleteObject, LoadObject, SaveObject and SlugPreparation use `Teknoo\East\Website\Contracts\ObjectInterface`
  instead `Teknoo\East\Website\Object\ObjectInterface`. SaveObject pass the id only if the object implements
  this last object
- Writers services, Deleting services, and interfaces use also `Teknoo\East\Website\Contracts\ObjectInterface`.

## [5.0.1] - 2021-03-24
### Stable Release
- Constructor Property Promotion
- Non-capturing catches

## [5.0.0] - 2021-03-20
### Stable Release
- Migrate to PHP 8.0
- Remove support of Symfony 4.4 and old versions of Doctrine.
- QA
- Fix license header

## [4.3.4] - 2021-03-11
### Stable Release
- Support East Foundation 4.1
- Remove some public services

## [4.3.3] - 2021-03-09
### Stable Release
- Clean symfony yaml indentations

## [4.3.2] - 2021-02-27
### Stable Release
- Create `Teknoo\East\Website\Contracts\ObjectInterface`, `Teknoo\East\Website\Object\ObjectInterface` extends it
  dedicated to non persisted object, manipulable by other components
- Update steps and forms interface to use this new interface

## [4.3.1] - 2021-02-25
### Stable Release
- Replace ServerRequestInterface to MessageInterface for ListObjectAccessControlInterface and ObjectAccessControlInterface
- Switch Render steps to MessageInterface

## [4.3.0] - 2021-02-25
### Stable Release
- Switch to East Foundation 4.0

## [4.2.0] - 2021-02-23
### Stable Release
- Add `ExprConversionTrait::addExprMappingConversion` to allow your custom evaluation of expression
- Add `ObjectReference` expression to filter on reference

## [4.1.7] - 2021-02-10
### Stable Release
- Fix priorities in cookbook

## [4.1.6] - 2021-02-10
### Stable Release
- Can pass arguments to create object with CreateObject

## [4.1.5] - 2021-02-10
### Stable Release
- Can pass the variable name to store object loaded into workplan

## [4.1.4] - 2021-02-05
### Stable Release
- Improve criteria sanitization to support array and Expr object.

## [4.1.3] - 2021-02-05
### Stable Release
- Fix SearchFormLoader to pass manager to the builder as option instead of fetch a form instance
 from builder. (More easier to write form, without overide Symfony Form).

## [4.1.2] - 2021-02-05
### Stable Release
- Allow POST method for list, to allow search forms

## [4.1.1] - 2021-02-04
### Stable Release
- RenderList step pass also search form to thew view

## [4.1.0] - 2021-02-03
### Stable Release
- Update to East-foundation 3.3.3 and reset criteria ingredient in the workplan of ListContentEndPointInterface
- SearchForm behavior on List of object
- Fix deprecated in DI
- Add interfaces to implements access control on CRUD endpoints

## [4.0.8] - 2021-01-31
### Stable Release
- Fix definitions for `_teknoo_website_admin_media_new`

## [4.0.7] - 2021-01-31
### Stable Release
- Add optional $criteria to the step LoadListObjects to filter list 

## [4.0.6] - 2021-01-31
### Stable Release
- Fix RenderList and RenderForm to pass view parameters in request to view

## [4.0.5] - 2021-01-31
### Stable Release
- Fix MenuGenerator when menu is empty

## [4.0.4] - 2021-01-28
### Stable Release
- Error template can have the joker `<error>`, it will be replace by the code following `40[0-4]` 
  for errors 400 to 404, error and will be replace by `server` for all other error.

## [4.0.3] - 2021-01-25
### Stable Release
- Revert Fix RenderError to not rethrow an error to switch to silently mode

## [4.0.2] - 2021-01-25
### Stable Release
- Fix RenderError to not rethrow an error.

## [4.0.1] - 2021-01-25
### Stable Release
- Fix symfony endpoints services definition to use contracts interfaces instead of cookbooks classes

## [4.0.0] - 2021-01-24
### Stable Release
- Migrate to Recipe 2.3+ and Tekno 3.3
- Migrate all classics services endpoints to Cookbook and Recipe.
- Remove all traits in main namespace with implementation in infrastructures namespaces.
- All cookbooks and recipes, and majors of step are defined in the main namespace, only specialized steps are defined in infrastructures namespace.
- Remove AdminEditEndPoint, AdminListEndPoint, AdminNewEndPoint, ContentEndPointTrait and MediaEndPointTrait.
- Update Symfony configuration to manage this new architecture. Remove all services dedicated for each objects in Website, replaced by only agnostic endpoint. All configuration is pass in route.

## [3.2.5] - 2020-12-03
### Stable Release
- Official Support of PHP8

## [3.2.4] - 2020-10-19
### Stable Release
- MediaEndPoint use `CallbackStreamFactoryInterface`
- Other Endpoint use `StreamFactoryInterface`

## [3.2.3] - 2020-10-19
### Stable Release
- MediaEndPoint use `CallbackStreamFactoryInterface`
- Other Endpoint use `StreamFactoryInterface`

## [3.2.2] - 2020-10-18
### Stable Release
- Simplify `infrastructures/di.php` and remove useless `*key*::class:value`

## [3.2.1] - 2020-10-12
### Stable Release
- Prepare library to support also PHP8.
- Fix tests on PHP8

## [3.2.0] - 2020-10-04
### Stable Release
- Switch to Teknoo/recipe 2.1
- Switch to Teknoo/east-foundation 3.2

## [3.1.3] - 2020-09-18
### Stable Release
- Update QA and CI tools
- fix minimum requirement about psr/http-factory and psr/http-message

## [3.1.2] - 2020-09-11
### Stable Release
### Update
- Replace `@security.encoder_factory` by `Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface`

## [3.1.1] - 2020-09-10
### Stable Release
### Update
- Add Di definition dedicated to Laminas for Symfony bundle to work out of the box with the metapackage 
  `teknoo/east-symfony-symfony`.

## [3.1.0] - 2020-09-10
### Stable Release
### Update
- Use new version of teknoo/bridge-phpdi-symfony

## [3.0.11] - 2020-09-04
### Stable Release
### Update
- Fix Mongo Reference to be compliant with previous version of data created from previous version of this lib.

## [3.0.10] - 2020-08-25
### Stable Release
### Update
- Update libs and dev libs requirements

## [3.0.9] - 2020-8-25
### Stable Release
### Change
- Fix Translatable/Persistence/ODM to manage ObjectId id instead of UUID for old translations.
- Fix FindSlugService and FindBySlugQuery to manage Soft Deletable contents.

## [3.0.8] - 2020-8-21
### Stable Release
### Change
- Fix DateService to keep the computed date on first DatesService::getCurrentDate()

## [3.0.7] - 2020-07-27
### Stable Release
### Change
- Fix Item mapping about content and items in the doctrine configuration dedicated for ODM.

## [3.0.6] - 2020-07-18
### Stable Release
### Change
- Fix tests with last Teknoo/states

## [3.0.5] - 2020-07-18
### Stable Release
### Change
- Fix nullable restriction on doctrine mapping

## [3.0.4] - 2020-07-18
### Stable Release
### Change
- Fix endpoint visibility in container

## [3.0.3] - 2020-07-18
### Stable Release
### Change
- Fix UserProvider to work with Sf 4.4 and Sf 5.* (Since 5.* UserProviderInterface change and is incompatible with 4.4)

## [3.0.2] - 2020-07-17
### Stable Release
### Change
- Update libs requirements
- Fix QA
- Switch to fork teknoo/bridge-phpdi-symfony instead php-ti/symfony-bridge
- Add travis run also with lowest dependencies.

## [3.0.1] - 2020-07-16
### Stable release
- Fixing issue with new GridFS specifications and ID must be ObjectId and not UUID.
- Add custom MediaWriter into infrastructure/doctrine/odm to manage file uploading to new GridFS Specification.
- Add custom Media ODM Repository to manage download file from new GridFS specifications to be compliant with old UUID and new ObjectId.
- Add legacyId in media metadata to allow loader to find media with media created with old GridFS specifications to be found from UUID (ODM try to convert it to objectId and fail).
- Add InclusiveOr expr for query and add convert method in Doctrine.
- Update DeletingService to manage also non DeletableInterface implementation (like media) to call the function remove of manager.
- Update MediaLoader to not use the LoaderTrait and create custom query, without deletedAt and build a query compliant with old and new id.
- Add to WriterInterface and implementations a method to remove an object by calling the DBSource manager.

## [3.0.0] - 2020-07-12
### Stable release
- Migrate to Doctrine ODM 2
- Migrate to new GridFS Repository
- Migrate Gedmo's Timestamp to intern function and service
- Migrate Gedmo's Slug to intern function and service
- Migrate to Doctrine XML Mapping
- Reworking Translation : Fork Gedmo Translation, clean, simplify, rework, in East philosophy
- Remove Gedmo
- Create new Translation configuration
- Migrate Universal into src root
- Pagination Query support countable
- Update Composer libs
- Add full symfony stack in behat test for crud, like in real
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

## [3.0.0-beta4] - 2020-07-12
### Change
- Require to East Foundation 3.0.0-beta2
- Fix errors in services definitions

## [3.0.0-beta3] - 2020-07-12
### Change
- Switch to East Foundation 3.0.0-beta1

## [3.0.0-beta2] - 2020-07-09
### Change
- Change exception management into MediaEndPoint

## [3.0.0-beta1] - 2020-07-08
### Beta Release
### Change
- Migrate to Doctrine ODM 2
- Migrate to new GridFS Repository
- Migrate Gedmo's Timestamp to intern function and service
- Migrate Gedmo's Slug to intern function and service
- Migrate to Doctrine XML Mapping
- Reworking Translation : Fork Gedmo Translation, clean, simplify, rework, in East philosophy
- Remove Gedmo
- Create new Translation configuration
- Migrate Universal into src root
- Pagination Query support countable
- Update Composer libs
- Add full symfony stack in behat test for crud, like in real
- ContentType and ItemType are not hardcoded to use DocumentType, but a Type passed in options via the EndPoint
- Optimize menu to limit requests
- Expr In Agnostic support 
- Change Doctrine Repository behavior to create classes dedicated to ODM
- Create Common repository for non ODM with fallback feature
- Autoselect Good Repository in DI
- Migrate MediaEndPoint into ODM namespace
- Add ProxyDetectorInterface and a snippet into DI to detect if an object is behind a proxy agnosticaly

## [2.1.2] - 2020-04-23
### Stable Release
### Change
- Change Symfony routes keys to be prefixed by `_teknoo_website_`
- Change route `_teknoo_website_content` to not interceptd by `_wdt`

## [2.1.1] - 2020-04-22
### Stable Release
### Fix
- In ContentEndPoint impletation for Symfony, manage also `index.php` as entry file.

## [2.1.0] - 2020-03-11
### Stable Release
### Change
- Update dev tools, migrate to PHPUnit 9.0, phploc 6.0, phpcpd 5.0 
- Migrate Symfony implementation to infrastructures
- Total switch to PSR7 and PSR17 and remove Zend Diactoros to manage PSR7 Request and Response
- Switch to East CallbackStreamInterface instead of CallbackStream of Zend Diactoros
- Remove all behaviors about translation in Universal object to migrate them to Doctrine+Gedmo implementation
- Remove all Doctrine and MongoDb in Universal, migrate into Doctrine+Gedmo implementation
- Update Doctrine mapping file based on the above changes
- Remove LocaleMiddleware dependence to `Gedmo\Translatable\TranslatableListener` to be agnostic and manage directly a callable to pass the current locale
- Adapt Symfony implementation to Symfony 4.4+ changes (interface `Symfony\Component\Translation\TranslatorInterface` to `Symfony\Contracts\Translation\LocaleAwareInterface`)
- Fix Doctrine definitions.
- Use new interfaces about EndPoint provided by East Foundation.
- Fix Symfony definitions
- Provide default DI implemenation about PSR17 Factories.
- Fix Object implementations to be able easily extended by any implementations.
- Fix Doctrine definitions to avoid BC Breaks with old translation and keep use original full qualified document name
- Fix Symfony Form Type dedicated to Content and Item to use Doctrine implementation instead of universal versions
- Update Media Endpoit to allow developper to chose another StreamFactory instead the stream factory identified by `Psr\Http\Message\StreamFactoryInterface` by using `teknoo.east.website.endpoint.media.stream.factory`.  
- Add management of error during uploading in Media Type
- Slug in Content and Item are by default nullable fields and not empty string fields.

## [2.1.0-beta6] - 2020-03-11
### Beta Release
- Fix Doctrine definitions to avoid BC Breaks with old translation and keep use original full qualified document name
- Fix Symfony Form Type dedicated to Content and Item to use Doctrine implementation instead of universal versions
- Update Media Endpoit to allow developper to chose another StreamFactory instead the stream factory identified by `Psr\Http\Message\StreamFactoryInterface` by using `teknoo.east.website.endpoint.media.stream.factory`.  
- Add management of error during uploading in Media Type
- Slug in Content and Item are by default nullable fields and not empty string fields.

## [2.1.0-beta5] - 2020-03-09
### Beta Release
- Fix Symfony definitions

## [2.1.0-beta4] - 2020-03-09
### Beta Release
- Fix Symfony definitions

## [2.1.0-beta3] - 2020-03-09
### Beta Release
- Fix Doctrine definitions.
- Use new interfaces about EndPoint provided by East Foundation.
- Fix Symfony definitions
- Provide default DI implemenation about PSR17 Factories.
- Fix Object implementations to be able easily extended by any implementations.

## [2.1.0-beta2] - 2020-03-05
### Beta Release
- Total switch to PSR7 and PSR17 and remove Zend Diactoros to manage PSR7 Request and Response
- Switch to East CallbackStreamInterface instead of CallbackStream of Zend Diactoros
- Remove all behaviors about translation in Universal object to migrate them to Doctrine+Gedmo implementation
- Remove all Doctrine and MongoDb in Universal, migrate into Doctrine+Gedmo implementation
- Update Doctrine mapping file based on the above changes
- Remove LocaleMiddleware dependence to `Gedmo\Translatable\TranslatableListener` to be agnostic and manage directly a callable to pass the current locale
- Adapt Symfony implementation to Symfony 4.4+ changes (interface `Symfony\Component\Translation\TranslatorInterface` to `Symfony\Contracts\Translation\LocaleAwareInterface`)

## [2.1.0-beta1] - 2020-03-01
### Beta Release
- Update dev tools, migrate to PHPUnit 9.0, phploc 6.0, phpcpd 5.0 
- Migrate Symfony implementation to infrastructures

## [2.0.2] - 2020-02-06
### Stable Release
- Fix in Symfony Configuration the TreeBuilder Configuration to remove deprecated defintion of root. 

## [2.0.1] - 2020-01-29
### Stable Release
- Fix QA
- Require Teknoo State 4.0.1+
- Update requirement for dev tools

## [2.0.0] - 2020-01-14
### Stable Release

## [2.0.0-beta7] - 2019-12-30
### Change
- ContentEndPoint put Last-Modified date into Response header from last updated date of content object
- Can update manually the UpdatedAt for class implementing ObjectTrait

## [2.0.0-beta6] - 2019-12-30
### Change
- Update copyright

## [2.0.0-beta5] - 2019-12-23
### Change
- Fix Make definitions tools
- Fix QA issues spotted by PHPStan
- Enable PHPStan extension dedicated to support Stated classes

## [2.0.0-beta4] - 2019-11-28
### Change
- Enable PHPStan in QA Tools

## [2.0.0-beta3] - 2019-11-28
### Change
- Fix typed propoerty's default value for some objects  

## [2.0.0-beta2] - 2019-11-28
### Change
- Set dependencies defined into PHP-DI used in Symfony as synthetic
  services into Symfony's services definitions to avoid compilation error with Symfony 4.4
- Set default values for Objects.  

## [2.0.0-beta1] - 2019-11-28
### Change
- Most methods have been updated to include type hints where applicable. Please check your extension points to make sure the function signatures are correct.
_ All files use strict typing. Please make sure to not rely on type coercion.
- PHP 7.4 is the minimum required
- Switch to typed properties
- Remove some PHP useless DockBlocks
- Replace array_merge by "..." operators

### Info
This version is not compatible with Doctrine ODM2.0 because Gedmo Extension does not support this version.

## [1.0.2] - 2019-10-24
### Release
- Maintenance release, QA and update dev vendors requirements

## [1.0.1] - 2019-06-09
### Release
- Maintenance release, upgrade composer dev requirement and libs

## [1.0.0] - 2019-02-10
### Release
- Remove support of PHP 7.1
- Remove support of Symfony 4.0 and 4.1 (keep 3.4, LTS)
- Switch to PHPUnit 8.0
- First major stable release

## [0.0.15] - 2019-01-08
### Update
- Need Teknoo East Foundation ^^0.0.11

## [0.0.14] - 2019-01-04
### Add
- Check technical debt and add support for php 7.3

## [0.0.13] - 2018-10-27
### Fix
- Fix syntax of template layout to follow normalize form "@BundleName/Controller/action.format.engine"

## [0.0.12] - 2018-09-02
### Fix
- Fix exception when order is empty

## [0.0.11] - 2018-09-02
### Add
- Pass query param to the view list

## [0.0.10] - 2018-09-02
### Add
- Add direction attribute support to sort results with AdminListEndPoint
- Add change/set default column to order and sort direction to sort results with AdminListEndPoint

## [0.0.9] - 2018-08-15
### Fix
- Fix Recipe bowl, they have an extra looping because the loop counter had a bug.
- Fix recipe compiling when several steps share the same name, firsts was lost.

## [0.0.8] - 2018-07-19
### Fix
- Item object use an array instead of ArrayObject to avoid error mapping
- RepositoryTrait Doctrine bridge suppports DocumentRepository and use a query for findBy() method in this case

## [0.0.7] - 2018-07-18
Stable release

## [0.0.7-beta2] - 2018-07-14
### Update
- Create DBSource interfaces to define object manager and object repository and allow loaders 
  and writers to be independent of Doctrine common. Theses interfaces are inspirated from Doctrine interfaces.
- Main Website namespace is Doctrine independent. Loaders and writers are agnostics.
- Create default implementation of DBSources interfaces with Doctrine ODM.
- Loader are simplified, queries are externalized into independent class.
- LoaderInterface load method accepts only ids, no other criteria are allowed.

### Added
- LoaderInterface query method to execute a QueryInterface instance about objects managed by the loader.

## [0.0.7-beta1] - 2018-06-15
### Update
- update to use recipe 1.1 and east foundation 0.0.8

## [0.0.6] - 2018-06-02
### Release
- Stable release

## [0.0.6-beta4] - 2018-04-18
### Fixed
- Fix getDeletedAt can be null

## [0.0.6-beta3] - 2018-02-26
### Fixed
- Fix error on writer when it's fail but not promise passed

## [0.0.6-beta2] - 2018-02-24
### Updated
- Use States 3.2.1 and last East Foundation 0.0.7-beta3
- Fix admin routing

## [0.0.6-beta2] - 2018-02-14
### Updated
- Use East Foundation 0.0.7-beta1

## [0.0.5] - 2018-01-25
### Updated
- Add tests files into package (remove from export-ignore

## [0.0.4] - 2018-01-23
### Updated
- Create a LoaderTrait to factorize code for non publishable object

## [0.0.3] - 2018-01-20
### Fix
fix doctrine mongodb configuration

## [0.0.2] - 2018-01-20
### Change
Update composer requirement (optional, only to use with Symfony) : require symfony/psr-http-message-bridge 1.0+ and zendframework/zend-diactoros 1.7+

## [0.0.1] - 2018-01-01
### First stable release
### Added
- add 404 response behavior when a content was not found
### Fixed
- update composer dev requirement and minimum stability

## [0.0.1-beta8] - 2017-12-27
### Fixed
- Remove Lexik bundle (useless)
- Set content type on media in Symfony Admin
- Fix deprecation with Symfony 3.4+
- Fix sluggable behavior

### Added
- Locale middleware dedicated to symfony translator updating,

## [0.0.1-beta7] - 2017-12-21
### Fixed
- Fix item loader to loading top
- Fix symfony routing failback for content in front
- QA

### Updated
- Update locale middleware to inject also locale in the view parameters list
- Add block type row

## [0.0.1-beta6] - 2017-11-29
### Fixed
- Update AdminEditEndPoint to recreate a form instance if the object has been updated to avoid error with dynamic form
 following a state of the object

### Updated
- Add pagination capacities in the Admin list endpoint
- Update collection loader to allow use iterator and countable results set to manage pagination
- Split mongo logic into a separated trait, added automatically in the DI

## [0.0.1-beta5] - 2017-11-27
### Fixed
- Fix category use Document Standard trait from states instead of entity
- Fix menu generator to use TopByLocation instead slug and replace TopBySlug method in Category loader by TopByLocation

### Updated
- Remove link in content to category rename Category to item
- Add reference to content into Items.

## [0.0.1-beta4] - 2017-11-24
### Fixed
- Not show solft deletd content into admin crud

## [0.0.1-beta3] - 2017-11-22
### Fixed
- Add publishing button and behavior of Publishable content in AdminEditEndPoint
- Migrate \DateTime type hitting to \DateTimeInterface
- Fix bug in MongoDB document postLoad

## [0.0.1-beta2] - 2017-11-22
### Changed
- Symfony optional support requires now 3.4-rc1 or 4.0-rc1

## [0.0.1-beta1] - 2017-11-21
### First beta release

### Added
- Interface to manages objects : ObjectInterface, to define the getter, PublishableInterface, DeletableInterface (to be soft deletable)
- Base objects :
    - Type : type of page, linked to a template and a list of block available in the template, to populate dynamically.
    - User : represent user able to manage the website's content.
    - Category : To create a set of page.
    - Media : To store image or other resources.
    - Content : Represent a page, owning a type and some categories, translatable.
- Loader and Writer to manage these objects.
- DeletingService to able soft delete some object.
- Trait to implement easily endpoints to display contents, media an static template in your framework.
- Symfony endpoints implementing previous traits.
- Middleware to manage locale to display the page.
- PHP-DI configuration to use universal package with any PSR11 applications.
- Symfony User class to wrap the user base class and branch it with Symfony' user provider / authentication.
- Symfony forms and admin end points to manage base objects.
