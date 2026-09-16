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
# edit config/config.php with your eBay App ID / Dev ID / Cert ID

php -S localhost:8000 -t public
```

Visit `http://localhost:8000`, create an account, and log in.

The SQLite database is created automatically on first request at `data/app.sqlite`
— no migration step needed.

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

## Going to production

Once you're happy with Sandbox testing:

1. Apply for/verify production keys in the eBay Developer Program and fill in
   `config.php`'s `ebay.production` block.
2. Create a **second RuName** under **Production Keys** (same idea, pointing at
   your real domain's `/ebay_callback.php`).
3. Set `ebay_environment` to `production` in `config.php`.
4. Reconnect your eBay account from the app (production tokens are separate from
   sandbox ones).

## Deploying to DirectAdmin (or any shared PHP host)

1. Upload the whole project outside the web root if possible, e.g.
   `/home/youruser/ebay_bidder/`, and point your domain/subdomain's **document
   root** at `.../ebay_bidder/public`.
   - If you can't change the document root, `.htaccess` files are already in
     `config/`, `includes/`, `cron/`, `sql/` and `data/` to block direct web access
     to those folders as a fallback — but a dedicated document root is safer.
2. Copy `config/config.example.php` to `config/config.php` on the server and fill
   in real values, with `app.base_url` set to your real domain.
3. Make sure `data/` is writable by PHP (it is by default once uploaded under your
   own account).
4. In DirectAdmin → **Cron Jobs**, add a job that runs every minute:
   ```
   * * * * * php /home/youruser/ebay_bidder/cron/snipe.php >> /home/youruser/ebay_bidder/data/cron.log 2>&1
   ```

## Project structure

```
config/     Config loader + your real config.php (gitignored)
includes/   Shared PHP: db, auth, csrf, EbayClient, layout partials
public/     Web root — every page users load lives here
cron/       snipe.php, run once a minute by the host's cron
sql/        schema.sql, applied automatically on first run
data/       SQLite database file lives here (gitignored)
```

Adding a new page later is just: drop a new `.php` file in `public/`, `require
__DIR__ . '/../includes/bootstrap.php'` at the top, and use `require_login()`,
`db()`, etc. No routing config to touch.

## Security notes

- `config/config.php` (your real eBay keys) and `data/*.sqlite` (your database,
  including connected eBay auth tokens) are gitignored and must never be
  committed — this matters even more once this repo is made public.
- Passwords are hashed with PHP's `password_hash()`; eBay auth tokens are stored
  as given by eBay (they're already opaque, revocable tokens, not your eBay
  password).
