# Xbox Gaming Data Exporter (Node.js + PHP)

An experimental local exporter that attempts to use the XboxReplay community authentication package and Xbox Live endpoints, saving raw responses to JSON. The optional PHP script validates the resulting JSON files.

## Important: this is not a guaranteed working sign-in path

Xbox Live is not a general-purpose public API. Community libraries and endpoint access can change or be restricted. The XboxReplay OAuth/custom application docs warn that custom OAuth setup is intended for approved Microsoft partners. The library's simple username/password method does not support MFA, so this project tries an interactive OAuth flow instead, but the library's code exchange may still rely on its own default client settings. Microsoft may reject the app or Xbox token exchange. I cannot promise this works with a personal Microsoft account, or that it can retrieve every type of gaming data.

References:
- https://github.com/XboxReplay/xboxlive-auth
- https://github.com/XboxReplay/xboxlive-auth/blob/master/docs/02-Custom_Azure_Application.md
- https://github.com/XboxReplay/xboxlive-api
- https://learn.microsoft.com/en-us/xbox/gdk/docs/reference/live/rest/uri/titlehistory/uri-titlehistoryusersxuidhistorytitlesgetv2?view=gdk-2604

## Requirements

- Node.js 20 or newer: https://nodejs.org/
- PHP 8.1+ only for the optional JSON validator

## Setup

1. Extract the ZIP.
2. Open Terminal in this folder.
3. Install dependencies:

   ```bash
   npm install
   ```

4. Edit `config.json`. The `clientId` placeholder must be replaced with a client ID configured for a compatible Microsoft OAuth application. The redirect URI is set to `http://localhost:3000/callback`. Whether this can be registered and used for Xbox token exchange depends on Microsoft's app policies and the library's implementation.
5. Start interactive authentication:

   ```bash
   npm run auth
   ```

6. If authentication succeeds and `tokens.json` is created, run:

   ```bash
   npm run export
   ```

7. Optional PHP validation:

   ```bash
   php export.php
   ```

## Output

The script attempts to create `profile.json`, `presence.json`, `friends.json`, `achievements.json`, `title_history.json`, and `export_summary.json` under `output/`. Each endpoint response is saved, including HTTP error responses, so you can see which routes failed.

The sample endpoints are experimental and may return 401/403/404. Xbox title history is not a complete record of every game ever launched, and playtime across all console and PC games is not guaranteed to be available.

## Security

- `tokens.json` contains sensitive bearer/refresh tokens. Treat it like a password.
- Never share `tokens.json`, `config.json`, or exported files publicly.
- Do not put your Microsoft password in this project.
- This tool only attempts read requests; it does not modify your Xbox profile or unlock achievements.
