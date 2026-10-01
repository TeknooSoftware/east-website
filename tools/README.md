East Website CLI
================

Command line client of the remote JSON API of East Website (see the section *JSON API* of the
[main README](../README.md)), built with the Symfony Console. It exposes every function of the API, grouped by
domain (`website:<domain>:<action>`), and it is designed to be used by **scripts and AI agents** as well as by humans:

* the result is always **one JSON document on stdout** (the envelope of the API is passed through unchanged),
* the failures are **one JSON document on stderr** with a stable **exit code**,
* nothing depends on a terminal (no colors, no prompt, except a confirmation before a deletion on an interactive tty),
* secrets are never accepted as a command line value, and never printed.

It is a standalone application, with its own dependencies: it is not part of the library, and it is excluded from its
archives.

## Install

```bash
make tools-depend      # or: cd tools && composer install
make tools-phar        # builds tools/dist/east-website.phar (needs PHP 8.4+, curl to download Box)
php tools/dist/east-website.phar list
# or, from the sources
php tools/bin/east-website list
```

The phar is built by [Box](https://github.com/box-project/box) from a staging directory where only the production
dependencies are installed, from `composer.lock`. It is not versioned: build it with `make tools-phar`.

## Connect to the API

The prefixes of the routes and the login route are defined by the application hosting East Website (East Website
ships no authentication): the defaults are the ones of the documentation of the library and of East Common.

| Environment variable | Option | Meaning |
|---|---|---|
| `EAST_WEBSITE_URL` | `--url` | Base URL of the website, like `https://example.com` (plain `http` is refused for a remote host, unless `--allow-http`) |
| `EAST_WEBSITE_USERNAME` | `--username` | Login: **`<keyName>:<email>`** (the name of the API key, a colon, the email of its owner) |
| `EAST_WEBSITE_API_KEY` | `--api-key-file` | The API key. No option takes the value itself, because it leaks in the list of processes and in transcripts. `--api-key-file=-` reads stdin |
| `EAST_WEBSITE_TOKEN` | `--token-file` | A JWT to use as is, without login |
| `EAST_WEBSITE_SESSION_FILE` | `--session-file`, `--no-session` | File storing the JWT between two calls |
| `EAST_WEBSITE_TIMEOUT` | `--timeout` | Timeout of a request, in seconds (30 by default) |
| `EAST_WEBSITE_API_PREFIX` | | Prefix of the public routes, `/api/v1` by default |
| `EAST_WEBSITE_ADMIN_PREFIX` | | Prefix of the admin routes, `/api/v1/admin` by default |
| `EAST_WEBSITE_LOGIN_PATH` | | Login route, `/api/v1/login` by default |
| `EAST_WEBSITE_LOGIN_USERNAME_FIELD`, `EAST_WEBSITE_LOGIN_SECRET_FIELD` | | Fields of the login body, `username` and `token` by default |

Other global options: `--anonymous` (send no JWT), `--insecure` (do not verify the TLS certificate), `--allow-http`.
An option always wins over the environment variable.

### Login

```bash
export EAST_WEBSITE_URL=https://example.com
export EAST_WEBSITE_USERNAME='my-key:me@example.com'
export EAST_WEBSITE_API_KEY='...'          # or: --api-key-file=/path/to/file

php east-website.phar website:auth:login    # JWT stored in the session file, printed only with --print-token
php east-website.phar website:tag:list      # the next commands reuse it
```

`website:auth:login` also accepts `--key-name=my-key --email=me@example.com` to compose the username.

The JWT is resolved in this order: `EAST_WEBSITE_TOKEN` / `--token-file`, then the stored session (when still valid),
then a new login with the username and the API key. A request rejected with a `401` is replayed once after a new login
(only when the JWT was not given explicitly). With the environment variables above, an agent does not need any explicit
login: the first command logs in and stores the session, the next ones reuse it.

The session (`{baseUrl, username, token, expiresAt}`, never the API key) is stored with the mode `0600`, atomically, in
`$EAST_WEBSITE_SESSION_FILE`, else `$XDG_STATE_HOME/east-website-cli/session.json` (`~/.local/state/...`). Without
`HOME` or `XDG_STATE_HOME`, there is no session: a warning is written on stderr and the commands still work.
`--no-session` disables it. There is no refresh token: `website:auth:renew` (`--days=N` or
`--expiration-date=YYYY-MM-DD`) gets a new JWT from the current one, and the lifetime is capped by the server
(1 day by default). There is no logout on the server: `website:auth:logout` only forgets the local session.

## Commands

`list --format=json` and `help <command> --format=json` (built-in) describe all the commands and their options.
`website:schema [resource]` describes the resources of the API: fields, enumerations, paths.

| Domain | Commands | Notes |
|---|---|---|
| `tag`, `type`, `content`, `post`, `item`, `user` | `website:<domain>:list`, `get <id>`, `create`, `update <id>`, `delete <id>` | |
| `media` | `website:media:list`, `get <id>`, `create --file=...`, `delete <id>` | A media can not be updated |
| `comment` | `website:comment:list <post-id>`, `get <post-id> <id>`, `update <post-id> <id>`, `delete <post-id> <id>` | `update` moderates a comment of a post |
| `front` (public API) | `website:front:content:get [slug]`, `post:get <slug>`, `post:list`, `post:list-by-tag <tag-slug>`, `comment:create <post-slug>` | Published objects only; the JWT is sent when available |
| `auth` | `website:auth:login`, `renew`, `status`, `logout` | `status` is offline and shows no secret |

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
* `--format=table` prints a table instead of JSON (for humans), `--compact` prints the JSON on one line.
* The output is never altered by the formatter of the Console: the HTML content of the website is printed as is.

## Output and exit codes

| Exit code | Meaning |
|---|---|
| `0` | Success |
| `1` | Server error (5xx, unexpected answer) or network failure |
| `2` | Usage error or validation error of the server (400) |
| `3` | Authentication error (missing credentials, 401, 403) |
| `4` | Not found (404) |

A failure is written on stderr, like an error of the API, with its kind and, for a validation error, the fields in
error (keys are the paths of the form):

```json
{"meta": {"error": true}, "data": {"code": 400, "kind": "validation", "message": "Validation failed", "fields": {".blocks.0.type": "This value is not valid."}}}
```

Non fatal problems (a session that can not be stored) are written on stderr as `warning: ...` lines after a successful
command. When the command fails, stderr stays a single JSON document, and the warnings are in its `data.warnings`.

## Development

```bash
make tools-depend      # install the dependencies in tools/vendor
make tools-qa          # lint, PHPStan (max), PHPCS (PSR-12), composer audit
make tools-test        # PHPUnit: unit tests and end to end tests, with coverage
make tools-phar        # builds tools/dist/east-website.phar, then run: make -C tools phar-test
```

* `tools/src` is the code, `tools/tests` the tests: `unit` (the whole application against a `MockHttpClient`), `e2e`
  (a real PHP process, the real HTTP client and a fake API served by the PHP built-in server) and `phar` (the same
  scenarios on the built phar, which must not embed any development dependency).
* The commands are generated from the catalogue `src/Resource/Registry.php`. To support a new resource or a new
  field of the API, describe it there: the commands, the options, `website:schema` and the tests of the catalogue
  follow.
* `BlockTypes::INDEXES` must follow the order of the choices of `BlockType` (the Symfony form of the bundle): a test
  compares them.
* The phar is built with Box, downloaded and verified by `tools/Makefile` (Box can not be a dependency of this project,
  it conflicts with PHPUnit).
