# Bruno's Games

The game collection site: black, finely pinstriped pages with a blue accent and a dark wooden bookcase, set in Orbitron (the big display text) and Exo 2 (everything else). It started from the record site (`brunovida.si/music`); the two are separate sites.

- **The bar**: the console filter sits under the header, and the bar under it has the search and the sort (a dropdown in the site's own style) on the left and the views on the right, with how many games are showing on the line below, as on the record site. On a phone the search folds into a magnifying glass that opens across the bar.
- **The shelf** (`index.html`): every game on a wooden bookcase, grouped by console, in that console's real case at its real size. Loose cartridges stand on their own (a SNES cartridge at its real size beside the cases, the small cards larger so they can be seen); SNES cartridges stand on their side in a row, like books, with the title on their top label facing out, beside the one that faces out. A PAL one, or an unofficial one, has nothing on its top edge, so it faces out too. The spines are drawn wider than real ones so their titles can be read. Each shelf is only a little taller than the tallest game on it. Like a real shelf, it is a bit messy: on most shelves the last game or two lean over onto the one before, and so do some at the end of a console before its bookend. The plate under each console is in its family's colour, as in the filter: red Nintendo, blue PlayStation, green Xbox. The console filter at the top shows a drawing of each console.
- **The grid**: every game face out, standing on one line at its real size, with its name under it. Going between *Shelf* and *Grid* carries every case on screen across, as the record site does between its floor and its grid: each case comes off the shelf and turns from its spine to its cover on the way, and turns back to its spine on the way home. Between the shelf and the rows each spine widens or narrows smoothly in the air to the width it lands at, and a case picked from the rows leaves at the row's spine width and narrows to its real depth as it turns (and widens again on the way home). A SNES cartridge standing on its side turns from its top label to its front the same way, as one solid cartridge, and so it does when it is picked off the shelf.
- **The rows**: each console on a strip of shelf of its own that scrolls sideways. The cases stand as on the shelf, their spines out (*Show* works as on the shelf), but taller and with each spine much wider than a real one, so they are easy to tap on a phone. Swipe a row to go along it, or with a mouse use the arrows that show at either end; a scroll down over a row still scrolls the page. Sorted any way but by console, the games stand on one long row.
- **The showcase**: one game at a time on a glossy stage, with the rest fanned out either side and the light behind it in the colour of its cover, as in `prototypes/3-showcase.html`. Drag, scroll sideways, use the arrows or the arrow keys to move along, or jump with the strip under it (a block per console). The game in the middle is picked up like anywhere else, and one to the side comes to the middle first. The cases fly across between the showcase and the other views, as between the shelf and the grid.
- **The list**: the same games as a sortable table, like the record site's list.
- **A game up close**: click a case on the shelf, in the grid or in the list. It lifts out of its place, which stays empty, and flies to the middle of the screen with its name above it and its details below. Drag it to turn it round, or use *Front*, *Spine* and *Back*. *Open case* shows the disc, and *Take it out* lifts the cartridge out of its box. The arrows at the bottom (or the arrow keys) go to the next and previous game. On a big screen the previous and next games stand smaller to the left and right; stepping, the game in the middle moves aside into the place beside the next one as that one comes in from its own, and a click on either one steps to it; closing flies it back into its place. On a phone held sideways the details stand beside it.
- **The collections**: *The Sims* and *GTA* in the top bar are filters on the same page, not pages of their own. Picking one (`?sims`, `?gta` in the address) leaves only its games on the shelf, gives it its own header and a *Checklist* view, and changes the whole page's look (a class on `<html>`, `css/sims.css` and `css/gta.css`), fading from one to the other; *The Shelf* goes back to everything. The view, the sort and the consoles picked carry across where they can.
- **The Sims collection** (`?sims`): the page in the game's own clothes: a deep Sims-blue sky, glossy round blue panels, plumbob green for what is picked, a cream wallpapered room behind the bookcase, Fredoka and Nunito. A plumbob (`assets/plumbob.svg`, turning) bobs beside the title, and over whichever case the pointer is on, as over a picked Sim. Every Sims game and pack, on the same bookcase, in the same grid and list, grouped by game (The Sims, The Sims 2, The Sims Stories, The Sims 3, The Sims Medieval, The Sims 4, SimCity, MySims) rather than by console. Its *Checklist* view sorts them by game and pack, with how much of each is there and the want list. A solid case is on disc, an outline is in the EA app.
- **Grand Theft Auto** (`?gta`): the page after GTA VI's Vice City (the `leonida` style prototype): a sunset over the water with palms, the shelf as a collage of panels split by black gutters, Anton, Yellowtail and Manrope. Every GTA copy on the bookcase, grouped by game. Its *Checklist* goes era by era, one row per game with its copies on every platform and a dashed outline at the real case size for each version still to find. Those outlines are the want list at the bottom.
- **Design prototypes** (`prototypes/`): the four early designs, kept as they were.
- **Style prototypes** (`prototypes/styles/`): eleven looks for the same site, none of them live yet. `prototypes/styles/index.html` is a gallery of them with a screenshot of each, and every style has a full preview of the site in its own folder (`prototypes/styles/neon/`, and `?sims`, `?gta` on it). Each look is one stylesheet, `prototypes/styles/<name>.css`, loaded after the site's own CSS: `neon` (Neon Arcade), `hud` (Holo Vault), `pixel` (Pixel Quest), `dashboard` (Next-Gen Home), `museum` (Hall of Fame), `atomic` (Atomic Purple), `comic` (Game Mag), `console` (Super Console), `glitch` (Glitch City), `leonida` (Leonida, after GTA VI's Vice City) and `datacore` (Data Core). The top of each names the Google Fonts it needs. The previews are copies of the site's page, so after changing `index.html`, run `node tools/style-previews.mjs` to rebuild them.

## Running it

The shelf loads `data/games.json`, so open it through a web server rather than as a file:

```sh
python3 -m http.server 8000     # or: npx serve
# then open http://localhost:8000
```

There is no build step. A link like `index.html#ps2` opens straight onto one console, and `index.html#ps2-final-fantasy-x` opens one game. `?sims` or `?gta` opens a collection, and in one a section's name opens the checklist there: `?gta#want-list`.

The collections are the same shelf (`js/app.js`): each adds itself to `window.COLLECTIONS` with its name, its games, how they group, its header and its checklist (see the top of `js/app.js`), and its look is a stylesheet scoped to its class on `<html>` (`.sims`, `.gta`).

## The collection: `data/games.json`

One entry per copy on the shelf:

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
| `caseColor` | `"#d8000f"` | optional: a case in another colour than its console's usual one (a red Wii case, a white Wii U one). It colours the plastic and the spine, and the spine's writing turns light or dark to suit. PS2 cases are blue by default; give the black ones `"#121212"` (only the plastic changes, the white paper spine stays) |
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

## Steam

The Steam library comes in through `tools/steam-sync.mjs`, from an export made by `api/steam-exporter` (see its README; its `config.php` holds the API key and is never committed, and neither are its exports):

```sh
cd api/steam-exporter && php export.php    # a new export in exports/
cd ../../tools && node steam-sync.mjs --dry  # show what it would change
node steam-sync.mjs                          # bring it in
```

Run it again whenever the library changes. It writes two files:

- `data/steam.json`: everything Steam says about each game, by app id: playtime (in minutes, in all and per system), when it was last played, its achievements (how many, and each one with its name, description, icon and, once Steam returns them, when it was unlocked), and its store page (description, genres, categories, platforms, languages, age ratings, Metacritic, price, DLC, screenshots, trailers, pictures). The sync writes it whole each time, so it is never edited by hand. The account's own details (its SteamID, bans, badges) stay out of it.
- `data/games.json`: each copy in the Steam library has `"steamAppId"`. A game in the library with no copy tied to it gets a new digital one (`"launcher": "steam"`, `"dateSource": "steam"`, the region from the store's currency), with the game's Steam library art as its cover (`"source": "steam"`; `fetch-covers.mjs` leaves it alone except with `--only`). A digital PC copy with the same title, no `steamAppId` and no other launcher is tied to it instead. A boxed copy never is: a Steam copy is a copy of its own, as RollerCoaster Tycoon 2: Triple Thrill Pack is both on the shelf and on Steam (a disc that activates on Steam can be tied by hand with `steamAppId` and `"alsoDigital": true`). After that the copy is yours: the sync only fills its fields that are still empty and never changes one that is set, so titles, `sortAs`, a better cover and the rest can be edited as on any game. To keep a Steam game off the site, give it `"hidden": true` rather than deleting it, or the next sync brings it back. A tied copy that drops out of the library is only reported, never deleted.

On the site, a game with Steam data shows its playtime, when it was last played, its achievements, the store's description and a link to its store page under its details, and its licence (the back of a digital copy) has the playtime and app id. Achievements unlocked need the Steam profile's *Game details* to be public; until then only how many a game has shows.

When the site talks to the Steam API itself, the PHP side can hand `fromExport()` in `tools/steam-sync.mjs` the same shape the exporter writes, or another reader can return what it returns, and the rest stays as it is.

## PlayStation Network

What PSN knows about the PlayStation games comes in the same way, through `tools/psn-sync.mjs`, from an export made by `api/psn-exporter` (see its README: it needs a fresh NPSSO token, which is never written down anywhere, and its exports are never committed):

```sh
cd api/psn-exporter && PSN_NPSSO='…' php export.php
cd ../../tools && node psn-sync.mjs --dry   # show what it would change
node psn-sync.mjs                           # bring it in
```

It writes `data/psn.json`, by PSN id, written whole each time: each game's play history (PS4 and PS5 only: how long, how many times, when first and last played), each trophy set (PS3, PS4 and PS5: how many trophies of each grade it has, how many were earned and when it last changed) and the account's trophy level. The account's ids, profile and friends stay out of it, and so do the apps that aren't games (Netflix, YouTube, Spotify).

The PlayStation games are discs already on the shelf, so the sync never adds a copy: it ties one with `"psnTitleId"` (`CUSA…`, `PPSA…`: its play history) and `"psnTrophySet"` (`NPWR…`: its trophies). A PSN game is tied to the one copy on its console with the same name, or with a name that starts with it (*The Sims 4* is *The Sims 4 Plus Cats & Dogs*). The export doesn't say which console a trophy set is for, only whether it is the PS5's trophy service, so an older one can be a PS3 or a PS4 set. When there is no copy or more than one to choose from, the sync only reports it, and it is tied by hand: *Mortal Kombat*'s trophies are *Mortal Kombat: Komplete Edition*'s, the two *SingStar* sets the PS3's and the PS4's (`check` on `ps3-singstar`). A copy with more than one game in it takes a list, and its numbers are added up: *GTA: The Trilogy – The Definitive Edition* has each game's own play history and trophies (`"psnTitleId": ["CUSA26613_00"]`, with Vice City's and San Andreas' to join it once they have been played).

On the site, a tied game shows under its details how long it was played, over how many sessions, when it was first and last played, and how many of its trophies were earned.

## Nintendo Switch

The Switch and Switch 2 play history comes in the same way, through `tools/nintendo-sync.mjs`, from an export made by `api/nintendo-exporter` (see its README: it signs in once to the Nintendo Account as the Nintendo Store app does, and keeps the session in `session.json`, which is never committed, and neither are its exports):

```bash
cd api/nintendo-exporter && php export.php login   # once
php export.php                                     # a new export in exports/
cd ../../tools && node nintendo-sync.mjs --dry     # show what it would change
node nintendo-sync.mjs                             # bring it in
```

It writes `data/nintendo.json`, by Nintendo title id (`0100…`), written whole each time: every game played on the account, cartridge or download, with how long it was played, on how many days, when it was first and last played and which console it was played on. It lists games played, not games owned: one never started isn't in it. Nothing about the account goes in it.

The sync ties a copy with `"nintendoTitleId"`, to the one Switch or Switch 2 copy with the same name (or `listedAs`), or with a name that starts with it (but not a sequel's: *Just Dance* isn't *Just Dance 2022*), leaving out *Nintendo Switch 2 Edition*; when there are several, the one on the console it was played on. When there are several, it only reports it, and it is tied by hand: *Moving Out* and *Pokémon: Let's Go, Pikachu!* are tied to both their copies. A copy with more than one game in it takes a list, and its numbers are added up: the *GTA* Trilogy cartridge has III's and San Andreas' (Vice City's joins once it has been played), the *MySims: Cozy Bundle* MySims' and MySims Kingdom's.

A game played with no copy at all was bought or downloaded on the eShop, so it gets a new digital copy (`--no-add` stops that), with only its title filled in: give it its release date, publisher, developer and genre by hand, and its cover with `fetch-covers.mjs --only` (a free game with no box takes its eShop icon from `data/nintendo.json`, as `"plain": true`). After that the copy is yours. To keep one off the site, give it `"hidden": true` rather than deleting it, or the next sync adds it back. Apps and what comes with Nintendo Switch Online (YouTube, Pokémon HOME, the Nintendo Classics, Tetris 99) are never added.

On the site, a tied game shows under its details how long it was played, on how many days and when it was first and last played, and a digital copy's licence has its playtime. A digital Switch or Switch 2 copy is a *Nintendo eShop* copy, in its details and its licence, and *eShop* finds them in the search.

## Xbox

What Xbox Live knows about the Xbox games comes in the same way, through `tools/xbox-sync.mjs`, from an export made by `api/xbox-exporter` (see its README: it signs in once to the Microsoft account as the Xbox app does, and keeps the session in `session.json`, which is never committed, and neither are its exports):

```bash
cd api/xbox-exporter && php export.php login   # once
php export.php                                 # a new export in exports/
cd ../../tools && node xbox-sync.mjs --dry     # show what it would change
node xbox-sync.mjs                             # bring it in
```

It writes `data/xbox.json`, by Xbox title id, written whole each time: every game played on the profile (Xbox 360, Xbox One, Series X|S and Xbox on PC), when it was last played, the minutes played Xbox keeps for it (only some Xbox One, Series and PC games have them; Xbox 360 ones never do), its achievements (how many, how many unlocked, the gamerscore, and each one unlocked with its date) and whether it came with Game Pass, and the profile's gamerscore. It lists games played, not games owned. The gamertag and Xbox id stay out of it.

The sync ties a copy with `"xboxTitleId"`, to the one copy with the same name (or `listedAs`), or with a name that starts with it, on a console it was played on: an Xbox 360 game to the `x360` copy, even when it was played on an Xbox One; an Xbox One or Series game to the `xone` copy; a game played in the Xbox app on PC to the `pc` copy. It reads Xbox's short names (*GTA V*, *Bully Scholarship Ed.*). When there is no copy or more than one, it only reports it, and it is tied by hand. It never adds a copy: Xbox lists the Game Pass and free games played too (*Fortnite*, *Call of Duty: Modern Warfare*), which aren't on the shelf. The Windows games the Xbox Game Bar noticed running (the EA *Sims* games) say only when they were last played, so they stay out.

On the site, a tied game shows under its details how long it was played (when Xbox knows), when it was last played, how many achievements were unlocked, the gamerscore and the latest achievement, and *Xbox Live* finds them in the search.

## EA app

The EA app library comes in the same way, through `tools/ea-sync.mjs`, from an export made by `api/ea-exporter` (see its README: it signs in once to the EA account as the EA app does, and keeps the session in `session.json`, which is never committed, and neither are its exports):

```bash
cd api/ea-exporter && php export.php login   # once
php export.php                               # a new export in exports/
cd ../../tools && node ea-sync.mjs --dry     # show what it would change
node ea-sync.mjs                             # bring it in
```

It writes `data/ea.json`, by EA game slug, written whole each time: every game and add-on (expansion, game, stuff pack, kit) the EA account owns on PC (bought, or a disc's code redeemed), the offers it is owned through and since when, and for a game the play time and last session the EA app keeps for it and its achievements with when each was unlocked. Unlike Xbox and Nintendo it lists games owned, played or not. The parts EA ships a game in (Battlefield 6's HD textures, multiplayer and single player), the free Battlefield REDSEC and the extras that aren't a pack (SimCity's city sets, the Sims 4 bonus items and bundles) stay out. Nothing about the account goes in it.

The sync ties a copy with `"eaSlug"`, to the one PC copy with the same name (or `listedAs`; ™, a year in brackets and *Expansion Pack* at the end don't count: *The Sims™ 3 Late Night Expansion Pack* is *The Sims 3: Late Night*), and of several to the digital one or the disc that is `alsoDigital`. When there are still several, it only reports it, and it is tied by hand. EA names a few games its own way, so they are tied by hand: *The Sims™ + The Sims Packs* is *The Sims Legacy Collection*, *The Sims™ 2 + The Sims™ 2 Packs* the *Legacy Collection* of The Sims 2, *Sim City 3000™ Unlimited* SimCity 3000. A game with no copy at all was bought in the EA app, so it gets a new digital one (`"launcher": "ea"`; `--no-add` stops that), with only its title and region filled in (an add-on takes its game's name before its own, *The Sims 4: Grunge Revival Kit*, and its series, publisher, developer and genre, and a `pack` from its name): give it its release date, publisher, developer and genre by hand, and its cover with `fetch-covers.mjs --only`. After that the copy is yours. To keep one off the site, give it `"hidden": true` rather than deleting it, or the next sync adds it back.

On the site, a tied game shows under its details how long it was played in the EA app, when it was last played and how many achievements were unlocked (a tied add-on, since when it is in the EA app), and a digital copy's licence has its playtime and the day it joined the account. With only the PC picked, the *EA app* library is exactly the copies tied to the export: the ones bought in the EA app and the discs whose code was redeemed in it (those are under *Physical* too). *EA app* finds them in the search.

## Rockstar Games

What Rockstar knows about the account comes in the same way, through `tools/rockstar-sync.mjs`, from an export made by `api/rockstar-exporter` (see its README: it signs in once from the site's sign-in check copied as cURL out of a signed-in rockstargames.com tab, and keeps it in `session.json`, which is never committed, and neither are its exports):

```bash
cd api/rockstar-exporter && pbpaste | php export.php login   # once
php export.php                                     # a new export in exports/
cd ../../tools && node rockstar-sync.mjs --dry     # show what it would change
node rockstar-sync.mjs                             # bring it in
```

It writes `data/rockstar.json`, by Rockstar's short name for a game (`gtav`, `rdr2`, `lan`), written whole each time: every game the account has played, on which platforms and when it was last seen in each, and for a PC game the achievements unlocked, with when. Rockstar keeps no play time anyone can read (the launcher counts it but never shows it), and gives only the achievements unlocked, not how many there are. Nothing about the account goes in it.

The sync ties a PC copy with `"rockstarTitle"`, to the one PC copy with the same name (or `listedAs`), and of several to the one on the Rockstar Games Launcher. When there are still several, it only reports it, and it is tied by hand. Rockstar names its games by short codes, which `TITLES` in the sync turns into the shelf's names; a code that isn't there is only reported, so add it. A PC game with no copy at all was bought in the launcher, so it gets a new digital one (`"launcher": "rockstar"`; `--no-add` stops that), with only its title and publisher filled in. The console games are left to PSN and Xbox. After that the copy is yours. To keep one off the site, give it `"hidden": true` rather than deleting it, or the next sync adds it back.

On the site, a tied game shows under its details when it was last played on PC and how many achievements were unlocked, and *Rockstar Games Launcher* finds them in the search.

**The Sims page** takes every game with a `"series"` (`"The Sims"`, `"The Sims 2"`, `"The Sims Stories"`, `"The Sims 3"`, `"The Sims Medieval"`, `"The Sims 4"`, `"SimCity"` or `"MySims"`), on any console. `"pack"` says what it is (`Base game`, `Collection`, `Expansion Pack`, `Game Pack`, `Stuff Pack`, `Kit`, `World` or `Extra`) and `"packCode"` gives The Sims 4's numbering (`EP01`, `GP04`, `SP12`). Serial keys are never kept here.

**The GTA page** reads `data/gta.json`: the eras, and in each its games with `copies` (ids from `games.json`) and `missing` (console ids, plus `psp` and `xsx` for Xbox Series X|S). When a missing version comes home, add it to `games.json` and move its id from `missing` to `copies`. A copy with two games in it (Grand Theft Auto & GTA London) goes in both games' `copies`: it shows in both rows of the checklist and stands on the shelf once, with the first.

**The Sims want list** is `data/sims-wants.json`: the games and packs not in the collection yet, with the same `series`, `pack` and `packCode`, plus `platform` and an optional `note`. When one comes home, delete it there and add it to `games.json`.

## Printed spines

A game's `spine` draws its spine as the real box prints it. Every part is drawn, so it stays sharp at any size; nothing is a photo. A game in a thick EA case (`bigCase`) gets `{ "style": "ea-pcgame" }` by default.

- `style`: `ea-pcgame` (EA's thick PC cases of the early 2000s: black ends, the cover's own art squeezed and blurred down the middle), `white` (the plain white spine of the DVD-case years), `sims4` (a band in the pack's colour at each end round The Sims 4's logo, the pack's icon and name in the lower one) or `art` (the cover's art right down the spine, SimCity 2013).
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

`tools/fetch-covers.mjs` looks for box art for every game whose `cover` is `null`. It downloads it into `covers/`, resizes it, and writes the file and its colours back into `games.json`:

```sh
cd tools && npm install
node fetch-covers.mjs --dry      # show what it would match
node fetch-covers.mjs            # download
node fetch-covers.mjs --only ps2-the-sims   # redo one game
node fetch-covers.mjs --recheck  # after changing regions: swap in the right region's box
node fetch-covers.mjs --upgrade-wiki   # swap Wikipedia pictures on PS4, PS5, Switch, Switch 2 and Xbox for real box scans
```

It uses free community collections on GitHub, region by region, and needs no keys:

- [libretro-thumbnails](https://github.com/libretro-thumbnails): PS1, PS2, Xbox, Wii, Wii U, DS, 3DS, Game Boy, GBC, GBA, SNES
- [aldostools/resources](https://github.com/aldostools/resources): PS3, by disc serial
- [xlenore/ps2-covers](https://github.com/xlenore/ps2-covers): PS2, by disc serial

If it picks the wrong one, or finds nothing, set `"coverMatch"` on the game:
- the libretro file name without `.png`, for example `"DreamWorks Shark Tale (Europe)"`
- a PS3 serial, for example `"BLES00229"`
- a PS2 serial, for example `"SLES-52047"`

PC games come from the box art on the game's page on [the Sims Wiki](https://sims.fandom.com), then Wikipedia, then libretro-thumbnails' DOS boxes (the first Grand Theft Auto). `"coverMatch"` names the page when the title doesn't find it. This needs `sims.fandom.com`, `static.wikia.nocookie.net`, `en.wikipedia.org` and `upload.wikimedia.org` to be reachable. In a Claude Code cloud session, run it as `NODE_USE_ENV_PROXY=1 node fetch-covers.mjs` so Node goes through the session's proxy.

None of these collections cover PS4, PS5, Switch, Switch 2, Xbox One or most Xbox 360 games. Those come from [TheGamesDB](https://thegamesdb.net), which has real box scans release by release: the script takes the scan of the copy's own region (a PAL copy gets a British or Australian box, with the console's band across the top), else an English one from elsewhere, and the plain release before a special edition. If it picks the wrong release, set `"tgdbId"` on the game to the number in that release's TheGamesDB address (`game.php?id=121209`). PC games it can't find on the wikis come from TheGamesDB too. A wide picture there (a photo of the box) is skipped. What TheGamesDB lacks takes the box on the game's Wikipedia page, which is often key art or another console's box, so look at what comes back. A picture wider than a box (a store icon, a banner) is skipped, `"coverMatch"` names the page, and `"wikiCover": false` stops a wrong one coming back. A game with no cover shows a printed title card in the right case. The details of every game without a cover have a *Find the cover on Google Images* link that searches for the title, console and region. To add a cover by hand:
1. Save the image as `covers/<console>/<name>.jpg`.
2. Set `"cover": { "file": "covers/…", "source": "manual" }` on the game.
3. Run `node fetch-covers.mjs` in `tools/`. It measures the cover's shape, so it fits the case, and takes its colours for the spine. It does not change the image.

The script never replaces covers marked `manual`. A source with an API key, such as IGDB or TheGamesDB, would fill these consoles automatically. It needs a small server to keep the key private, which can come with the PHP side of the site.

## Files

- `index.html`, `css/site.css`, `js/app.js`: the page, the filter, the shelf, the grid, the list, the shelf ⇄ grid flight and the spotlight
- `css/cases.css`, `js/cases.js`: each console's case, spine, back, disc and cartridge
- `js/consoles.js`: the consoles with their real case sizes, the regions and the PC launchers
- `js/console-art.js`: the console drawings in the filter
- `legacy/`: the console drawings and cartridges as they were before the PlayStation, Xbox and cartridge redraw (the in-between versions are in the git history) (`legacy/console-art.js`, `legacy/cases.js`), and `legacy/index.html`, which shows each old one beside the one on the site now. Nothing on the site loads them
- `css/collection.css`, `js/collection.js`: what the collections' checklists share: the cases on their stretches of shelf, the meters, the want cards
- `css/sims.css`, `js/sims.js`, `assets/plumbob.svg`: the Sims collection and its look
- `data/sims-wants.json`: the Sims want list
- `css/gta.css`, `js/gta.js`, `data/gta.json`: the Grand Theft Auto collection and its look
- `data/games.json`: the collection
- `data/steam.json`, `tools/steam-sync.mjs`, `api/steam-exporter/`: the Steam library (above)
- `data/psn.json`, `tools/psn-sync.mjs`, `api/psn-exporter/`: PlayStation Network (above)
- `data/nintendo.json`, `tools/nintendo-sync.mjs`, `api/nintendo-exporter/`: the Nintendo Switch play history (above)
- `data/xbox.json`, `tools/xbox-sync.mjs`, `api/xbox-exporter/`: Xbox Live (above)
- `data/ea.json`, `tools/ea-sync.mjs`, `api/ea-exporter/`: the EA app library (above)
- `covers/`: box art, one folder per console
- `tools/`: the cover fetcher (and `palette.mjs`, the colours it takes from a cover), the Steam, PSN, Nintendo, Xbox and EA app syncs, and `style-previews.mjs`, which builds the style prototype previews
- `prototypes/`, `assets/`: the four design prototypes (and the plumbob)
- `prototypes/styles/`: the style prototypes, their previews and their gallery
- `fonts/`: the self-hosted fonts: Orbitron and Exo 2 for the site, Fredoka and Nunito for the Sims, Anton, Yellowtail and Manrope for GTA, Fraunces and Space Grotesk for what is printed on the cases
