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
