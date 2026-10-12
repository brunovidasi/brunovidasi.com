# Rockstar Games Exporter

Exports what Rockstar Games knows about a Rockstar (Social Club) account: every game the account has played, on each platform (PC, PlayStation, Xbox) with when it was last seen there, the games the site's account menu lists as played, and each PC game's unlocked achievements with their dates.

**There is no play time.** The Rockstar Games Launcher counts the hours, but nothing Rockstar serves shows them: not the launcher's files, not the website, not the Social Club API. Rockstar games bought on Steam, Epic, PlayStation or Xbox have their play time in those exports.

It reads the Social Club API (`scapi.rockstargames.com`) with the same bearer token rockstargames.com uses. That token lasts only five minutes, so each export gets a new one the way the site does: it replays the site's silent sign-in check (`signin.rockstargames.com/connect/cors/check/rsg`, with the sign-in cookies and the browser's fingerprint), and trades the code it answers for a token at `www.rockstargames.com/auth/gateway.json`. That API is undocumented and Rockstar can change it at any time. It only reads; it changes nothing on the account.

## Requirements
- PHP 8+ with cURL

## Sign in (once)
1. In a browser, sign in at <https://www.rockstargames.com/> (the top-right **Sign In**; tick **Keep me signed in**).
2. Open the developer tools on the **Network** tab and type `check/rsg` in its filter.
3. Wait five minutes and reload the page. The token has run out by then, so the site checks the sign-in again, and a POST named `rsg` (to `signin.rockstargames.com`) shows up. If it doesn't, wait a little longer and reload again.
4. Right-click it, **Copy › Copy as cURL** (on Windows, *Copy as cURL (bash)*), then run:
   ```bash
   pbpaste | php export.php login
   ```
   (`php export.php login` alone asks for it at a prompt instead.)

What it needs from that request (the sign-in cookies, the fingerprint and the browser's headers) is saved in `session.json`. Each export renews it with them and keeps the cookies Rockstar sends back, so the session lasts as long as the rockstargames.com sign-in does (signing out in the browser ends it). If it stops working, copy the request again. You can also pass a bearer token alone as `ROCKSTAR_BEARER` (the `BearerToken` cookie, good for five minutes, not renewed).

## Export
```bash
php export.php                    # everything
php export.php --no-achievements  # skips the achievement lists (one request per PC game)
```
It writes to `exports/` (each file keeps Rockstar's response as it is, under `data`):

| File | What's in it |
| --- | --- |
| `games.json` | every game played, once per platform: `name` (Rockstar's short code: `GTAV`, `RDR2`, `LAN`, `MP3`…), `platform` and `lastSeen` |
| `last-played.json` | what the site's account menu lists as played: short code → platform |
| `achievements.json` | short code → the PC achievements unlocked, by achievement number, each with `dateAchieved` |
| `profile.json` | Social Club nickname and Rockstar id |

## Limitations
- No play time (above), and no total of achievements per game: Rockstar gives only the ones unlocked.
- It lists games **played**, not bought: a game bought and never started isn't there.
- Achievements are only read for PC. A console game's trophies and achievements are in the PSN and Xbox exports.
- Games without a Social Club achievement list (Bully, the original GTA III, Vice City and San Andreas) have none.

## Security
- `session.json` is a login credential for the Rockstar account: never share or commit it (it is in `.gitignore`).
- `profile.json` holds the account's Rockstar id, so keep `exports/` out of Git.
