# EA App Exporter

Exports what the EA app knows about an EA account: every game and add-on the account **owns** on PC (bought, redeemed or granted), the play time and last session the EA app keeps for each game, and each game's achievements (locked and unlocked, with unlock dates).

It signs in the way the EA app does (EA's `JUNO_PC_CLIENT`, the same sign-in the GOG Galaxy EA plugin uses), so it needs no API key, and two-step verification happens in the browser as usual. The GraphQL service it reads is the one the EA app uses. It is undocumented for outside use and EA can change it at any time. It only reads; it changes nothing on the account.

## Requirements
- PHP 8+ with cURL

## Sign in (once)
```bash
php export.php login
```
1. In your browser, open the developer tools on the **Network** tab, tick **Preserve log** (Chrome: **Keep log**) and pick the **All** or **Doc** filter (not Fetch/XHR).
2. Open the link it prints and sign in to your EA account.
3. After you sign in, the page seems to do nothing: EA sends the browser to the EA app's own address (`qrc:///html/login_successful.html?code=…`), which a browser can't open. Type `connect/auth` in the Network filter, click the last `auth?…` request (status 302) and, under **Response Headers**, copy the value of `location`.
4. Paste it into the terminal (it isn't shown). Pasting just the `code` value works too. The code works once, for a few minutes.

The session is saved in `session.json`. Each export renews it, so it lasts as long as you run an export now and then. If it stops working, sign in again. You can also pass the refresh token as `EA_REFRESH_TOKEN` instead of keeping the file.

## Export
```bash
php export.php                    # everything
php export.php --no-achievements  # skips the achievement lists (one or two requests per game)
```
It writes to `exports/` (each file keeps EA's response as it is, under `data`):

| File | What's in it |
| --- | --- |
| `owned-games.json` | every owned game: `originOfferId`, `product.name`, `product.gameSlug`, `product.baseItem.gameType` (`BASE_GAME`), `gameProductUser.ownershipMethods` and `initialEntitlementDate` (when it was added to the account) |
| `owned-addons.json` | every owned add-on (expansion, game, stuff and kit packs, extra content): `originOfferId`, `product.name`, `product.gameSlug`, `product.baseItem.gameType` (`EXPANSION_PACK`, `MICRO_CONTENT`, `BUNDLE_PACK`), ownership and since when. EA has no product page for some (bonus items, bundles, older extras, and the games themselves, which come back here too): those have `product: null` and their `legacyOffer` (`displayName`, `displayType`) instead |
| `play-time.json` | `gameSlug` → `totalPlayTimeSeconds`, `lastSessionEndDate` (only games with a session) |
| `achievements.json` | `offerToSet`: `originOfferId` → achievement set id; `sets`: set id → its achievements (`awardCount` 1 = unlocked, `date` = when) |
| `profile.json` | EA display name |

## Limitations
- Only PC games bought or redeemed through EA (EA app or Origin). EA games bought on Steam or Epic are in those stores' exports, and console play is in the PSN and Xbox exports.
- EA Play and Game Pass games you only played through a subscription aren't owned, so they aren't in `owned-games.json`.
- Play time exists only for games played through the EA app or Origin.
- Add-ons share their game's slug, so play time is per game, not per add-on.

## Security
- `session.json` is a login credential for the EA account: never share or commit it (it is in `.gitignore`).
- `owned-games.json` holds the account's entitlement ids, so keep `exports/` out of Git.
