# PSN Gaming History Exporter

Exports the information available through the unofficial `tustin/psn-php` library to JSON.

## Requirements
- PHP 8.1+
- Composer
- A newly generated PSN NPSSO token

## Install
```bash
composer install
```

## Run (macOS/Linux)
Generate a fresh NPSSO token yourself. Do not share it in chat, commit it to Git, or put it in a PHP file.

```bash
PSN_NPSSO='PASTE_YOUR_NEW_TOKEN_HERE' php export.php
```

## Run (PowerShell)
```powershell
$env:PSN_NPSSO = "PASTE_YOUR_NEW_TOKEN_HERE"
php export.php
Remove-Item Env:PSN_NPSSO
```

The exporter writes JSON files to `exports/`:
- `profile.json`
- `trophy-summary.json`
- `games.json`
- `trophy-titles.json`
- `trophies-detailed.json`
- `friends.json`
- `export-summary.json`

Some endpoints can fail or return limited data because Sony changes its API and privacy settings vary. Errors are saved in `export-summary.json`. The library does not guarantee access to purchase history or console hardware details.

## Security
- Treat NPSSO as a login credential.
- Keep the `exports/` directory private. It can contain account IDs and friends' information.
- Do not publish the export or upload it to a public repository.
