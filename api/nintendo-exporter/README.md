# Nintendo Switch Play History Exporter

Exports the play history the Nintendo Store app shows: every Switch and Switch 2 game played on the Nintendo Account (physical or digital), with its total play time, how many days it was played, when it was first and last played, and its icon.

It signs in the way the Nintendo Store app does (Nintendo Account OAuth), so the sign-in page says it is linking to *Nintendo Store*. The play history API is undocumented and Nintendo can change it at any time. It only reads; it changes nothing on the account.

## Requirements
- PHP 8+ with cURL

## Sign in (once)
```bash
php export.php login
```
1. Open the link it prints and sign in to your Nintendo Account.
2. On the **Select this person** page, don't click the button: right-click it and **Copy Link Address**. The link starts with `npf5c38e31cd085304b://auth#`.
3. Paste it into the terminal (it isn't shown). The link works once, for a few minutes.

The session is saved in `session.json` and lasts about two years. You can also pass it as `NINTENDO_SESSION_TOKEN` instead of keeping the file.

## Export
```bash
php export.php
```
Writes `exports/play-histories.json`: Nintendo's response as it is, under `data`. Game names are in `en-GB`; set `NINTENDO_LOCALE` (`en-US`, `ja-JP`, …) for another language.

## Limitations
- It lists games **played**, not games **owned**: a game bought but never started isn't in it.
- Play time is what the console reported to Nintendo, so a console that was offline for a while catches up when it next connects.
- Only Switch and Switch 2 games appear, not Wii U or 3DS.

## Security
- `session.json` is a login credential for the Nintendo Account: never share or commit it (it is in `.gitignore`).
- The export holds no account ids, but keep `exports/` out of Git all the same.
