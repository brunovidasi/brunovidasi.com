# The Collection

Bruno's records, CDs, DVDs and Blu-rays — a public site built on his own copy of
the Discogs data, with an admin behind it for everything Discogs doesn't know.

- **The shelf** — `/music/` — the whole collection as a pile on the
  floor, a grid or a list. On the floor, **Organise vinyls on a crate** throws
  the pile into a wooden crate to flip through, by artist, release year, title,
  format, region or when it was added; **Back to the mess** tips it out again.
- **Artist pages** — `/music/lady-gaga`, `/beyonce`, `/anitta`,
  `/rbd` — one page per artist, split into eras, each era split by format.
- **Wantlist** — `/music/wantlist` — what isn't on the shelf yet.
- **Admin** — `/music/admin` — one account, Bruno's.

## How it works

Discogs is the source for what a record *is*. It is not the source for what
Bruno knows about *his* copy — which pressing, what the barcode on the back
actually says, where he bought it, which of the twelve sleeve images is the one
to show. So the database keeps those apart:

- **`releases`** is Discogs' data, cached. A sync overwrites it freely.
- **`items`** is one physical copy, and it holds Bruno's own fields. **A sync
  never writes to them.**

Where one of his fields is empty, the site falls back to the Discogs value, so
filling one in is an override rather than a duplicate — and clearing it goes
back to Discogs. That fallback lives in one function,
`item_field_value()` in `includes/fields.php`, which the admin form, the drawer
and the API all read.

The front end no longer calls Discogs at all. It reads `api/collection`,
`api/artist` and `api/item` from this site, which is what makes the drawer able
to show Bruno's notes, his chosen cover, and only the fields he ticked in
**Drawer fields**.

A box set is one release on Discogs, but its discs can each be a record of their
own (`items.parent_item_id` names the box). Such a disc has no Discogs release; its
title, tracklist, cover and the rest are copied from the box's release into the
fields the admin overrides, and the drawer says "In the box" on the disc and
"Inside the box" on the box. It has no `instance_id`, so a sync never flags it as
gone. `cron/apply_lists.php` builds them from a lists file (its `discs` key).

The crate (`js/crate.js`, `css/crate.css`) belongs to the shelf page alone. It is
a state of the floor view, not a fourth view, so it follows the same search and
format filter, and it holds whatever the floor is showing — pick the Vinyl chip
first for a crate of records only. Its discs are the ones the floor slides out
on hover, so a pressing comes out of the crate in its own colour.

Switching between Floor and Grid uses the same idea without the crate
(`js/morph.js`, `css/morph.css`): each record on screen is carried from where the
old view had it to where the new one lays it.

Clicking a record does the same with the drawer (`js/spotlight.js`,
`css/spotlight.css`): a copy of the sleeve, with the hover's third of a disc,
flies to the left of the page while the drawer opens on the right, the discs
slide the rest of the way out, and the title and basics come in above it. The
drawer keeps everything else. Closing plays it backwards, home to wherever the
page has that record now. It works from a floor tile, a grid cell, a list row
or a `#item-…` link, and stays out of the crate and off windows too narrow to
leave room beside the drawer, where the drawer opens as it always did.

Every public page draws a record the same way — `js/tiles.js` builds the sleeve,
the case and the discs that slide out of it, over the wooden floor in
`css/floor.css`. The shelf, an artist's era and the wantlist differ only in how
the records are arranged, never in what a record looks like.

## Requirements

- PHP 8.1+ with `pdo_sqlite` and `curl`
- A Discogs [personal access token](https://www.discogs.com/settings/developers)
  (free). It raises the rate limit from 25 calls a minute to 60, which halves
  the time a first sync takes — phase 3 below is one call per release.

## Local setup

```sh
cp config/config.example.php config/config.php
# edit config/config.php: owner_email, discogs.username, discogs.token
php -S localhost:8010 -t public dev-server.php
```

Then open <http://localhost:8010/setup> to create the one account (the email
must match `owner_email`), and sync from the dashboard.

`dev-server.php` exists because PHP's built-in server has no rewrite engine; it
mirrors `.htaccess` so `/admin` and `/lady-gaga` resolve locally exactly as they
do on the server.

> The built-in server is single-threaded. If you trigger `cron_sync.php` against
> it, that one request occupies the server for as long as the sync runs and
> every other request waits. Apache/FPM on the real host is fine.

## Syncing

A sync has three phases, and it is restartable at every point:

1. **Collection** — the copies, 100 per call. A few calls in all.
2. **Wantlist** — the same, for what Bruno wants.
3. **Details** — tracklist, images, identifiers, barcode. **One HTTP call per
   release**, against 60 a minute. A 500-record collection is therefore a
   ~10-minute first sync.

Because of phase 3, nothing tries to do it all in one web request:

- **The Sync button** (`/admin`) posts to `admin_sync` over and over, each call
  doing ~12 seconds of work and reporting progress. Closing the tab is harmless.
- **The cron URL** loops the same steps for up to 15 minutes, then stops; the
  next night's run continues the same job.
- **Sync with Discogs** on a record's edit page refreshes just that one record
  (`sync_one_item()`: its release detail plus its own collection entry — one or
  two API calls). It writes the same Discogs-side columns a full sync does and
  nothing Bruno typed.

After the first full sync, a nightly run only fetches what's new plus anything
whose detail has gone stale (45 days by default, in **Settings**), so it is
short.

### The daily cron

The dashboard shows the URL with the token already in it:

```
https://brunovida.si/music/cron_sync.php?token=…
```

Set `cron_token` in the config to enable it (`php -r "echo bin2hex(random_bytes(24));"`).
Without a token the endpoint answers 503 and refuses to run — a sync costs real
API calls, so it must never be startable anonymously.

Point any scheduler at it once a day. If the host's cron can run PHP directly,
use that instead and skip HTTP entirely:

```
17 4 * * * /usr/local/bin/php /path/to/cron/sync.php >> /path/to/data/sync.log 2>&1
```

### What a sync will never do

Delete anything. A copy that has left the Discogs collection is *flagged*
(`missing_since`), listed in the admin under "No longer on Discogs", and left
alone — it still carries hand-typed notes. Any later sync that finds it again
clears the flag.

## Artists and eras

An era (The Fame, Born This Way, Mayhem) is a row; what puts a record *in* one
is an `era_rules` row holding a Discogs **master** id. A master covers every
pressing of an album at once — the CD, the vinyl, the 2023 reissue — which is
why one rule usually does the whole album. Releases with no master (most promos)
are matched by their own release id.

Two ways to write a rule:

- On a record's own page: tick **"Put every pressing of this album in that
  era"** when choosing its era.
- On **Artists & eras → Eras**: paste a Discogs link or a master id.

Rules are reapplied on every sync, so a rule added today re-files everything it
matches tonight. An era chosen by hand on a single item is locked (`era_locked`)
and a sync won't move it.

Lady Gaga's twelve eras and their master ids were carried over from the
hand-written page this app replaced; the other three artists have their eras but
no rules yet.

## Signing in

There is one account and no registration page. `/setup` creates it once, only
for the address in `owner_email`, and refuses afterwards. To reset the password,
delete the row and run `/setup` again:

```sh
php -r 'require "includes/bootstrap.php"; db()->exec("DELETE FROM users");'
```

Sign-in failures back off per session (five tries, then a doubling wait), and a
wrong address takes exactly as long to answer as a wrong password.

## Deployment

The repo deploys by FTP to `public_html/`, and this folder lands at
`public_html/music`, i.e. `brunovida.si/music`.
Only `public/` is web-reachable: `.htaccess` maps every request into it, and
`config/`, `includes/`, `cron/`, `sql/` and `data/` each carry a deny-all
`.htaccess` as well.

### The instance directory

**Do not put `config/config.php` on the server.** The repo is public and the
deployed folder sits under `public_html`. Instead create, *above* `public_html`:

```
/home/<user>/domains/<domain>/music-instance/config.php   <- the real config
/home/<user>/domains/<domain>/music-instance/data/        <- the SQLite database
```

The app walks up the tree looking for a directory named `music-instance`, so no
absolute server path is ever hardcoded. The dashboard's "Where things are" panel
reports which config and database it actually found — check it after the first
deploy.

### First deploy

1. Push to `main`; the GitHub Action FTPs the tree up.
2. Create `music-instance/config.php` with `'env' => 'production'`, the Discogs
   token, `owner_email` and a `cron_token`.
3. Either upload the dev SQLite database as `music-instance/data/collection.production.sqlite`
   to carry over everything already synced locally, or leave `data/` empty and let
   `/music/setup` + a first sync build it fresh on the server.
4. Open `/music/setup` and create the account (skip this if you uploaded the dev
   database — the account already exists in it).
5. Sync from the dashboard (the first one takes a while — see above), or skip it
   if you uploaded a database that's already synced.
6. Add the cron URL to a scheduler.

## Project structure

```
.htaccess              every request -> public/ ; pretty URLs ; artist slugs
dev-server.php         the same routing for `php -S`
config/                config.example.php (the real one is gitignored)
sql/schema.sql         the whole schema, applied on every request
includes/
  bootstrap.php        loads the app (the public pages, the API and cron use it)
  admin.php            bootstrap.php + the admin helpers + a session
  config.php           instance directory, paths, credentials
  runtime.php          error reporting, the session cookie
  db.php               PDO, migrations, settings
  helpers.php          escaping, requests, redirects, dates, Discogs links
  auth.php, csrf.php   the one account
  artists.php          artist and era lookups
  fields.php           what a record has; Bruno's values over Discogs'
  regions.php          two-letter region codes for the lists
  discs.php            the discs in a sleeve: count, colours, pictures
  items.php            rows -> the cards the shelves draw
  drawer.php           rows -> the drawer's facts and sections
  hero.php             the header every public page shares
  DiscogsClient.php    the API, throttled (writes are stubbed, see below)
  sync.php             the restartable sync
  admin_ui.php         the admin's page frame and shared markup
  admin_records.php    saving records from the edit pages
  seed_data.php        first-run artists and Lady Gaga's eras
  templates/           page heads and footers, the error page, the admin layout
public/
  index.php            the shelf
  artist.php           one page for every artist
  wantlist.php         what's missing
  selling.php          what's for sale
  admin*.php           the admin
  cron_sync.php        the token-protected daily trigger
  api/                 collection, artist, item, wantlist, selling (JSON)
  css/floor.css        the wooden floor and the objects on it — every public page
  css/artist.css       the era spine, layered on floor.css
  js/common.js         API + drawer, shared by every public page
  js/tiles.js          sleeves, cases and the discs that slide out of them
  js/shelf.js          what the page scripts share: prefs, format chips, toggles, loading
  js/spotlight-core.js the spotlight's flight, for the collection's and the shop's
  js/hero.js           the header's numbers counting up, and the collections menu on a phone
  js/controls.js       the search box that folds to a glass on a phone, and the format chips' scroll
  js/dropdown.js       the site-styled dropdown that stands in for a <select>
  js/admin-edit.js     the record and listing edit pages' shared behaviour
cron/sync.php          the CLI equivalent of cron_sync.php
cron/apply_lists.php   applies an artist's hand-kept lists
```

## Writing back to Discogs

Not built yet, deliberately. `DiscogsClient::request()` already takes a method
and a body, and the personal access token already authorises writes, so adding
"move this copy to another folder" or "push my rating back" is a method each —
the endpoints are listed in a comment in `DiscogsClient.php`. What is missing is
the part that deserves care: nothing about a wrong POST against a real
collection is undoable from here.

## Security notes

- The Discogs token, the cron token and the password hash live outside the repo
  and outside the web root.
- The database is denied over HTTP twice: by `data/.htaccess` and by a
  `FilesMatch` on `.sqlite` in the app's own `.htaccess`.
- Every admin form is CSRF-checked; the session cookie is scoped to this app's
  path, `HttpOnly`, `SameSite=Strict`, and `Secure` in production.
- Everything from Discogs is escaped on output, and only `http(s)` URLs from the
  API are ever turned into links.
- `api/` responses are anonymous and cacheable, and start no session — a hidden
  or removed record 404s there rather than opening.
