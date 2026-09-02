# brunovidasi.com

Personal portfolio site for Bruno Vieira — Full Stack / Front-End Developer based in Sydney, Australia.

Live at [brunovidasi.com](https://brunovidasi.com).

## What it is

A single-page portfolio styled as a code editor. The whole site is built to look and feel like VS Code: a title bar, a collapsible file explorer, tabs, and an editor pane. Each "file" in the explorer (`intro.js`, `readme.md`, `experience.js`, `skills.json`, `landing-pages.html`, etc.) opens a panel with real content — bio, work experience, education, skills, and project showcases (landing pages, mini tools, eDM/email templates and modules, freelance work, contact details, resume/documents).

Sections are deep-linkable via clean URL paths (e.g. `brunovida.si/experience`, via the History API plus an `.htaccess` rewrite so direct visits/refreshes resolve to the app), and the layout adapts to a mobile menu on small screens.

## How it was built

- **Vanilla HTML/CSS/JS** — no framework, no build step, no bundler. The whole app is `index.html`, `css/style.css`, and `js/script.js`.
- **Data-driven content** — project listings (landing pages, mini tools, eDM work/tools, site history, taglines) live in JSON files under `json/`, which `script.js` fetches and renders into the matching panel. Adding a project is a JSON edit, not a markup edit.
- **Custom "editor" UI** — the file tree, tab bar, active-file state, and folder expand/collapse are hand-rolled in JS, mimicking VS Code's explorer/tabs behavior rather than using a UI library.
- **Static hosting** — plain static assets (HTML/CSS/JS/JSON/images), no server-side runtime required.
- **`projects/`** — hosts the actual project work referenced by the portfolio: landing pages, mini web tools, eDM (email) kinetic modules and automation tools, prototypes, and archived past versions of this site itself (2012–2025) for a visual history of the site's evolution.

## Structure

```
index.html          Markup for the editor shell and all content panels
css/style.css        All styling (VS Code–inspired theme, responsive layout)
js/script.js          File/tab state, JSON fetch + render, deep-linking, UI behavior
json/                 Content data (projects, tools, site history, taglines)
assets/               Favicons, logo
img/                  Site images
projects/              Actual project code referenced/showcased by the portfolio
```
