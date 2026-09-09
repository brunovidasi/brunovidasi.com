# Cron Builder

A visual cron expression builder: pick minute/hour/day/month/weekday rules with buttons and chips, see the expression update live, get a plain-English explanation, and preview the next 5 times it would actually run.

## Files

- `index.html` — markup/structure
- `style.css` — warm paper styling, color-coded per cron field
- `script.js` — cron parsing, scheduling, and UI behavior
- `fonts/` — self-hosted Inter and JetBrains Mono (variable woff2, copied from the site's own `/fonts`)

## Usage

Open `index.html` in any modern browser. No build step, no server, no network calls.

1. Pick a preset, or set each of the 5 fields (minute, hour, day of month, month, day of week) to **Every**, **Step** (every N units), **Range**, or **Specific** values via chips.
2. The expression box at the top updates live and can also be typed into directly — it parses standard cron syntax (`*`, `*/5`, `1-5`, `1,15,30`, `1-5/2`) plus month/weekday names (`JAN`, `MON`, …), and syncs back into the visual builder.
3. Read the plain-English explanation and the next 5 run times, shown in your local timezone.

## How it works

Each field is parsed into the set of integer values it matches (minute-by-minute for step/range/list syntax). The day-of-month and day-of-week fields follow standard cron OR semantics: if both are restricted (not `*`), a day matches if it satisfies *either* one. Next-run times are found by walking forward from now, skipping whole months/days/hours that can't match before checking minutes, so it stays fast even years out.

## Privacy

Everything happens locally in the browser. Nothing is sent to a server.
