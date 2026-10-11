# Steam PHP Personal Data Exporter

Exports the widest practical set of data available from Steam's public Web API and public Steam Store app-details endpoint into one local JSON file.

## What it attempts to export

- Profile summary: SteamID, display name, profile URL, avatar, visibility/status fields returned by Steam
- Steam ban-status fields returned by the API
- Owned/played library, App IDs, game names/icons, playtime and extended app info where returned
- Calculated library totals and recent playtime fields
- Recently played games
- Steam level and badges
- Per-game achievements, including unlocked state and unlock timestamps where Steam returns them
- Per-game achievement schema (names, descriptions, icons) where available
- Steam Store details for each game, including available descriptions, genres, categories, release information, developer/publisher, platforms and other store fields
- Errors for unavailable/private/unsupported endpoints

## Requirements

- PHP 8+
- PHP cURL extension
- Internet connection
- Steam Web API key
- Your 17-digit SteamID64

## Setup

1. Get a key at https://steamcommunity.com/dev/apikey. Keep it private.
2. Find your SteamID64 using the instructions below.
3. Open `config.php` and enter the key and ID.
4. Run `php export.php` in Terminal from this folder.
5. The JSON file is saved in `exports/`.

## Find your SteamID64

1. Open your Steam Community profile in a browser.
2. Click **Edit Profile**.
3. Look for the **Custom URL** section. If you have a custom URL, clear the custom URL text temporarily to reveal the numeric SteamID64 in the profile URL shown on the page. You do not need to save this change.
4. If you have not set a custom URL, Steam's profile URL should already contain the 17-digit number.

You can also copy your profile URL and use Steam's official support article for guidance: https://help.steampowered.com/en/faqs/view/2816-BE67-5B69-0FEC

## Settings

- `max_achievement_games => 0`: attempts achievements for every game returned by Steam. This may take a long time and some games will return errors.
- `include_game_schemas => true`: attempts to get achievement names/descriptions/icons for each game.
- `include_store_details => true`: gets Store metadata per game. With `max_store_detail_games => 0`, it attempts all games and can take a while. Set a limit (e.g. 50) for a quicker first run.
- `request_delay_ms`: delay between requests to reduce request bursts.

## Important limitations

No public Steam API gives a complete dump of every detail in your Steam account. In particular, this script cannot promise a complete purchase/transaction history, complete historical session log, all game-specific statistics, or private inventory/friends data. Privacy settings and game/API support affect what is returned. The exporter does not bypass privacy controls. Errors are included in the JSON so you can see which requests did not return data.

`config.php` contains your API key. Do not publish it, commit it to a public Git repository, or share the folder without removing the key first.
