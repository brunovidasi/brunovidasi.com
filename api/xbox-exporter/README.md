# Xbox Exporter

Exports what Xbox Live knows about the games played on an Xbox profile: every game played on Xbox 360, Xbox One, Xbox Series X|S or Xbox on PC, with its gamerscore and achievement counts, when it was last played, its image and store details, the minutes played Xbox keeps for it, and each game's achievements (locked and unlocked, with unlock dates and rarity).

It signs in the way the Xbox app does (a Microsoft account, with the Xbox app's client id), so it needs no Azure app and no API key, and two-step verification happens in the browser as usual. The services it reads are the ones the Xbox app uses. They are undocumented for outside use and Microsoft can change them at any time. It only reads; it changes nothing on the account.

## Requirements
- PHP 8+ with cURL

## Sign in (once)
```bash
php export.php login
```
1. Open the link it prints and sign in with the Microsoft account your Xbox profile uses.
2. After you sign in, the page goes blank. Copy the whole address from the address bar. It starts with `https://login.live.com/oauth20_desktop.srf?code=`.
3. Paste it into the terminal (it isn't shown). The code works once, for a few minutes.

The session is saved in `session.json`. Each export renews it, so it lasts as long as you run an export every month or two. If it stops working, sign in again. You can also pass the refresh token as `XBOX_REFRESH_TOKEN` instead of keeping the file.

## Export
```bash
php export.php                    # everything
php export.php --no-achievements  # skips the per-game achievement lists (one or two requests per game)
```
It writes to `exports/` (each file keeps Xbox's response as it is, under `data`):

| File | What's in it |
| --- | --- |
| `title-history.json` | every title played: `titleId`, name, `devices`, `achievement` (current/total gamerscore and achievements, %), `titleHistory.lastTimePlayed`, `displayImage`, `detail` (publisher, developer, release date, genres), Game Pass, product ids |
| `minutes-played.json` | `titleId` → minutes played |
| `achievements.json` | `titleId` → its achievements (`contract: 2` for newer games; `contract: 1` for Xbox 360 games, with `earned` listed separately) |
| `profile.json` | gamertag, gamerscore, picture |

Game names are in `en-GB`; set `XBOX_LOCALE` (`en-US`, `pt-BR`, …) for another language.

## Limitations
- It lists games **played**, not games **owned**: a game bought but never started isn't in it. Microsoft has no service that lists what an account owns.
- Minutes played exist only for Xbox One, Series and PC games. Xbox 360 games have achievements but no play time.
- Original Xbox games played on an Xbox 360 or Xbox One show up only if they were played through backward compatibility while signed in.
- Xbox Live may ask the exporter to slow down on a big library; it waits and carries on.

## Security
- `session.json` is a login credential for the Microsoft account: never share or commit it (it is in `.gitignore`).
- The exports hold the account's Xbox id (XUID) in places, so keep `exports/` out of Git.
