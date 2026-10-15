# Bruno's Game Shelf

The game collection site: black, finely pinstriped pages with a blue accent and a dark wooden bookcase, set in Orbitron (the big display text) and Exo 2 (everything else). It started from the record site (`brunovida.si/music`); the two are separate sites.

- **The bar**: the console filter sits under the header, and the bar under it has the search and the sort (a dropdown in the site's own style) on the left and the views on the right, with how many games are showing on the line below, as on the record site. On a phone the search folds into a magnifying glass that opens across the bar.
- **The shelf** (`public/index.php`): every game on a wooden bookcase, grouped by console, in that console's real case at its real size. Loose cartridges stand on their own (a SNES cartridge at its real size beside the cases, the small cards larger so they can be seen); SNES cartridges stand on their side in a row, like books, with the title on their top label facing out, beside the one that faces out. A PAL one, or an unofficial one, has nothing on its top edge, so it faces out too. The spines are drawn wider than real ones so their titles can be read. Each shelf is only a little taller than the tallest game on it. Like a real shelf, it is a bit messy: on most shelves the last game or two lean over onto the one before, and so do some at the end of a console before its bookend. The plate under each console is in its family's colour, as in the filter: red Nintendo, blue PlayStation, green Xbox. The console filter at the top shows a drawing of each console.
- **The grid**: every game face out, standing on one line at its real size, with its name under it. Going between *Shelf* and *Grid* carries every case on screen across, as the record site does between its floor and its grid: each case comes off the shelf and turns from its spine to its cover on the way, and turns back to its spine on the way home. Between the shelf and the rows each spine widens or narrows smoothly in the air to the width it lands at, and a case picked from the rows leaves at the row's spine width and narrows to its real depth as it turns (and widens again on the way home). A SNES cartridge standing on its side turns from its top label to its front the same way, as one solid cartridge, and so it does when it is picked off the shelf.
- **The rows**: each console on a strip of shelf of its own that scrolls sideways. The cases stand as on the shelf, their spines out (*Show* works as on the shelf), but taller and with each spine much wider than a real one, so they are easy to tap on a phone. Swipe a row to go along it, or with a mouse use the arrows that show at either end; a scroll down over a row still scrolls the page. Sorted any way but by console, the games stand on one long row.
- **The showcase**: one game at a time on a glossy stage, with the rest fanned out either side and the light behind it in the colour of its cover, as in `prototypes/3-showcase.html`. Drag, scroll sideways, use the arrows or the arrow keys to move along, or jump with the strip under it (a block per console). The game in the middle is picked up like anywhere else, and one to the side comes to the middle first. The cases fly across between the showcase and the other views, as between the shelf and the grid.
- **The list**: the same games as a sortable table, like the record site's list.
- **A game up close**: click a case on the shelf, in the grid or in the list. It lifts out of its place, which stays empty, and flies to the middle of the screen with its name above it and its details below. Drag it to turn it round, or use *Front*, *Spine* and *Back*. *Open case* shows the disc, and *Take it out* lifts the cartridge out of its box. The arrows at the bottom (or the arrow keys) go to the next and previous game. On a big screen the previous and next games stand smaller to the left and right; stepping, the game in the middle moves aside into the place beside the next one as that one comes in from its own, and a click on either one steps to it; closing flies it back into its place. On a phone held sideways the details stand beside it.
- **The collections**: *The Sims* and *GTA* in the top bar are filters on the same page, not pages of their own. Picking one (`/sims`, `/gta` in the address) leaves only its games on the shelf, gives it its own header and a *Checklist* view, and changes the whole page's look (a class on `<html>`, `css/sims.css` and `css/gta.css`), fading from one to the other; *The Shelf* goes back to everything. The view, the sort and the consoles picked carry across where they can.
- **The Sims collection** (`/sims`): the page in the game's own clothes: a deep Sims-blue sky, glossy round blue panels, plumbob green for what is picked, a cream wallpapered room behind the bookcase, Fredoka and Nunito. A plumbob (`assets/plumbob.svg`, turning) bobs beside the title, and over whichever case the pointer is on, as over a picked Sim. Every Sims game and pack, on the same bookcase, in the same grid and list, grouped by game (The Sims, The Sims 2, The Sims Stories, The Sims 3, The Sims Medieval, The Sims 4, SimCity, MySims) rather than by console. Its *Checklist* view sorts them by game and pack, with how much of each is there and the want list. A solid case is on disc, an outline is in the EA app.
- **Grand Theft Auto** (`/gta`): the page after GTA VI's Vice City (the `leonida` style prototype): a sunset over the water with palms, the shelf as a collage of panels split by black gutters, Anton, Yellowtail and Manrope. Every GTA copy on the bookcase, grouped by game. Its *Checklist* goes era by era, one row per game with its copies on every platform and a dashed outline at the real case size for each version still to find. Those outlines are the want list at the bottom.
- **Design prototypes** (`prototypes/`): the four early designs, kept as they were.
- **Style prototypes** (`public/prototypes/styles/`): eleven looks for the same site, none of them live yet. `public/prototypes/styles/index.html` is a gallery of them with a screenshot of each, and every style has a full preview of the site in its own folder (`prototypes/styles/neon/`, and `?sims`, `?gta` on it). Each look is one stylesheet, `prototypes/styles/<name>.css`, loaded after the site's own CSS: `neon` (Neon Arcade), `hud` (Holo Vault), `pixel` (Pixel Quest), `dashboard` (Next-Gen Home), `museum` (Hall of Fame), `atomic` (Atomic Purple), `comic` (Game Mag), `console` (Super Console), `glitch` (Glitch City), `leonida` (Leonida, after GTA VI's Vice City) and `datacore` (Data Core). The top of each names the Google Fonts it needs. The previews are copies of the site's page, so after changing `index.html`, run `node tools/style-previews.mjs` to rebuild them.

## How it is built

The site is PHP with a SQLite database, built the same way as the record site (`brunovida.si/music`), and has an admin behind it at `/admin`:

- **The front end** is the same page as before (`public/index.php`, `public/js/`, `public/css/`). It reads the collection from the site's own JSON API (`api/games`, `api/steam`, `api/psn`, `api/nintendo`, `api/xbox`, `api/ea`, `api/rockstar`, `api/sims_wants`, `api/gta`), which hands it exactly what the old `data/*.json` files had, so nothing on the page had to change. The site's name, each page's title, description and header, the views it offers and the "Add me" links come from Settings.
- **The admin** (`/admin`): the dashboard, the collection, Sync History and Settings. One account, Bruno's.
- **The syncs** talk to Steam, PlayStation Network, Xbox, Nintendo, the EA app and Rockstar themselves, from the admin's buttons or a cron job, and write into the database. They were the PHP exporters in `api/` and the Node tools in `tools/` (still in the git history, and in the `games-website` repo).

Every address is a plain path, and every change is a step in the browser's history, so Back and Forward go through them. `/ps2` opens straight onto one console, and `/ps2-final-fantasy-x` opens one game. `/sims` or `/gta` opens a collection, with a console or a game after it (`/sims/wiiu`), or a section's name, which opens the checklist there: `/gta/want-list`. Opening a game adds a step, so Back closes it; going from game to game in the middle doesn't. The server sends these addresses to `index.php` (`.htaccess`, and `dev-server.php` locally), which 404s anything that isn't a console, a game or a collection. The old `#ps2` and `?gta#want-list` links still work: the page turns them into the new ones.

The collections are the same shelf (`public/js/app.js`): each adds itself to `window.COLLECTIONS` with its name, its games, how they group, its header and its checklist (see the top of `js/app.js`), and its look is a stylesheet scoped to its class on `<html>` (`.sims`, `.gta`).

## Local setup

Needs PHP 8.1+ with `pdo_sqlite`, `curl`, `gd` and `sodium` (all in the usual builds).

```sh
cp config/config.example.php config/config.php
# in config/config.php: owner_email, and an app_key:
php -r "echo bin2hex(random_bytes(32));"
php cron/import_json.php          # the collection from seed/*.json, once
php cron/verify_import.php        # checks the API hands back exactly what the files said
php -S localhost:8020 -t public dev-server.php
```

Then open <http://localhost:8020/setup> to create the one account (the email must match `owner_email`), connect the platforms in **Settings → Connections**, and sync from the dashboard.

`dev-server.php` exists because PHP's built-in server has no rewrite engine; it mirrors `.htaccess` so `/admin` and `/api/games` resolve locally exactly as they do on the server. The built-in server does one request at a time, so the admin waits while a sync runs; Apache on the real host doesn't.

`seed/` holds the JSON the site had before it had a database. It is only read by `cron/import_json.php` (`--force` to start over, which keeps the account, settings and sign-ins); after that, the database is the collection.

## The database

`sql/schema.sql`, applied on every request (`includes/db.php`). Columns added later go in `MIGRATED_COLUMNS` there.

- **`games`**: one row per copy, keyed by the id the site has always used. The fields the admin filters on are columns; the rest of what the shelf draws a copy with (spine, case colour, cartridge, box…) is in `extra_json`. Which field is where is one list, `FIELDS` in `includes/fields.php`, which the import, the edit form and the API all read.
- **`platform_records`**: what each platform says about each of its games (play time, achievements, trophies, the Steam store page), by the platform's id, as the old `data/steam.json`… had it. `platform_accounts` has the account-wide figures (trophy level, gamerscore).
- **`field_state`**: which of a copy's fields a sync wrote, and whether it was changed by hand since (`locked`).
- **`platform_overrides`**: synced numbers corrected by hand.
- **`sync_runs`**, **`sync_changes`**, **`platform_record_history`**, **`sync_snapshots`**: every sync, every value it changed (before and after), every record it replaced, and what the platform answered.
- **`sync_unmatched`**: games a sync couldn't tie to one copy.
- **`credentials`**: the platforms' keys and sign-ins, encrypted (below). **`connections`**: how each one last fared.
- **`wantlist`** (the Sims want list for now; the site's own wantlist will be rows here too) and **`gta_eras`**, **`gta_games`** (the GTA checklist).
- **`settings`**, **`users`**, **`login_failures`**.

## The admin

- **Dashboard**: the collection in figures, counted as the site counts it (a bundle is the packs in it); each platform's last sync, with a *Sync* button, and *Sync all*; what needs a look (games to tie by hand, covers missing on the server, games with something to check, a PSN sign-in about to run out); and where things are.
- **Collection**: every copy, with search and filters (console, physical or digital, launcher, region, series, pack, platform, favourites, hidden, no cover…). *Add a game* and each copy's edit page have every field the shelf uses. A field a sync wrote says so (*Synced from Steam · 12 Oct 2026*); changing it keeps it as you leave it, unless *Let future syncs update it* is ticked, and the platform's own value is shown beside yours. A synced number (hours played, trophies, achievements) can be corrected too: *Mine, always*, or *Mine, until the platform changes*. The cover can be uploaded, taken from an address, or found (below).
- **Consoles**: the machines themselves (the `hardware` table), each with its model, colour, region, year, release date, manufacturer, serial number, manufacture date and everything its label says, its specs (from Wikipedia), a photo of the model (from Wikimedia Commons, in `public/covers/consoles/`, credited with its author and licence), and other markings as JSON (product id, FCC ids, the Wii U GamePad). Only the admin shows them for now: the site isn't handed any of it. The serial numbers stay out of the repo: `data/hardware.json` is gitignored, and `php cron/import_hardware.php` adds the consoles in it that the database doesn't have yet (`--update` also fills the fields still empty in the ones it has). The photos travel with the covers, by FTP.
- **Sync History**: every sync, and for each what it added, tied, filled and changed, with the value before and after, its log, and what the platform answered (to download). A platform's latest sync can be **undone**: the copies it added go, the fields it filled go back, and every record it replaced is put back.
- **Settings**: the site's name and each page's titles and texts (the shelf, The Sims, GTA); the views (shelf, rows, grid, showcase, list) and the one a first visit opens on; the "Add me" links; the platforms' sign-ins (Connections); the cron address and how many snapshots to keep (Sync); the password.

## Syncing

Each platform is a client in `includes/sync/` (`steam.php`, `psn.php`, `xbox.php`, `nintendo.php`, `ea.php`, `rockstar.php`) and they share `includes/sync/engine.php`: fetch, keep what was answered, turn it into the records the site shows, tie and add copies, then write it all in one transaction, noting every change. The matching rules are the old `tools/*-sync.mjs`'s, unchanged; `normalize()` gives the same records the old tools wrote, checked against the old exports.

What a sync never does: delete a copy, or change a field you typed. It adds a copy (a game bought in a launcher or on the eShop), ties one to a platform id, and fills a field that is still empty, or one it filled itself that you haven't changed. A game it can't tie to exactly one copy goes on the dashboard to tie by hand (or dismiss: a game played but not owned). To keep a synced game off the site, hide it: deleted, the next sync adds it back. A record the platform stops listing is kept and marked.

| Platform | Connecting (Settings → Connections) | Lasts |
|---|---|---|
| Steam | the Web API key, pasted once; **Sign in through Steam** fills in the SteamID64 (official API) | until the key is revoked |
| Xbox | **Connect Xbox**: sign in with Microsoft and come straight back, through a Microsoft app of your own registered once in Azure (the page has the steps; free). Without one, Microsoft's sign-in as the Xbox app does it, its last address pasted back | renewed by each sync (the app's client secret lasts up to 24 months) |
| PlayStation Network | the **Send to my shelf** bookmark on Sony's NPSSO page (or the token pasted), traded at once for a refresh token | about two months, renewed by each sync |
| Nintendo | the Nintendo Account sign-in as the Nintendo Store app does it, then the **Send to my shelf** bookmark on its *Select this person* page (or its button's link pasted) | about two years |
| EA app | EA's sign-in as the EA app does it: the `location` of its last request, from the browser's developer tools | renewed by each sync |
| Rockstar | the site's sign-in check, copied as cURL from the developer tools | as long as the rockstargames.com sign-in |

Only Microsoft and Steam let other sites sign you in and send you back: Sony, Nintendo, EA and Rockstar send a sign-in to their own apps, so it is carried here by hand or by the bookmark. EA's code is in a redirect no page can read, and Rockstar's sign-in cookies are hidden from scripts, so those two stay manual; both are done once. The returns land on `admin/connect`, which hands them on to Settings from the site itself (the admin's cookie is `SameSite=Strict`, so it is left off a page another site sends you to), where they are checked against the session that started them; the bookmark brings its sign-in in the address's `#fragment`, which no server sees.

Everything but Steam is undocumented and can change without warning, which is why every sync keeps its before-and-after and can be undone. A renewed sign-in is saved the moment it arrives.

**On a schedule**: Settings → Sync makes a secret address,

```
https://brunovida.si/games/cron_sync.php?token=…              every platform ticked there
https://brunovida.si/games/cron_sync.php?token=…&platform=psn  one
```

for any scheduler that can open an address (without a token it answers 503). If the host's cron runs PHP, use the command line instead:

```
17 4 * * * /usr/local/bin/php /path/to/games/cron/sync.php >> /path/to/games-instance/data/sync.log 2>&1
```

## Signing in

There is one account and no registration page. `/setup` creates it once, only for the address in `owner_email`, and refuses afterwards. To reset the password, delete the row and run `/setup` again:

```sh
php -r 'require "includes/bootstrap.php"; db()->exec("DELETE FROM users");'
```

Sign-in failures back off per IP address (five tries, then a doubling wait), and a wrong address takes exactly as long to answer as a wrong password. Every admin form is CSRF-checked; the session cookie (`GAMES_SESSION`) is scoped to the app's path, `HttpOnly`, `SameSite=Strict`, and `Secure` in production.

The platforms' keys and sign-ins are kept in the database encrypted (libsodium) with the config's `app_key`, so the database alone gives none of them away; the admin never shows one again once saved. Keep the same `app_key` when moving the database, or they have to be entered again.

## Deploying (later)

The plan is the record site's: this repo becomes the `games/` folder of the `brunovidasi.com` repo (as `music/` is), deployed by its FTP Action to `public_html/games`, i.e. `brunovida.si/games`. Only `public/` is web-reachable: `.htaccess` maps every request into it, and `config/`, `includes/`, `cron/`, `sql/`, `seed/` and `data/` each carry a deny-all `.htaccess` as well.

**Do not put `config/config.php` on the server.** Create, *above* `public_html`:

```
/home/<user>/domains/<domain>/games-instance/config.php   <- the real config, with 'env' => 'production'
/home/<user>/domains/<domain>/games-instance/data/        <- the SQLite database
```

The app walks up the tree looking for a directory named `games-instance`, so no server path is hardcoded; the dashboard's *About the site* shows which config and database it found. Then either upload the local database as `games-instance/data/games.production.sqlite` (with the same `app_key`), or run `/games/setup` and the import there. **The covers aren't in the repo** (`public/covers/` is gitignored; the database names each file): upload the folder by FTP once. The dashboard counts any it can't find. New covers from the admin are written there on the server.

## The fields of a copy

What the site is handed for each copy (`api/games`, the shape `data/games.json` had), and what the admin's edit page has a box for:

| Field | Example | Notes |
|---|---|---|
| `id` | `"ps2-final-fantasy-x"` | console + title, unique |
| `console` | `"ps2"` | which shelf it sits on. Ids are in `js/consoles.js` |
| `title` | `"Final Fantasy X"` | the official title |
| `listedAs` | `"Nárnia"` | how it was written on the original list, when different |
| `sortAs` | `"Rock Band"` | optional: where it files when sorted by title, when that isn't its title. Titles already file without a leading The, A or An (The Sims under S), so this is only for the rest: LEGO Rock Band and The Beatles: Rock Band with the Rock Band games, Disney's Tarzan under T. Games that file under the same name stand in the order they came out |
| `sameAs` | `"Bully: Scholarship Edition"` | optional: the title of the game this copy is the same game as, when its own title differs (Canis Canem Edit, Bully's European name). Copies with the same title already find each other, on other consoles or on the same one in another format: the drawer lists them all together. `false` keeps a copy apart from a different game of the same title (Just Dance on the Switch) |
| `edition` | `"Nintendo Switch 2 Edition"` | optional |
| `region` | `"AUS"` | the region code on the box: `AUS`, `EUR`, `HOL` (Netherlands), `UK`, `USA` or `JPN`. When it isn't known, just `PAL`, `NTSC-U` or `NTSC-J` |
| `released` | `"2001-07-19"` | first release on that console. Can be `"2001-07"` or `"2001"` |
| `dateSource` | `"manual"` | `libretro-database`, `steam` (the Steam store's date, which for an older game is its Steam release), or `manual` (from memory, worth checking) |
| `publisher`, `developer`, `genre` | | |
| `format` | `"boxed"` | `boxed`, `cartridge-only` (stands loose on the shelf) or `digital` (an outline) |
| `kinect` | `true` | optional: a Kinect game, drawn in a purple case |
| `platinum` | `true` | PS2 and PS3: a Platinum copy. On the PS2, the silver band across its cover and a silver spine. On the PS3 it comes in a silver case instead of the clear one, and its `cover` should be the Platinum insert (yellow, with the Platinum band). Every PS2 game has it, `false` until set |
| `caseColor` | `"#d8000f"` | optional: a case in another colour than its console's usual one (a red Wii case, a white Wii U one). It colours the plastic and the spine, and the spine's writing turns light or dark to suit. PS2 cases are blue by default; give the black ones `"#121212"` (only the plastic changes, the white paper spine stays). So does EA's printed PC GAME spine (`"style": "ea-pcgame"`, the default for `bigCase`): only the plastic takes the colour, as on the Sims' grey cases |
| `cartColor` | `"#f2c318"` | optional: a cartridge in its own colour (a yellow Pokémon Yellow, a smoky clear Game Boy Color cartridge). It colours the plastic all round. Game Boy Color games come in the clear Game Boy Color-only shell (no notch, GAME BOY COLOR across the top, the circuit board showing through); one in the Game Boy's shape (dual-mode, Pokémon) has `"caseStyle": "gb"` |
| `cartGlitter` | `true` | optional: glitter in the plastic (Pokémon Gold and Silver) |
| `cartBattery` | `true` | optional: a clear Game Boy Color cartridge with a save battery, which shows through the shell above the label (or `[x, y]`, its middle in mm) |
| `cartMould` | `"GAME"` | optional: what an unofficial Game Boy shell has moulded on the front instead of Nintendo GAME BOY™ (`"GAME AMY"`: the first word big, the rest small) |
| `cartBack` | `"pat-pend"` | optional: an unofficial Game Boy shell's back: `"pat-pend"` (PAT. PEND. alone, no MADE IN JAPAN) or `"bare"` (nothing moulded, no dimple) |
| `cartScrew` | `"phillips"` | optional: a cross-head screw in the back instead of Nintendo's tri-wing (on a SNES cartridge, the two in front instead of its security screws) |
| `cartLabel` | `[25.8, 3.2, 106.1, 43.7]` | optional: an unofficial SNES cartridge's own label (a multicart's), where it is stuck on the shell, in mm from its top left corner: left, top, right, bottom. The printed Nintendo label is left off |
| `topLabel` | `false` | optional: a SNES cartridge with no label over its top edge (an unofficial one). It faces out on the shelf |
| `steelbook` | `true` | optional: a SteelBook (tin case). Drawn as metal with rounded corners and no plastic rim, its art to the edge, and a metal spine tinted in the cover's colour. Its `cover` is the tin's own art, not the box's, and `cover.back` (any game can have one) is a picture of its back, shown on the back of the case |
| `caseStyle` | `"ps4"` | optional: drawn in another console's case (a PS4 disc on the PS5 shelf) |
| `shelfDate` | `"2000-02-05"` | optional: the date it files under on the shelves (the whole shelf's and a collection's) and the checklist instead of its release date (The Sims Deluxe Edition right after The Sims) |
| `box` | `{ "w": 148, "h": 208, "d": 60 }` | optional: a copy sold in a box of its own instead of its console's case (a bundle, a collector's edition), its size in mm |
| `contains` | `["pc-the-sims-4-spa-day", …]` | optional: the copies a bundle came with, by id (The Sims 4 Bundles, a code in a box for three packs). They stand inside the bundle instead of on the shelf: opened, its case holds the code's slip with each of them on it (with its icon from the spine), its drawer lists them, and a search for one, its link or a click on it anywhere opens the bundle. They stay copies of their own, so the checklist still ticks them off ("in The Sims 4 Bundle 1") and the collection still counts them |
| `model` | `"sims3-ce"` | optional: a special box built in 3D from its parts instead of as a block (`js/cases.js`, `MODELS`). `sims3-ce` is The Sims 3 Collector's Edition: the clear box, the card sleeve round its lower half, the DVD's holder across the back with its green panel, and the plumbob USB drive standing in the window, which *Take it out* lifts out of the lid with its cap off the plug. `medieval-ce` is The Sims Medieval Collector's Edition: a gold book in a card sleeve open at both ends (its gilt page edges show there); *Take it out* slides the sleeve off and sets it behind, and the cover swings open on the kingdom's map, the plumbob board and the open fore-edge with the DVD case in it. Its extra photos are in `cover.parts` |
| `note` | `"No guitar controller"` | shown under the details |
| `fav` | `true` | optional: faces out on the shelf in "One cover per console" |
| `faceOut` | `true` | optional: faces out (`true`) or stands on its side (`false`) on the shelf in "One cover per console", whether or not it is a `fav` (and, with `false`, even a cartridge with no top label, which is then drawn with its title on its top like the others) |
| `reproduction` | `true` | optional: a reproduction cartridge, not an official one. Only a record: it doesn't change how the game is shown |
| `hidden` | `true` | optional: kept in the list but left off the site (off every shelf and out of the counts) |
| `check` | | something still to confirm. Not shown on the site |
| `cover` | `{ "file": "covers/ps2/final-fantasy-x.jpg", … }` | the box art, or `null`. `"plain": true` marks a picture that is only the game's key art, not that console's box (the PS5 and Switch 2 pictures from Wikipedia): the case keeps its real size, the console's band is printed across the top and the art fills the rest. `cover.spine` and `cover.side` are pictures of a box's real left and right sides: the left is its spine on the shelf, the right shows in the showcase |
| `colors` | `["#fcfcfc", …]` | taken from the cover. Some spines use them |
| `spine` | `{ "style": "white", "top": "pccd", "title": "The Sims™ 2 Seasons *expansion pack**", "foot": ["M", "ea"] }` | optional: how the copy's spine is printed, drawn in that box family's own design (below) instead of the plain one |

To add a game, copy an entry, change it, and set `cover` and `colors` to `null`. Then fetch its cover (below).

**PC games**: use `"console": "pc"` and add `"launcher"` (`steam`, `origin`, `ea`, `rockstar`, `battlenet`, `gog`, `ubisoft` or `none`). Add `"big": true` for a big-box release (cardboard), `"bigCase": true` for one in the thick plastic case of the early 2000s (EA's *PC GAME* and *PC CD* cases: a DVD case's height and 28 mm deep, with a CD inside), `"mac": true` when it plays on a Mac too, and `"alsoDigital": true` for a disc that is also in the launcher's library. A disc in a clear case instead of the usual black one gets `"clearCase": true` (San Andreas, Bully, the Sold Out pack). A copy in the Steam library has `"steamAppId"` (below).

## Printed spines

A game's `spine` draws its spine as the real box prints it. Every part is drawn, so it stays sharp at any size; nothing is a photo. A game in a thick EA case (`bigCase`) gets `{ "style": "ea-pcgame" }` by default.

- `style`: `ea-pcgame` (EA's thick PC cases of the early 2000s: black ends, the cover's own colour down the middle), `white` (the plain white spine of the DVD-case years), `sims4` (a band in the pack's colour at each end round The Sims 4's logo, the pack's icon and name in the lower one), `sims4-bundle` (a Sims 4 Bundle's light grey spine: the packs' round icons, `icons`, from `logos/sims4-icons/`, cut from a photo of the spines, then the logo and `sub`, BUNDLE) or `art` (SimCity 2013's: the title in white, in its logo's red shadow, on the white spine).
- `top`: the platform's logo: `pcgame`, `pccd` (white on a black strip), `pccd-tile` (PC on a red tile), `pcdvd`, `pcdvd-mac`, `pcdvd-mac-dl` (with DIGITAL DOWNLOAD), `pcmac` (PC/MAC and the download arrow), `pcdvd-block` and `pccdrom-block` (PC DVD-ROM or PC CD ROM in white on a dark block: Bully, Sold Out), or `gfw` and `gfw-live` (Games for Windows, with LIVE and its orange line). On a spine with its own `color` the PC CD and PC DVD-ROM strips take that colour. `head`: badges straight under the top, as `foot` (San Andreas' Rockstar logo).
- `title`: the words as printed, when they differ from the game's title. `*…*` is set smaller (`*expansion pack**`), `\n` breaks the line, `™` and `®` are set small. A long title is set smaller to fit. `caps: true` sets it in capitals throughout (THE SIMS™2 NIGHTLIFE); `font: "plain"` in a plain rounded type (MySims), `"arial"` big in plain Arial (LucasArts' Grim Fandango) or `"copper"` in Copperplate capitals (Sold Out).
- `logo`: the series logo instead of a title, from `logos/` (the logos are turned to read down the spine: the Sims Wiki's transparent pack logos, Wikimedia Commons' The Sims 4 logo). `sub` is the pack's name after it, `small` the smaller line beside it (EXPANSION PACK, LIMITED EDITION), and `subStyle` prints them another way: `gold` (Episodes From Liberty City) or `plaque` (Bully's SCHOLARSHIP·EDITION). `wide: true` sets a wordmark narrower and in the middle of the spine: the GTA ones (`logos/gta-*.webp`, each the stacked logo from Wikimedia Commons cut into its words and set on one line, as the spines print it) and Bully's.
- `stripe`: a thin line in that colour under the top (green for The Sims Stories, black for the Sims 2 DVDs, red for Pets). `color`: the band's own colour instead of white or the cover's art. `accent`, `label` and `icon` (`together`, `work`, `city`, `paw`, `seasons`, `famous`, `island`): the colour, the EXPANSION PACK line and the pack's icon of a Sims 4 spine. `band: "classics"`: EA Classics' blue band. `deco: "soldout"`: Sold Out Software's logo, orange band and fine lines before the title.
- `foot`: the badges at the foot, top to bottom: ratings (`M`, `G`, `PG`, `MA15`, or a number such as `12` for a British one) and EA's logo as that year printed it (`ea` with its green ring, `ea-gold`, `ea-silver`, `ea-yellow`, `ea-red`, `ea-green`, `ea-games` the silver EA GAMES badge, `ea-mark` the plain letters). For the Rockstar years: `rockstar` (its logo), `MA15R` (Australia's later MA15+ label, RESTRICTED under it), `bbfc15` and `bbfc18` (British ones), `pcdvd` or `pcdvd2` (PC DVD in the grey block at the foot, on one line or two) and `txt:` small print across the spine (`txtv:` to read down it: a catalogue number). The ratings are turned to read down the spine as the Sims boxes print them; a badge ending in `^` stands upright instead (`PG^`).

## Cases and cartridges

Each case is drawn at its console's real height from `js/consoles.js`, so a DS case is shorter than a PS2 one and a PS3 case sits between them. A PS3 case is clear plastic with a faint blue tint, with the moulded strip across its top above the cover (the Blu-ray Disc logo and PLAYSTATION 3 raised in it), as on the real ones; the strip carries on over the top of the spine. The case is built around its cover scan: it takes its width from the scan, so the scan always shows whole and fills the front, with just the case's rim and hinge round it. Cardboard boxes (SNES, Game Boy) simply are their scan. A game without a scan gets its case's real width.

The cartridges and game cards (SNES, Game Boy, GBA, DS, 3DS, Switch, Switch 2) are drawn in `js/cases.js` with their real outlines and label areas. The label shows the box art without the console-name strip that Western boxes have down one side.

The SNES, Game Boy, Game Boy Color, GBA, DS, 3DS, Switch and Switch 2 cartridges are traced from photos of real ones: the SNES cartridge's ridged sides, grip and screws, with the Nintendo seal and the SNES logo either side of the art on its label; the PAL SNES cartridge (an `AUS`, `EUR` or other PAL copy, and a Japanese one too: it is the Super Famicom's shell) traced from Super Mario Kart SNSP-MK-AUS, at 129 × 87 × 20 mm, the size measured on real ones (Wikipedia's 130 × 86 is close; the photo was used for where each part sits, as a share of the width and height): rounded top corners, the middle half a millimetre above the sides at the top, five slots through each side, the band across the top over the wide label recess, the long ribbed grip with its middle third raised, the screws deep in their holes, and no top label; behind, fine ribs over the middle, the raised panel with the Nintendo logo and *PAT. PEND. MADE IN JAPAN*, the silver *IMPORTANT* sticker over the dimple (the copy's sticker is torn: what was left was read off it, the rest made up to match), the holes at the corners and two notches cut into the top of the back half only; the Game Boy's corner notch, raised logo and side rails; the GBA's ledge with the arch moulded across it and its label printed like a real one, the box art small in the middle with the seal and the code either side; the DS and 3DS cards' cut corner and white label with the console's logo across the top (and the tab sticking out of the top of the 3DS card's right side); and the Switch cards' red band over the art and code strip under it. Each cartridge is a solid: its front and back are its real thickness apart (20 mm for the SNES, 8 mm for the Game Boys, under 4 mm for the cards), with edges that follow its outline, so it turns in the close-up like the cases do. The SNES, Game Boy, Game Boy Color and GBA cartridges show the opening in their bottom edge with the gold edge connector inside. The backs follow photos of real ones: the DS and 3DS cards' long window of 17 contacts at the bottom under the moulded Nintendo logo and model number, the Switch cards' five windows of contacts, the GBA's raised panel with *MODEL NO. AGB-002* and its screw at the top, the SNES cartridge's moulded warning and corner holes. The Game Boy back (logo, MADE IN JAPAN, the screw) follows its usual layout; no photo of one could be checked. Its contacts show only in the bottom edge. In a DS, 3DS or Switch case the card sits in the moulded holder at the top of the inside. The PlayStation and Xbox drawings in the console filter are traced from pictures of the real consoles in the same way.

## Covers

Every cover is a JPEG 520 px tall under `public/covers/<console>/<id without the console>.jpg`, with its shape and the three colours the spine is drawn in worked out from it (`includes/images.php`, with GD). On a copy's edit page:

- **Find cover** asks every source that might have the copy's box and shows them all, best first, its own region's before another's; *Use this* saves one (`includes/covers.php`, was `tools/fetch-covers.mjs`). The sources are free and need no key: [libretro-thumbnails](https://github.com/libretro-thumbnails) (PS1, PS2, Xbox, Wii, Wii U, DS, 3DS, Game Boy, GBC, GBA, SNES, and DOS boxes for the first PC games), [aldostools/resources](https://github.com/aldostools/resources) (PS3, by disc serial), [xlenore/ps2-covers](https://github.com/xlenore/ps2-covers) (PS2, by serial), [the Sims Wiki](https://sims.fandom.com) and Wikipedia (PC), and [TheGamesDB](https://thegamesdb.net) (PS4, PS5, Xbox One, Xbox 360, Switch, Switch 2, PC). The GitHub collections' file lists are kept a week in `data/cache/`.
- A copy steers it with **Cover match** (a libretro file name such as `DreamWorks Shark Tale (Europe)`, a PS3 serial such as `BLES00229`, a PS2 serial such as `SLES-52047`, or a wiki page), **TheGamesDB id** (the number in a release's address there), and **Wikipedia cover**: *No* (keeps a wrong key-art picture from being offered).
- **Upload a picture**, or **take it from an address**: anything else, a photo of your own copy included.
- **Upload a photo of the card or cartridge label** puts it in `cover.label`. (The old `tools/fetch-cards.mjs` found label scans in LaunchBox's 100 MB database download, which a web host can't keep; those labels are all still there, and new ones are uploaded by hand.)
- *Work out the shape and colours again* measures a cover file that was put on the server by hand.

A picture wider than a box is turned away when it comes from a source (it is a photo of something else); one you upload never is. A cover you picked is yours: a sync no longer replaces it.

## Files

- `public/index.php`, `public/css/site.css`, `public/js/app.js`: the page, the filter, the shelf, the grid, the list, the shelf ⇄ grid flight and the spotlight
- `public/css/cases.css`, `public/js/cases.js`: each console's case, spine, back, disc and cartridge
- `public/js/consoles.js`: the consoles with their real case sizes, the regions and the PC launchers
- `public/js/console-art.js`: the console drawings in the filter
- `public/css/collection.css`, `public/js/collection.js`: what the collections' checklists share
- `public/css/sims.css`, `public/js/sims.js`, `public/assets/plumbob.svg`: the Sims collection and its look
- `public/css/gta.css`, `public/js/gta.js`: the Grand Theft Auto collection and its look
- `public/api/`: the JSON the page reads
- `public/admin*.php`, `public/css/admin.css`, `public/js/admin.js`: the admin; `public/login.php`, `setup.php`, `logout.php`
- `public/cron_sync.php`, `cron/sync.php`: the scheduled sync; `cron/import_json.php`, `cron/verify_import.php`: the import from `seed/`
- `includes/`: `bootstrap.php` (the app), `admin.php` (+ the admin and a session), `config.php`, `runtime.php`, `db.php`, `helpers.php`, `auth.php`, `csrf.php`, `secrets.php` (the encrypted sign-ins), `fields.php` (every field of a copy), `games.php`, `platforms.php`, `site.php` (Settings' texts, views and links), `http.php`, `images.php`, `covers.php`, `sync/`, `admin_ui.php`, `templates/`
- `sql/schema.sql`: the whole schema
- `seed/`: the JSON the site had before the database
- `public/covers/`: box art, one folder per console (not in the repo)
- `public/legacy/`: the console drawings and cartridges as they were before the PlayStation, Xbox and cartridge redraw, beside the ones on the site now. Nothing on the site loads them
- `public/prototypes/`: the design prototypes and the style prototypes with their gallery
- `public/fonts/`: the self-hosted fonts: Orbitron and Exo 2 for the site, Fredoka and Nunito for the Sims, Anton, Yellowtail and Manrope for GTA, Fraunces and Space Grotesk for what is printed on the cases
