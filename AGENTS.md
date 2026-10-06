# Project Overview
This project is a PHP library (`teknoo/east-website`) designed to provide a basic CMS for displaying dynamic pages with different types and templates. It follows the **East programming philosophy** and is built on top of:
- `Teknoo/East-Foundation`
- `Teknoo/Recipe`
- `Teknoo/Immutable`
- `Teknoo/States`

## Core Architecture
The library is structured around a modular architecture where functionality is divided into:
- **Objects**: Core domain entities (e.g., `Post`, `Item`, `Comment`, `Tag`, `Type`).
- **Loaders**: Responsible for fetching data from sources (e.g., `PostLoader`, `TagLoader`).
- **Writers**: Responsible for persisting data (e.g., `PostWriter`, `TagWriter`).
- **Queries**: Data retrieval logic (e.g., `PublishedPostFromSlugQuery`).
- **Recipes & Plans**: Definition of workflows and operations.
- **Contracts**: Interfaces for both core logic and infrastructure (e.g., `ContentRepositoryInterface`).

## Key Components
### 1. Data Modeling
- **Content types**: Support for various types of content (Posts, Items, etc.).
- **Status Management**: Uses `Teknoo\States` to manage object states (e.g., `Draft`, `Published`, `Moderated`).
- **Immutability**: Leverages `Teknoo\Immutable` for consistent state handling.

### 2. Data Access
- **Repositories**: Abstracted via contracts to support multiple data sources (e.g., MongoDB, Doctrine).
- **Loaders**: High-level services to fetch specific entities.
- **Queries**: Structured query objects for complex filtering and retrieval.

### 3. Workflows (Recipes & Plans)
- **Recipes**: High-level business logic definitions.
- **Plans**: Sequence of steps (`Step`) to perform specific actions.
- **Endpoints**: Defined operations like `ListAllPostsEndPoint`, `PostCommentOnPostEndPoint`, etc.

## Technology Stack
- **PHP 8.1+**
- **Symfony 6.3+** (Optional, for administration features)
- **PHP-DI** (Dependency Injection)
- **Doctrine MongoDB ODM** (Common data source)
- **PHPStan** (Static analysis)
- **Behat / PHPUnit** (Testing)

## CLI client of the remote API (`tools/api-client/`, experimental)
`tools/api-client/` is an experimental standalone Symfony Console application (own `composer.json`, own dependencies, not part of the library and excluded from its archives) which exposes every function of the remote JSON API, grouped by domain (`website:<domain>:<action>`, e.g. `website:type:create`). It is configured only by `website:auth:login` (URL, username `<keyName>:<email>`, API key, options), which gets a JWT and writes `./east-website.json` (0600, API key included, `--config=<file>` for another file); all the other commands read this file and nothing else (no environment variable; no file, no JWT), and `website:auth:logout` deletes it. It prints JSON on stdout, JSON errors on stderr, and uses stable exit codes (0 ok, 1 server/network, 2 usage/validation, 3 auth, 4 not found), so it can be used by agents. For humans only, `--format=table` prints tables and `--format=tui` opens an interactive interface built on `symfony/tui` (tables for the lists, forms for get/create/update); it needs a terminal on stdin and stdout and fails with exit code 2 without it, so agents keep the default JSON format. Usage guide: `tools/api-client/README.md`.
- `src/`: code (namespace `Teknoo\East\Website\Tools`), the commands are generated from `src/Resource/Registry.php`; `src/Tui/` is the interactive mode (its own table and form widgets, screens, navigation).
- `tests/`: PHPUnit suites `unit`, `e2e` (real HTTP client and a fake API) and `phar`; the interactive mode is tested on a virtual terminal (`tests/support/TuiHarness.php`, `tests/support/ScriptedDriver.php`).
- `dist/`: `east-website.phar`, built by `make tools-phar` (not versioned).
- `make tools-depend`, `make tools-qa`, `make tools-test`, `make tools-phar` (not run by `make qa` and `make test`).

## Development & Quality Assurance
The project uses a `Makefile` to manage development tasks.

### Commands
- `make qa`: Runs linting, PHPStan, PHPCS, and security audits.
- `make qa-offline`: Runs linting, PHPStan, and PHPCS without security audits.
- `make test`: Runs PHPUnit tests with coverage and Behat tests.
- `make clean`: Cleans up vendor and test caches.
- `make depend`: Updates dependencies via Composer.

### Standards
- **Coding Style**: PSR-12.
- **Static Analysis**: PHPStan is used for rigorous type checking.
- **Testing**: Behavior is verified through Behat and unit tests through PHPUnit.

## Project Structure
- `src/`: Main source code.
  - `Object/`: Domain entities.
  - `Loader/`: Data fetching logic.
  - `Writer/`: Data persistence logic.
  - `Query/`: Query definitions.
  - `Contracts/`: Interface definitions.
  - `Recipe/`: Workflows and plans.
- `infrastructures/`: Infrastructure-specific implementations (e.g., Doctrine mappings).
- `tests/`: Test suite.
