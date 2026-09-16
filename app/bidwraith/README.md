# Bidwraith

_eBay Bidder_

A small personal web app for auction sniping on eBay: add item IDs to a watchlist
with up to 5 scheduled bids each (e.g. a moderate bid at 10s before the end, a
higher one at 3s, your real ceiling at the last second), and a cron job fires them
at the exact right moment — even if your browser is closed.

Built as plain PHP + SQLite on purpose: no framework, no build step, no Composer
install required. Copy the files to any PHP host and it runs.

## How it works

- **Web app** (`public/`): log in, add auctions by eBay item ID with a ladder of
  timed bids, see live status/current price and an edit page per auction on the
  dashboard.
- **eBay connection**: you authorize the app once via eBay's sign-in page (like any
  "Connect your eBay account" flow — this app never sees your eBay password).
  Bidding uses eBay's Trading API `PlaceOffer` call, the same mechanism eBay's own
  proxy bidding uses: you set a max, eBay auto-raises your bid up to that max as
  others bid against you.
- **Sniping** (`cron/snipe.php`): a host cron job runs this script every minute. It
  looks for any watched auction ending in the next ~65 seconds, sleeps until a few
  seconds before the exact end time, then places the bid. This works around shared
  hosting only offering 1-minute cron granularity.

## Requirements

- PHP 8.0+ with `pdo_sqlite` and `curl` extensions (both are on by default on
  virtually every host, including DirectAdmin/cPanel shared hosting).
- A cron job that can run a PHP script once a minute.
- An eBay Developer account with API keys (Sandbox keys work for testing).

## Local setup

```bash
cp config/config.example.php config/config.php
# edit config/config.php with your eBay Sandbox keys

php -S localhost:8000 -t public
```

Visit `http://localhost:8000`, create an account, and log in. The SQLite database is
created automatically on first request — no migration step.

`config/config.php` and `data/*.sqlite` are gitignored. **This matters more than
usual here:** the app is deployed as a subfolder of a public repository, so the
`.gitignore` in this folder is what keeps your eBay keys and the token database out
of it. Don't remove those rules.

## Connecting your eBay account (Sandbox)

Bidding requires an eBay **Auth'n'Auth** user token (the older token type Trading
API bidding still uses — it was never migrated to modern OAuth). To get one, you
need a **RuName** configured for your app:

1. Go to the [eBay Developer Program](https://developer.ebay.com/) → **My Account
   → Sandbox Keys**.
2. Under "Get a Token from eBay via Your Application", create a RuName. Set:
   - **Auction privacy policy / consent URLs**: any placeholder page is fine for now.
   - **Accepted URL**: `{your base_url}/ebay_callback.php`
     (e.g. `http://localhost:8000/ebay_callback.php` for local testing).
   - **Rejected URL / Declined URL**: `{your base_url}/connect_ebay.php`
3. Copy the generated RuName into `config/config.php` under `ebay.sandbox.ru_name`.
4. In the app, go to **eBay account → Connect eBay account**, sign in with a
   [Sandbox test user](https://developer.ebay.com/api-docs/static/gs_create-a-test-account.html),
   and you'll be redirected back once connected.

**Sandbox limitation**: the Buy Browse API in Sandbox only resolves items that
exist in your own sandbox test-seller inventory — it will not find real,
production item IDs from ebay.com. When adding a real item ID for testing the UI
flow, the app will ask you to enter the end time manually instead; the actual
`PlaceOffer` bid call still needs a Sandbox test listing (or production
credentials) to succeed for real.

## Deployment

The app is deployed at `https://app.brunovidasi.com/bidwraith` as part of the
brunovidasi.com website repo, which pushes to the server over FTP via GitHub Actions.

### The instance directory

The real config and the database must live **outside** the deployed tree, because the
repo is public and `public_html` is web-reachable. Create this once, by hand, above
`public_html`:

```
/home/<user>/domains/brunovidasi.com/
├── bidwraith-instance/          <- create manually, never deployed
│   ├── config.php               <- from config/config.example.php, with 'env' => 'production'
│   └── data/                    <- database, cron log (created automatically)
└── public_html/
    └── app/bidwraith/           <- deployed by GitHub Actions
```

The app finds this folder by walking up the directory tree looking for
`bidwraith-instance`, so no absolute server path is hardcoded anywhere in the repo.
Set `BIDWRAITH_INSTANCE` to override the location.

### First deploy

1. Push to `main`; the existing workflow uploads `app/bidwraith/` with the rest of the site.
2. Create `bidwraith-instance/config.php` via DirectAdmin's File Manager (copy
   `config.example.php`, set `'env' => 'production'` and fill in the eBay keys).
3. Open `/bidwraith/preflight.php`. While no account exists it's open to anyone; it
   reports PHP version, extensions, resolved paths, and prints the exact cron line.
4. Create your admin account at `/bidwraith/setup_admin.php`. It disables itself
   permanently once any account exists, and closes off `preflight.php` too.
5. Add the cron job (below), then reload `preflight.php` — every check should pass.

### Triggering the bidder

Something must run a bid pass every minute. There are two ways, and the app shares
one implementation (`includes/snipe_runner.php`) between them.

**Host cron**, if the host's cron daemon runs your jobs. `preflight.php` prints the
line with real paths; in DirectAdmin use every field `*` and leave the notification
email blank:

```
* * * * * /usr/local/bin/php /home/<user>/domains/<domain>/public_html/app/bidwraith/cron/snipe.php >> /home/<user>/domains/<domain>/bidwraith-instance/data/cron.log 2>&1
```

**External scheduler**, for hosts where cron doesn't run at all — which is the case
on this one. Set `cron_token` in the config, then point any service that can fetch a
URL once a minute at:

```
https://app.brunovidasi.com/bidwraith/cron_http.php?token=<cron_token>
```

The endpoint answers immediately and finishes the pass in the background, so a
scheduler's short request timeout is fine. **It must run every minute** — a pass only
looks 65 seconds ahead, so a 5-minute scheduler (including GitHub Actions `schedule:`,
which is also imprecise by several minutes) will miss bids.

Treat the URL as a password. Rotate it by changing `cron_token`.

Either way, the admin dashboard shows when a pass last ran. If that goes red, bids
are not firing.

### Switching to production eBay

`ebay_api` is deliberately independent of `env`, so the live site can run against
sandbox eBay while production keys are still being issued. Once you have them:

1. Fill in `ebay_keys.production` in the instance config.
2. Create a production RuName whose Accepted URL is
   `https://app.brunovidasi.com/bidwraith/ebay_callback.php`.
3. Set `'ebay_api' => 'production'` in the production environment block.
4. Reconnect your eBay account — production tokens are separate from sandbox ones.

## Project structure

```
config/     config.example.php; your real config.php locally (gitignored)
includes/   db, auth, csrf, config, runtime, EbayClient, layout partials
public/     Web root — every page users load, plus preflight.php and setup_admin.php
cron/       snipe.php, run once a minute by the host's cron
sql/        schema.sql, applied automatically on first run
data/       SQLite database locally (gitignored); on the server this lives in the
            instance directory instead
.htaccess   Maps every request into public/ so the rest of the project isn't web-reachable
```

Adding a page is: drop a `.php` file in `public/`, `require
__DIR__ . '/../includes/bootstrap.php'`, and use `require_login()`, `db()`, etc.

## Environments

`config.php` has one `env` key selecting a block that controls the base URL, database
file, data directory, eBay API side, error display and whether public registration is
open. Only that one line differs between a laptop and the server.

eBay credentials are keyed by API side (`ebay_keys.sandbox` / `ebay_keys.production`)
rather than by environment, so both environments can share sandbox keys during rollout.

## Security notes

- The real `config.php` and the database live outside the repo and outside the web
  root. Neither is committable; neither is downloadable.
- `.htaccess` maps all requests into `public/`, so `config/`, `includes/`, `cron/`,
  `sql/` and `data/` return 403/404. Each also carries its own deny-all `.htaccess`.
- Session cookies are scoped to `/bidwraith/`, `HttpOnly`, and `Secure` in production.
  `SameSite` is `Lax`, not `Strict`, because eBay's sign-in redirect must carry the
  session back — `Strict` would silently break connecting an account.
- Passwords are hashed with `password_hash()`. eBay auth tokens are stored as issued
  by eBay (opaque and revocable, not your password).
- Errors are displayed in development and logged to file in production.
