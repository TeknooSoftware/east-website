East Website CLI
================

> **Experimental.** This client is provided as an experimental tool: its commands, its options and the format of its
> configuration file may change at any time, outside of the semantic versioning of the library.
>
> This client was co-written with Claude Fable 5.1.

Command line client of the remote JSON API of East Website (see the section *JSON API* of the
[main README](../../README.md)), built with the Symfony Console. It exposes every function of the API, grouped by
domain (`website:<domain>:<action>`), and it is designed to be used by **scripts and AI agents** as well as by humans:

* by default, the result is always **one JSON document on stdout** (the envelope of the API is passed through
  unchanged),
* the failures are **one JSON document on stderr** with a stable **exit code**,
* with this default format, nothing depends on a terminal (no colors, no prompt, except the API key at the login and a
  confirmation before a deletion, only on an interactive tty),
* secrets are never accepted as a command line value, and never printed,
* for humans, `--format=table` prints tables and `--format=tui` opens an
  [interactive interface](#interactive-mode---formattui) in the terminal: tables for the lists, forms to read, create
  and update the objects.

It is a standalone application, with its own dependencies: it is not part of the library, and it is excluded from its
archives.

## Install

```bash
make tools-depend      # or: cd tools/api-client && composer install
make tools-phar        # builds tools/api-client/dist/east-website.phar (needs PHP 8.4+, curl to download Box)
php tools/api-client/dist/east-website.phar list
# or, from the sources
php tools/api-client/bin/east-website list
```

The phar is built by [Box](https://github.com/box-project/box) from a staging directory where only the production
dependencies are installed, from `composer.lock`. It is not versioned: build it with `make tools-phar`.

## Connect to the API

The connection is configured by **one command**, `website:auth:login`: it logs in with the username and the API key,
gets a JWT, and writes the configuration file **`./east-website.json`** (in the current directory). All the other
commands read this file, and nothing else: no environment variable, no connection option. **Without this file, there
is no JWT**, and the commands fail with the exit code `3`.

```bash
php east-website.phar website:auth:login --url=https://example.com --username='my-key:me@example.com'
API key:                                    # asked without being displayed
php east-website.phar website:tag:list      # reads ./east-website.json
php east-website.phar website:auth:logout   # deletes ./east-website.json
```

The username is **`<keyName>:<email>`** (the name of the API key, a colon, the email of its owner), or
`--key-name=my-key --email=me@example.com`. The API key is never accepted as an option value (it leaks in the list of
processes, the shell history and the transcripts of agents): it is asked on a terminal without being displayed, or
read from a file with `--api-key-file=/path/to/file`, or from stdin with `--api-key-file=-` (for scripts and agents):

```bash
printf '%s' "$KEY" | php east-website.phar website:auth:login --url=https://example.com \
    --username='my-key:me@example.com' --api-key-file=-
```

Options of `website:auth:login`, stored in the file:

| Option                                               | Meaning                                                                                     |
|------------------------------------------------------|---------------------------------------------------------------------------------------------|
| `--url`                                              | Base URL of the website, like `https://example.com` (required for the first login)          |
| `--username`, or `--key-name` with `--email`         | Login: `<keyName>:<email>`                                                                  |
| `--api-key-file`                                     | File containing the API key, `-` for stdin (else the key is asked on a terminal)            |
| `--insecure` / `--no-insecure`                       | Do not verify the TLS certificate (a local server with a self-signed certificate)           |
| `--allow-http` / `--no-allow-http`                   | Allow plain `http` to a remote host (refused by default, always allowed for `localhost`)    |
| `--timeout`                                          | Timeout of a request, in seconds (30 by default)                                            |
| `--api-prefix`, `--admin-prefix`                     | Prefixes of the public and of the admin routes, `/api/v1` and `/api/v1/admin` by default    |
| `--login-path`                                       | Login route, `/api/v1/login` by default                                                     |
| `--login-username-field`, `--login-secret-field`     | Fields of the body of the login, `username` and `token` by default                          |
| `--print-token`                                      | Print the JWT in the result (it is never printed by default)                                |

The prefixes, the login route and its fields are defined by the application hosting East Website (East Website ships
no authentication): the defaults are the ones of the documentation of the library and of East Common.

The file (`url`, `username`, `apiKey`, `token`, `expiresAt` and the options above) is written with the mode `0600`,
atomically. **It contains the API key: do not commit it** (add `east-website.json` to your `.gitignore`). The API key
lets the CLI log in again by itself when the JWT expires, or when the server rejects it (once by command), and the new
JWT is written in the file.

* `--config=<file>` (on every command) uses another file than `./east-website.json`, to work with several websites
  or accounts. A relative path is relative to the current directory.
* Run again, `website:auth:login` reuses the settings of the existing file for the same URL, and its API key for the
  same URL and the same username (the API key is never sent to another server or for another user): a new login is
  just `website:auth:login`.
* `website:auth:status` displays the file and the state of the JWT, offline and without any secret.
* `website:auth:renew` (`--days=N` or `--expiration-date=YYYY-MM-DD`) gets a new JWT from the current one and writes
  it in the file. The lifetime is capped by the server.
* `website:auth:logout` deletes the file. There is no logout on the server: the JWT stays valid until its expiration.
* `--dry-run` and `website:schema` work without the file (they send nothing).

## Commands

`list --format=json` and `help <command> --format=json` (built-in) describe all the commands and their options.
`website:schema [resource]` describes the resources of the API: fields, enumerations, paths.

| Domain                                           | Commands                                                                                                                        | Notes                                                                         |
|--------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
| `tag`, `type`, `content`, `post`, `item`, `user` | `website:<domain>:list`, `get <id>`, `create`, `update <id>`, `delete <id>`                                                     |                                                                               |
| `media`                                          | `website:media:list`, `get <id>`, `create --file=...`, `delete <id>`                                                            | A media can not be updated. The type of the file is detected from its content |
| `comment`                                        | `website:comment:list <post-id>`, `get <post-id> <id>`, `update <post-id> <id>`, `delete <post-id> <id>`                        | `update` moderates a comment of a post                                        |
| `front` (public API)                             | `website:front:content:get [slug]`, `post:get <slug>`, `post:list`, `post:list-by-tag <tag-slug>`, `comment:create <post-slug>` | Published objects only; the JWT is sent, unless `--anonymous`                 |
| `auth`                                           | `website:auth:login`, `renew`, `status`, `logout`                                                                               | `login` writes `./east-website.json`, `logout` deletes it                     |

### Fields

The fields of a resource are options in kebab-case (`--is-highlighted`, `--first-name`, `--locale-field`...), see
`website:schema` or `help`. **Only the provided options are sent**, so an update changes only what was asked:

```bash
php east-website.phar website:tag:create --name="News" --is-highlighted
php east-website.phar website:tag:update 0198... --no-is-highlighted
php east-website.phar website:item:update 0198... --parent=          # empty: JSON null
php east-website.phar website:content:update 0198... --tag=a --tag=b  # repeatable, --tag= alone clears the list
php east-website.phar website:user:update 0198... --role=ROLE_ADMIN
```

`--data='{"name":"x"}'`, `--data=-` (stdin) and `--data-file=body.json` provide the JSON body as is (write `--data=-`,
**with the equal sign**: the Console does not accept a value starting with a dash after a space); the typed options
override it.

### Types and contents

* A type declares its blocks with `--block=<name>:<kind>`, kind in `textarea|raw|text|numeric|image` (repeatable, the
  list replaces all the blocks, `--block=` clears it). The API expects the index of the choice of the form: the CLI
  maps the kinds for you.
* The value of a block of a content or a post is `--part=<name>=<value>` (or `--part-file=<name>=<path>`), and
  `--publish` publishes it (there is no way to unpublish with the API).
* The API ignores the blocks sent in the request **creating** a content, or **changing its type**. The CLI sends then
  **two writes**: the fields first (a creation is followed by the read of the created object, like any creation),
  then a `PUT` with the blocks and the publication. When the second one fails, the error document contains
  `partial.id`, to resume with `website:content:update <id> ...`.

```bash
php east-website.phar website:type:create --name=Article --template=article.html.twig --block=intro:textarea --block=cover:image
php east-website.phar website:content:create --title="Hello" --type=0198... --author=0198... --part=intro="Welcome" --publish
```

### Environments

Contents, posts and items belong to an environment of the website (`default`, or one of the environments defined by
the server, like `validation`): `--environment=validation` on `create` and `update`, the name is returned by `get` and
`list`. The CLI does not know the environments of the server: an unknown name is rejected by the server (error 400 on
the field `environment`). The public API (`website:front:*`) serves the default environment, the selection of another
environment (`website-env`) is not supported by the CLI yet.

### Lists

`--page` (from 1), `--order=<field>`, `--direction=ASC|DESC` (validated by the CLI: an invalid value makes the server
fail), and `--locale=<xx>` for translatable objects (content, post, item). The size of a page is fixed by the server and
the API has no filter.

### Creation

The API answers a creation with a redirection to the created object, without any body: the CLI follows it explicitly
and prints the created object, so `data.id` is the id of the new object. If the object was created but can not be
fetched, the result is a document with a `meta.warning` (and the id), not an error: do not retry, to not create a
duplicate.

### Safety nets

* `--dry-run` prints the HTTP requests that would be sent (the bearer is masked) and sends nothing.
* `delete` asks a confirmation only on an interactive terminal; `-y`/`--yes` or `-n` skip it, so an agent is never
  blocked. On the server, a deletion is a soft deletion.
* `--format=table` prints a table instead of JSON (for humans), `--format=tui` opens the
  [interactive mode](#interactive-mode---formattui), `--compact` prints the JSON on one line.
* The output is never altered by the formatter of the Console: the HTML content of the website is printed as is.

## Interactive mode (`--format=tui`)

For humans, the commands of the admin API have an interactive interface in the terminal, built with the Symfony
[TUI component](https://symfony.com/doc/current/tui/index.html). The JSON output stays the default, and it is not
changed by this mode.

```bash
east-website website:content:list --format=tui                  # the table of the contents, page by page
east-website website:content:get <id> --format=tui              # an object, read only
east-website website:content:update <id> --format=tui --title=T # its form, filled with the object and the options
east-website website:content:create --format=tui                # an empty form
```

Each of these commands is an entry point: from the table, the objects are opened, edited, created and deleted without
leaving the interface.

| Screen                       | Keys                                                                                 |
|------------------------------|--------------------------------------------------------------------------------------|
| Everywhere                   | `Ctrl+C` quit                                                                         |
| Table                        | `↑`/`↓` (or `k`/`j`), `PgUp`/`PgDn`, `Home`/`End` move · `Enter` view · `e` edit · `n` new · `d` delete (confirmed) · `←`/`→` previous / next page · `r` reload · `Esc` back · `q` quit |
| Table, to choose a relation  | `Enter` choose · `Space` check (several objects) · `Backspace` none · `Esc` cancel   |
| Object, read only            | `↑`/`↓` move · `e` edit · `d` delete · `Esc` back · `q` quit                         |
| Form                         | `Tab`/`Shift+Tab` next / previous field · `Enter` next field, new line in a text on several lines, or choose the objects of a relation · `Space` check · `Ctrl+S` or `F2` save · `Esc` back (twice to discard the changes) |
| Confirmation                 | `y` yes · `n`, `Enter`, `Esc` no                                                     |

* **Forms.** Like the options of the commands, an update sends only the changed fields. The values given as options
  on the command line fill the form and are always sent (as they were given, while they are not changed in the form).
  The validation errors of the API are displayed under their fields: fix them and save again. After a creation, the
  form goes on as an update of the created object.
* **Relations** (author, type, tags, parent item, linked content) are chosen in the table of their resource.
* **Blocks of a content.** The form has an editor by block of the type of the content, they are loaded when the type
  is chosen, and sent with a second request when the API needs it. If this second request fails, the form goes on as an
  update of the object, to not create it twice.
* **Other commands.** `delete`, `website:auth:*`, `website:schema`, `website:media:create` and `website:front:*` have no
  screen: with `--format=tui` they print the same table as `--format=table`. `--dry-run` never opens the interface, it
  prints the requests as JSON.
* **A terminal is required** on the standard input and on the standard output. Without it (a pipe, a redirection,
  `--no-interaction`), the command fails with a usage error (exit code `2`) before any request.
* **Errors.** The command sends its first request before opening the interface: its failure follows the usual contract
  (a JSON document on stderr, the exit codes below). The later errors are displayed in the interface, and leaving it is
  a success (exit code `0`).

Limits of this mode:

* The requests are synchronous: the interface is frozen until the response (or the timeout) of a request, and the
  keys typed meanwhile, `Ctrl+C` included, are handled after.
* It needs a Unix terminal with `stty` (Windows is not supported), and `ext-pcntl` to follow the resizing of the
  terminal.
* Some terminals and multiplexers keep `Ctrl+S` for themselves (flow control): use `F2` to save.
* The TUI component is experimental in Symfony 8.1, its version is pinned to this minor version (`~8.1.0`).

## Output and exit codes

| Exit code | Meaning                                                  |
|-----------|----------------------------------------------------------|
| `0`       | Success                                                  |
| `1`       | Server error (5xx, unexpected answer) or network failure |
| `2`       | Usage error or validation error of the server (400)      |
| `3`       | Authentication error (no configuration file, 401, 403)   |
| `4`       | Not found (404)                                          |

A failure is written on stderr, like an error of the API, with its kind and, for a validation error, the fields in
error (keys are the paths of the form):

```json
{
    "meta": {
        "error": true
    },
    "data": {
        "code": 400,
        "kind": "validation",
        "message": "Validation failed",
        "fields": {
            ".blocks.0.type": "This value is not valid."
        }
    }
}
```

Non fatal problems (a new JWT that can not be written in the configuration file) are written on stderr as
`warning: ...` lines after a successful command. When the command fails, stderr stays a single JSON document, and the warnings are in its `data.warnings`.

## Development

```bash
make tools-depend      # install the dependencies in tools/api-client/vendor
make tools-qa          # lint, PHPStan (max), PHPCS (PSR-12), composer audit
make tools-test        # PHPUnit: unit tests and end to end tests, with coverage
make tools-phar        # builds tools/api-client/dist/east-website.phar, then run: make -C tools/api-client phar-test
```

* `tools/api-client/src` is the code, `tools/api-client/tests` the tests: `unit` (the whole application against a
  `MockHttpClient`), `e2e` (a real PHP process, the real HTTP client and a fake API served by the PHP built-in server)
  and `phar` (the same scenarios on the built phar, which must not embed any development dependency).
* The commands are generated from the catalogue `src/Resource/Registry.php`. To support a new resource or a new
  field of the API, describe it there: the commands, the options, `website:schema`, the screens of the interactive mode
  and the tests of the catalogue follow. For the interactive mode, a field can name the resource it references
  (`target`, to choose it in a table) and be edited on several lines (`multiline`); a resource has the columns of its
  table (`listColumns`) and the field naming its objects (`labelField`).
* `src/Tui` is the interactive mode: the TUI component has no table and no form, they are the widgets of
  `src/Tui/Widget`. It is tested without any terminal, on the virtual terminal of the component
  (`tests/support/TuiHarness.php` for a widget or a screen, `tests/support/ScriptedDriver.php` for a whole command with
  a script of keys), and in a real pseudo terminal by the end to end tests when `script` (util-linux) is available.
* `BlockTypes::INDEXES` must follow the order of the choices of `BlockType` (the Symfony form of the bundle): a test
  compares them.
* The phar is built with Box, downloaded and verified by `tools/api-client/Makefile` (Box can not be a dependency of
  this project, it conflicts with PHPUnit).
