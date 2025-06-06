# Teknoo Software - Website - Change Log

## [10.1.4] - 2025-05-19
### Stable Release
- Add `teknoo.east.website.form.new_comment.type.class` option to allow customize the comment form class.

## [10.1.3] - 2025-05-13
### Stable Release
- Fix coverage

## [10.1.2] - 2025-05-07
### Stable Release
- Fix issue in Post mapping

## [10.1.1] - 2025-05-06
### Stable Release
- Fix deprecation with Doctrine ODM 2.1+

## [10.1.0] - 2025-04-14
### Stable Release
- Add Blog feature with `Post`, categorized by `Tag` with `Comment` and moderation
  - Blog's `Post` are translatable
  - The library provides queries and endpoints to list tags with pagination and tag filtering
  - User can comment post
  - Routes are availables in dedicated routing.yaml file to avoid enable blog feature and comment feature when there 
    are not required
- Drop Support of PHP 8.2
- Drop Support of Doctrine Persistence 3
- Fix bug in compliance with Doctrine Persistence 4

## [10.0.6] - 2025-02-07
### Stable Release
- Update dev lib requirements
    - Require Symfony libraries 6.4 or 7.2
    - Update to PHPUnit 12
- Drop support of PHP 8.2
    - The library stay usable with PHP 8.2, without any waranties and tests
    - In the next major release, Support of PHP 8.2 will be dropped

## [10.0.5] - 2025-01-30
### Stable Release
- Remove `ProxyDetector`
- If Doctrine ODM, requires `Doctrine ODM Bundle 5.2`

## [10.0.4] - 2025-01-25
### Stable Release
- Update to support Doctrine ODM 2.10 and ODM Bundle 5.1

## [10.0.3] - 2024-12-18
### Stable Release
- Update `ContentType` and `ItemType` to set `doctrine_type` as required instead of as an option.

## [10.0.2] - 2024-11-09
### Stable Release
- Remove Translation Doctrine extension and migrate to dedicated package `teknoo/east-translation`.

## [10.0.1] - 2024-11-09
### Stable Release
- Fix mistake into Doctrine DI MigrationCommand decoration

## [10.0.0] - 2024-11-01
### Stable Release
- Migrate to `Teknoo Recipe` 6.
- Rename `Cookbook` to `Plan`.
    - Old classes and interfaces are deprecated.
- Migrate to `EditablePlan` all previous `Cookbook` / `Plan`.
- Migrate the decoration in Symfony DI to the East Foundation Plan to register the `MenuMiddleware`.
- Migrate the decoration in CRUD and STATIC Plan to register the `LoadTranslationsInterface` middleware.
- Remove Translation Doctrine extension and migrate to dedicated package `teknoo/east-translation`.           

## [9.2.5] - 2024-10-14
### Stable Release
- Update requirement libraries
- Use `random_bytes` instead of `uniqid`

## [9.2.4] - 2024-10-07
### Stable Release
- Update dev lib requirements

## [9.2.3] - 2024-06-03
### Stable Release
- Remove useless dependency to `symfony/templating`

## [9.2.2] - 2024-05-31
### Stable Release
- Fix deprecated : replace `Symfony\Component\HttpKernel\DependencyInjection\Extension`
        by `Symfony\Component\DependencyInjection\Extension\Extension`

## [9.2.1] - 2024-05-17
### Stable Release
- Support East Common 2.12

## [9.2.0] - 2024-05-07
### Stable Release
- Drop support of PHP 8.1
- Add sensitive parameter attribute on methods catching throwable to prevent leak.

## [9.1.12] - 2024-03-22
### Stable Release
- Fix support of last PHPStan 1.10.64
- Use State 6.2

## [9.1.11] - 2024-03-13
### Stable Release
- Use Recipe 5+
- some `Promise` with new features

## [9.1.10] - 2024-02-26
### Stable Release
- Fix typo `preferRealDate` instead of `prefereRealDate`

## [9.1.9] - 2024-01-31
### Stable Release
- Require East Common 2.7
- Common `DatesService` is deprecated, use Foundation's `DatesService` instead

## [9.1.8] - 2024-01-16
### Stable Release
- Support Doctrine Mongo ODM Bundle 5+

## [9.1.7] - 2023-12-11
### Stable Release
- Fix order in content and item symfony forms.

## [9.1.6] - 2023-12-08
### Stable Release
- Fix issue in items with ODM when an item change of parent.

## [9.1.5] - 2023-12-08
### Stable Release
- Fix issue in ODM Translation persister to support `ObjectId` when orphans translations are deleted.

## [9.1.4] - 2023-12-04
### Stable Release
- Support Symfony 7+

## [9.1.3] - 2023-12-01
### Stable Release
- Update dev lib requirements
- Support Symfony 6.4+ (7+ comming soon)

## [9.1.2] - 2023-11-30
### Stable Release
- Update dev lib requirements
- Support Symfony 6.4+ (7+ comming soon)

## [9.1.1] - 2023-11-24
### Stable Release
- Support of Doctrine ODM 2.6.1+

## [9.1.0] - 2023-10-26
### Stable Release
- Update to East Common 2.5
  - Add `teknoo.east.website.rendering.clean_html` and `teknoo.east.website.admin.rendering.clean_html`
    to setup  auto cleanup of the html output.

## [9.0.3] - 2023-10-26
### Stable Release
- Fix issues when a translated field has the same value of the original, the field was not persisted. 

## [9.0.2] - 2023-10-09
### Stable Release
- Fix issues in content form when a block has the same name of a content's owned field.

## [9.0.1] - 2023-10-08
### Stable Release
- Fix issues in translation with last doctrine locator

## [9.0.0] - 2023-08-26
### Stable Release
- Support PHP-DI 7.0+
- Support Laminas Diactoros 3.0+

## [8.6.5] - 2023-08-06
### Stable Release
- Reorder options in Symfony Routes

## [8.6.4] - 2023-06-07
### Stable Release
- Update Teknoo libs
- Require Symfony 6.3 or newer

## [8.6.3] - 2023-05-15
### Stable Release
- Update dev lib requirements
- Update copyrights

## [8.6.2] - 2023-04-16
### Stable Release
- Update dev lib requirements
- Support PHPUnit 10.1+
- Migrate phpunit.xml

## [8.6.1] - 2023-04-11
### Stable Release
- Allow psr/http-message 2

## [8.6.0] - 2023-03-20
### Stable Release
- Migrate Media to Common
- Migrate LocaleMiddleware to Common

## [8.5.1] - 2023-03-12
### Stable Release
- Q/A

## [8.5.0] - 2023-02-28
### Stable Release
- East Common 1.7
- Fix default value of `errorTemplate` to '@@TeknooEastCommon/Error/<error>.html.twig'

## [8.4.3] - 2023-02-11
### Stable Release
- Remove phpcpd and upgrade phpunit.xml

## [8.4.2] - 2023-02-03
### Stable Release
- Update dev libs to support PHPUnit 10 and remove unused phploc

## [8.4.1] - 2022-12-16
### Stable Release
- Some QA fixes
- Drop support of Symfony 6.0 and Doctrine 2.x, supports SF 6.1+ and Doctrine 3+

## [8.4.0] - 2022-12-13
### Stable Release
- Optimize MenuGenerator to fetch menu on a minimum of request
- Add a DI key `teknoo.east.website.menu_generator.default_locations` to set an array of locations to fetch at
  first `MenuGenerator.extract`
- Add deferred translations loading to load all translations of all loaded translatable contents in a minimum requests
- Add `TranslationManager` to enable or stop and fetch all translations
- Add `LoadTranslationsInterface` and `LoadTranslations` step to automatically fetch translation before rendering
  content in dynamic call
- Extends `OriginalRecipeInterface::class . ':CRUD'` and OriginalRecipeInterface::class . ':Static' to disable this
  behavior in admins parts.
- Add `teknoo.east.website.translatable.deferred_loading` key in DI to enable by default this new behavior

## [8.3.6] - 2022-11-25
### Stable Release
- Update symfony configuration for behat

## [8.3.5] - 2022-11-12
### Stable Release
- Migrate behat boostrap into tests directory
- Add strict_types=1 to all tests
- Fix error in ExtensionMetadataFactory::getCacheId to not use reserved char `\`

## [8.3.4] - 2022-10-14
### Stable Release
- Support Recipe 4.2+

## [8.3.3] - 2022-09-11
### Stable Release
- Twig filter `SanitizedContent` can be inherited and content can be altered by overwritting the proteted method `hook`.

## [8.3.2] - 2022-08-27
### Stable Release
- Support East Common 1.4+

## [8.3.1] - 2022-08-23
### Stable Release
- Fix issue in Translation when an use want update a translated object with new properties
  without already set in the original language.

## [8.3.0] - 2022-08-23
### Stable Release
- Add `ReadOnlyArray` to simulate a read only array, to improve memory access (object are passed by reference in PHP)
 instead of array
- `Content::getParts()` return now an instance of `ReadOnlyArray` and this instance is cached until parts properties is
 not updated
- Add `sanitizedParts` to store parts value sanitized during Content's edition. Authenticity of sanitized values
  (they are not be updated directly into the data store) is granted by a hash, computed with `sha256` and a salt to
 pass at each call, If the hash is invalid, null answer is returned by the `Content` instance. 
- Add `SanitizedContent` twig filter to return automatically the sanitized part's element if it is available, else
 sanitize if from parts's elements and return it.

## [8.2.0] - 2022-08-14
### Stable Release
- Support last version of `Teknoo East Common`
- Update writers to support `preferRealDateOnUpdate` behavior

## [8.1.1] - 2022-08-06
### Stable Release
- Fix composer.json

## [8.1.0] - 2022-06-27
### Stable Release
- Fix Support last version of Doctrine persistence
- Old version are set as conflict

## [8.0.7] - 2022-06-18
### Stable Release
- Improve exception message in `PublishedContentFromSlugQuery`

## [8.0.6] - 2022-06-17
### Stable Release
- Clean code and test thanks to Rector
- Update libs requirements

## [8.0.5] - 2022-05-16
### Stable Release
- Fix admin lists routes
- Update teknoo libs requirements

## [8.0.4] - 2022-04-17
### Stable Release
- Rename `.yml` files to `.yaml`
- 
## [8.0.3] - 2022-04-15
### Stable Release
- Fix Recipe decoration in `src/di.php`
 
## [8.0.2] - 2022-04-11
### Stable Release
- Fix `Common\RepositoryTrait::$repository` definition
- Fix generic definition in `TranslatableListener`
- Upgrade dev libs requirements

## [8.0.1] - 2022-04-10
### Stable Release
- Fix `MediaType`, `TypeType`, `ContentType` and `ItemType` form to be use as subforms
  *(missed `data_class`) options.

## [8.0.0] - 2022-04-08
### Stable Release
- Implement Teknoo East Common and keep only CMS Features

## [8.0.0-beta1] - 2022-04-08
### Stable Release
- Implement Teknoo East Common and keep only CMS Features

## [7.0.3] - 2022-03-10
### Stable Release
- Require Recipe 4.1.2+ or later
- Improve PHPStan analyse

## [7.0.2] - 2022-03-08
### Stable Release
- Require Immutable 3.0.1 or later

## [7.0.1] - 2022-03-05
### Stable Release
- Disable test on soft deletable object with `ObjectTestTrait` if the object is not softdeletable

## [7.0.0] - 2022-03-05
### Stable Release
- Support Recipe 4.1.1+
- Support East Foundation 6.0.1+
- Remove support of `PHP 8.0`, support only `PHP 8.1+`
- Remove support of `Symfony 5.4`, support only `Symfony 6.0+`
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

## [6.1.10] - 2022-02-24
### Stable Release
- Support Recipe 4.0.1+
- Support East Foundation 6.0.1+

## [6.1.9] - 2022-02-11
### Stable Release
- Support Immutable 3.0
- Support State 6.0
- Support Recipe 4.0

## [6.1.8] - 2022-01-17
### Stable Release
- Switch to PHPStan 1.4.1+

## [6.1.7] - 2021-12-19
### Stable Release
- Use internal fork of OAuth2 client instead of League version
- Some deprecation fixes with PHP 8.1

## [6.1.6] - 2021-12-12
### Stable Release
- Remove unused QA tool
- Remove support of Symfony 5.3
- Support Symfony 5.4 and 6.0+

## [6.1.5] - 2021-12-10
### Stable Release
- Fix some deprecated with PHP 8.1
 
## [6.1.4] - 2021-12-08
### Stable Release
- Fix some deprecated with PHP 8.1

## [6.1.3] - 2021-12-03
### Stable Release
- Fix some deprecated with PHP 8.1

## [6.1.2] - 2021-11-16
### Stable Release
- QA

## [6.1.1] - 2021-11-14
### Stable Release
- Migrate to PHPStan 1.1+

## [6.1.0] - 2021-10-06
### Stable Release
- Add `NotEqual` expression
- `Sluggable` object can update their slug
- Users can override slugs of `Sluggable` objects

## [6.0.3] - 2021-10-04
### Stable Release
- Fix deprecation in doctrine ODM mapping

## [6.0.2] - 2021-09-22
### Stable Release
- Add `active` property to `User` to allow disable an user (If the field is not present it is considered at true)

## [6.0.1] - 2021-09-16
### Stable Release
- Change `setLocaleField` to allow translatable object with monolanguage website.
- Fix Doctrine `TranslatableListener` when an empty locale is passed to use the default locale
- Fix `LocaleMiddleware` to set the default locale into Session when is not already set.

## [6.0.0] - 2021-09-10
### Stable Release
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

## [6.0.0-rc1] - 2021-09-10
### Beta Release
- Clean legacies salt and hash after migration into User object.

## [6.0.0-beta8] - 2021-09-09
### Beta Release
- Fix migration of old document structure about user to new structure : *(Doctrine ODM override authData collection)*
- Migration structure is now managed by a `User` child class in the `Doctrine` namespace.

## [6.0.0-beta7] - 2021-09-09
### Beta Release
- Migration of old document structure about user to new structure

## [6.0.0-beta6] - 2021-09-08
### Beta Release
- Complete phpdoc
- Add ConnectEndPoint to redirect viisitor to an oauth2 provider thanks to KNPU OAuth2 Client Bundle

## [6.0.0-beta5] - 2021-09-08
### Beta Release
- Complete integrations tests
- Fix Symfony config

## [6.0.0-beta4] - 2021-09-03
### Beta Release
- Fix `SymfonyUserWriter` to set algo when password is hashed

## [6.0.0-beta3] - 2021-09-02
### Beta Release
- Fix `OAuth2Authenticator` with exception during user's fetching from storage.
- Rename `ThirdPartyAuthenticatedUser` to `ThirdPartyAuthenticatedUser`.
- Add `ThirdPartyAuthenticatedUserProvider` as provider for third part authenticated user.

## [6.0.0-beta2] - 2021-09-01
### Beta Release
- Add `ThirdPartyAuth` to store ids data from thrid party needed to authenticate an user.
- Update annd fix some minor bug in Doctrinemapping
- Add hidden and non usable field in `StoredPassword` to help persistents systems to identify `AuthDataInterface` 
  instances.
- Add `AbstractPassordAuthUser` to implement password logic for `LegacyUser` and `PasswordAuthenticatedUser`.
- `AbstractUser` can be also used for non password authenticated user.
- Rename `PasswordAuthenticatedUser` to `SymfonyUserWriter`
- Create `OAuth2Authenticator`, built on KNPU OAuth2 client bundle to authenticate user thanks to a OAuth2 provider. 

## [6.0.0-beta1] - 2021-08-26
### Beta Release
- Add `UserInterface` to represent and User in a Eastt Website / WebApp.
- Add `AuthDataInterface` to represent any data/credentials, able to authenticate an user
- Update `User` class to following the previeous interface
- Split authentications data from `User` class to a dedicated class `StoredPassword`
- Support password already hashed into `StoredPassword`
- Update Doctrine ODM mappingg about `User` ans add `StoredPassword`
- Create `AbstractUser` to wrap East Webiste `User` with a `StoredPassword` in Symfony
- Create `PasswordAuthenticatedUser` to implements new Symfony's interface `PasswordAuthenticatedUserInterface`
- Update `LegacyUser` to use `AbstractUser`
- Update `UserWriter` implementation in Symfony to hash password only when its needed.
- Rework `UserProvider` to `PasswordAuthenticatedUserProvider` to return a `LegacyUser` if the user use the legacy Symfony behavior with a slug
  or a `PasswordAuthenticatedUser`. It is able to migrate logged user to the new behavior, update the hashed ppassword passed by Symfony and 
  remove salt.
- Prepare third-party authentication.
- Some QA fixes on PHPDoc
- Remove deprecated `ViewParameterInterface`
- Remove deprecated Symfony `User` class
- Create `StoredPasswordType` to manage new user in a Symfony Form.
- Fix some bug in admin routes.

## [5.1.5] - 2021-08-12
### Stable Release
- Switch to `Recipe Promise`
- Remove support of Symfony 5.2

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
- CreateObject, DeleteObject, LoadObject, SaveObject and SlugPreparation use `Teknoo\East\Common\Contracts\Object\ObjectInterface`
  instead `Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface`. SaveObject pass the id only if the object implements
  this last object
- Writers services, Deleting services, and interfaces use also `Teknoo\East\Common\Contracts\Object\ObjectInterface`.

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
- Create `Teknoo\East\Common\Contracts\Object\ObjectInterface`, `Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface` extends it
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
- Migrate all classics services endpoints to Plan and Recipe.
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
