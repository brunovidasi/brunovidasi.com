# ProgrammerTime

A project-management tool for small software teams — clients, projects,
tasks, logged work hours (time entries), billing, internal messaging, and
client-facing PDF reports. Originally built in Portuguese as a
TCC (undergraduate thesis) project by Bruno Vieira ([@brunovidasi](https://github.com/brunovidasi))
and Filipe Moreira.

Built on **CodeIgniter 2.2.0** (PHP) with a **SQLite** database (MySQL also supported), Bootstrap 3 /
AdminLTE-style UI, and PDF export via a bundled copy of **mPDF**.

## Status

This app was written against PHP 5.4 (2014–2015) and did not run at all on
PHP 8. It has been fixed and modernized:

- **~500 instances** of removed curly-brace string/array-offset syntax
  (`$str{0}`) across the application code and the vendored mPDF/dompdf
  libraries, converted to `$str[0]`.
- **~20 vendored classes** (the entire mPDF/CPDF PDF-rendering stack —
  `mPDF`, `Cpdf`, `cssmgr`, `SVG`, `otl`, the GIF/BMP/WMF decoders, etc.)
  used PHP 4-style constructors (`function ClassName()` instead of
  `function __construct()`). PHP 8 removed support for these entirely, so
  `new mPDF()` silently ran an *empty* constructor — none of the class's
  setup code executed, and PDF generation failed with a cascade of
  "call to a member function on null" errors. This was the single biggest
  blocker to the app actually running, and easy to miss since `php -l`
  doesn't catch it (it's not a parse error).
- The database layer used the `mysql` driver against PHP's long-removed
  `ext/mysql`; switched to `mysqli`. Also restored CodeIgniter's expected
  `mysqli_query() === false`-on-error behavior via `mysqli_report(MYSQLI_REPORT_OFF)`,
  since PHP 8.1 made mysqli throw exceptions by default, which bypassed
  CodeIgniter's own error handling.
- Removed the `__autoload()` magic function (removed in PHP 8) from
  dompdf's config, `create_function()` (removed in PHP 8) in dompdf's
  reflower classes, and `each()` (removed in PHP 8) throughout mPDF and
  CodeIgniter's core `Security`/`Xmlrpc` classes.
- Fixed several PHP 8 type-strictness fatals surfaced by actually running
  the app end-to-end (not just linting): `count()`/`in_array()`-style calls
  on values that used to silently coerce from `null`, a `filter_var()` call
  passed a string instead of an int/array flag, an `UPDATE` helper called
  with an empty string where an array was expected, arithmetic on
  non-numeric CSS-length strings (mPDF's `ConvertSize()`), and a
  `break` used outside any loop/switch in an SVG parser callback (should
  have been `return`).
- Fixed CodeIgniter 2's own `&get_config()` reference-return quirk and a
  couple of "optional parameter before required parameter" signatures
  (deprecated, and noisy, under PHP 8).
- **Fixed a live, unauthenticated SQL injection** in `assets/ajax/*.php` —
  two standalone endpoints (used for inline status/priority edits) built
  raw `UPDATE` statements by concatenating the table name, column name, and
  value straight from POST data, with no auth check. Rewrote them to
  validate table/column against a fixed whitelist and bind the value via a
  prepared statement. They have since been moved into an authenticated
  CodeIgniter controller (`application/controllers/ajax.php`) with
  per-table permission checks.
- PHP 8.2–8.4 compatibility pass: `#[\AllowDynamicProperties]` on the
  CodeIgniter and mPDF classes, `E_STRICT`/`(boolean)`/`(double)`/`${}`
  deprecations removed, PHP 8 `TypeError`/`ArgumentCountError` crashes
  fixed (missing task, malformed dates, missing URL segments, upload crop
  maths), mPDF warnings that corrupted PDF output (incl. the PHP 8.3
  `unserialize()` trailing-data warning) fixed, and CI's upload MIME
  sniffing restored for PHP 8.1's `finfo` objects.
- Model queries now cast/escape every interpolated value (the login form
  was injectable), and `upload/save_image` is no longer URL-callable.
- Reconstructed `schema.sql` from the model queries, since no database dump
  existed anywhere in the project.
- Translated from Portuguese to English: the interface, emails, PDF reports,
  code (file, class, method, variable and route names) and the database
  schema, including stored values (`'sim'`/`'nao'` are now `'yes'`/`'no'`,
  statuses like `'nao_comecado'` are `'not_started'`, and so on). It was also
  localized: the Brazil-only CPF/CNPJ/CEP validation, input masks and address
  lookup are gone, and money is shown as `$ 1,234.56`. See
  [Translation to English](#translation-to-english).

See [Security](#security) below for what else was found and fixed in the
credential/secret scan.

## Requirements

- PHP 8.1+ with `pdo_sqlite`, `mbstring`, `gd` (`mysqli` instead of
  `pdo_sqlite` if you use MySQL/MariaDB)
- A web server with URL rewriting (Apache + `mod_rewrite`, using the bundled
  `.htaccess`)

## Setup

All settings (environment, base URL, timezone, encryption key, admin
password, database) live in one file, never in the repo. See
`config.example.php`.

1. Copy `config.example.php` to `config.php` at the project root (gitignored)
   and set `'env' => 'development'`, `'base_url' => ''` (auto-detected), any
   `encryption_key`, and an `admin_password`.

2. Point your web server's document root at the project root (so
   `.htaccess` and `index.php` are at the top level), or mount it in a
   subfolder: nothing in the app assumes it is at the site root.

3. Open the app. The SQLite database is created on the first request, in
   `data/programmertime.sqlite` (gitignored), from `schema.sqlite.sql`; there
   is no import step. It seeds an `admin` account whose password is
   `admin_password`, then asks you to create your own account (the app's own
   first-access screen, open until a second account exists).

`timezone` sets the timezone for every date the app shows and records
(default `Australia/Sydney`); change it in the config file.

**MySQL instead of SQLite.** Set `'db' => array('driver' => 'mysql', ...)` with
credentials and load `schema.sql` yourself (`mysql -u root < schema.sql`). Its
seeded admin has no usable password; set one with
`UPDATE user SET password = MD5(CONCAT('<encryption_key>', '<password>')) WHERE login = 'admin';`
(stored passwords are `md5(encryption_key . password)`).

### How SQLite runs on CodeIgniter 2

CodeIgniter 2's own `sqlite` driver needs `ext/sqlite`, which PHP removed in
5.4, so SQLite goes through the `pdo` driver. That driver had never worked for
SELECTs and was fixed in `system/database/drivers/pdo/`: it returned
`execute()`'s boolean instead of the statement, its options used string keys
PDO ignores (so PHP 8 threw exceptions past CodeIgniter's error handling), and
it reported errors from the connection rather than the failed statement. Rows
are now buffered so `num_rows()` and seeking work, and each connection turns
on `foreign_keys` (the schema's cascades depend on it) and a busy timeout.

Two MySQL behaviours the app relied on are handled in the app itself:
optional references posted as `''`/`0` are stored as NULL (`id_or_null()` in
`sql_helper.php`), and the date helpers accept a bare date in a `DATETIME`
column, which MySQL would have padded with `00:00:00`.

## Deployment

The app is deployed at `https://app.brunovidasi.com/programmertime` as part of
the brunovidasi.com website repo (a git subtree at `app/programmertime`), which
pushes to the server over FTP via GitHub Actions.

### The instance directory

The real config and the database must live **outside** the deployed tree,
because the repo is public and `public_html` is web-reachable. Create this
once, by hand, above `public_html`:

```
/home/<user>/domains/brunovidasi.com/
├── programmertime-instance/     <- create manually, never deployed
│   ├── config.php               <- from config.example.php, with 'env' => 'production'
│   ├── data/                    <- SQLite database (created automatically)
│   └── logs/                    <- CodeIgniter error logs (created automatically)
└── public_html/
    └── app/programmertime/      <- deployed by GitHub Actions
```

The app finds this folder by walking up the directory tree looking for
`programmertime-instance`, so no absolute server path is hardcoded anywhere in
the repo. Set `PROGRAMMERTIME_INSTANCE` to override the location. Until the
config exists, the app answers every request with "This application is not
configured yet." It does the same in production if `encryption_key` is shorter
than 32 characters, or if the database doesn't exist yet and `admin_password`
is shorter than 8; the reason goes to PHP's error log.

### First deploy

1. Create `programmertime-instance/config.php` from `config.example.php`, with
   `'env' => 'production'`, a new `encryption_key`
   (`php -r 'echo bin2hex(random_bytes(32)), "\n";'`) and an `admin_password`.
2. Push.
3. Open `https://app.brunovidasi.com/programmertime` straight away: the
   database is created, and the first-access screen asks for a second account.
   It is open to anyone until that account exists, so do it immediately.
4. Log in as `admin`, then remove `admin_password` from the config (it is only
   read when the database is created).
5. Make `assets/images/{users,projects,company,temp}` writable by PHP (755,
   or 775 if uploads fail).

**Backups:** the whole database is `programmertime-instance/data/programmertime.sqlite`.
Download that file. Never change `encryption_key` afterwards: it salts every
stored password.

Production hides PHP errors, logs errors only (to the instance `logs/`), and
marks the session cookie `Secure`. The cookie is scoped to the app's own path,
so it isn't sent to other apps on the same host.

## Project layout

Standard CodeIgniter 2 MVC layout:

- `application/controllers/` — one file per feature area: `auth`
  (login/registration), `project`, `client`, `task`, `time_entry` (logged
  work hours), `finance` (payments), `report` (PDF/HTML reports),
  `message` (internal messages), `user`, `access_level` (permission
  profiles), `company` (company settings), `dashboard`, plus `json` (a small
  read-only API) and `ajax` (see below).
- `application/models/` — one model per feature area, mirroring the
  controllers above.
- `application/views/` — Bootstrap 3 / AdminLTE-style templates, one
  subfolder per feature area.
- `application/helpers/fdate_helper.php`, `hours_helper.php`,
  `hash_password_helper.php`, `sql_helper.php`, `generate_password_helper.php`,
  `currency_helper.php` — app-specific helpers (date and duration formatting,
  password hashing, escaping, password generation, money formatting).
- `application/controllers/ajax.php` — inline click-to-edit endpoints used
  by `assets/js/inlineUpdate.js`.
- `assets/mpdf/` — vendored mPDF, used by the `report` PDF export.
- `system/` — CodeIgniter 2.2.0 core, patched only where it broke under
  PHP 8 (see Status above); not upgraded to a newer framework version.
- `schema.sql` — reconstructed from the model layer (MySQL); not part of stock
  CodeIgniter.
- `schema.sqlite.sql` — the SQLite port of it, applied automatically on first run.
- `config.example.php` — template for the per-machine settings file;
  `application/config/instance.php` finds and loads it.

## Translation to English

The app was originally written entirely in Portuguese. Everything the app
owns is now in English; the vendored libraries (CodeIgniter core, mPDF,
CKEditor, AdminLTE, jQuery plugins) are untouched apart from the English
day/month names in `assets/js/bootstrap-datepicker.js`.

The main renames, for anyone comparing with the old code:

| Before | After |
| --- | --- |
| `acesso` | `auth` |
| `projeto`, `cliente`, `usuario`, `empresa` | `project`, `client`, `user`, `company` |
| `tarefa` | `task` |
| `etapa` (table `projeto_tarefa_hora`) | `time_entry` |
| `financeiro` (table `projeto_financeiro`) | `finance` controller, `payment` table |
| `relatorio`, `mensagem`, `ajuda`, `imagem` | `report`, `message`, `help`, `image` |
| `nivel_acesso` (table `usuario_nivel_acesso`) | `access_level` |
| `lista` / `visualizar` / `cadastrar` / `editar` | `list` / `view` / `create` / `edit` |
| `etapa/lancar`, `etapa/retornar` | `time_entry/start`, `time_entry/finish` |

The database schema changed with it (tables, columns and stored values), so
**a database created before the translation will not work with this code**.
Delete `data/programmertime.sqlite` (on the server,
`programmertime-instance/data/programmertime.sqlite`) and let the app create
a fresh one; there is no migration.

Some dead code was removed rather than translated: the unreachable
`acesso_old_2014_05_20.php` and `java_old_2015_03_03.php` snapshots, the
`licenca` licence controller (it read constants that no longer existed and
was reachable without logging in), CodeIgniter's and the app's
`brazilian_portuguese` language packs, `leia-me.txt`, and an unused sample
report (`relatorio.html`).

## Security

A secret/PII scan was run over the tracked files and full git history
(single squashed commit) before this modernization. The repository is
**public** on GitHub, so anything found here was already exposed.

**Rotated/replaced in this pass** (values swapped for placeholders in the
working tree — see comments at each site):
- `application/config/config.php`'s `encryption_key` (now read from the
  untracked config file) — used for both
  CodeIgniter's session-cookie HMAC and password hashing
  (`hash_password_helper.php`). With the old key public, anyone could forge a
  valid session cookie (e.g. set `logged_in => true`) for any deployment
  still using it, or brute-force stored password hashes offline knowing
  the salt.
- `config.php` (project root)'s `PT_DB_*`/`DB_*_P` database credentials and
  `PT_LINCENSE_KEY`/`PT_ACCESS_PASS`/`PT_VERIFICATION` licensing secrets.
  Unused by the current app (the license check in `auth.php` is
  hardcoded to always pass) but real-looking hosting credentials. That file
  has since been removed; `config.php` at the root is now the gitignored
  local settings file (see Setup).
- A hardcoded shared token in `auth.php`'s `login()`, used as an
  API-key-style check for external/JSON callers.

**Flagged, not silently changed** — rotate these on any real server where
they were actually used, since replacing them in the repo doesn't undo the
exposure:
- The admin account's MD5 password hash was sitting in `auth.php` as a
  plain comment; removed from source. If this corresponds to a real
  deployment's admin account, change that password now.
- All of the above secrets remain visible in this repo's git history even
  after this commit — purging them requires rewriting history
  (`git filter-repo` or BFG) and force-pushing, which is a separate,
  more disruptive step not taken here.

**Removed entirely** (unauthenticated info-disclosure / SSRF surfaces, not
linked from the app's own UI, no functional loss):
- `phpVersion.php` — a bare `phpinfo()` call at the web root.
- `dataServer.php` — dumped the full `$_SERVER` array.
- `getJson.php` — a manual API tester that took an arbitrary `url` POST
  field and made a server-side `curl` request to it with
  `CURLOPT_SSL_VERIFYPEER` disabled (SSRF).

**Cleaned up** (stray files that shouldn't be in version control):
- A Dropbox "conflicted copy" of `application/config/config.php`.
- Two committed CodeIgniter debug log files and one PHP `error_log` file.
- Added `.gitignore` for OS files, logs, and local config overrides.

**Not a secret, left as-is**: several `@programmertime.com` addresses used
as the app's own from/reply-to addresses (e.g. `noreply@programmertime.com`
in `send_email.php`) — these are meant to be public-facing. One personal
Gmail address embedded in a sample report fixture was genericized (the
fixture has since been removed).
