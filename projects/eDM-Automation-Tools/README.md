# eDM Automation Tools

Automation tools to help me build eDMs.

A collection of lean, single-file, dependency-free tools built to speed up complex HTML email production — handling Outlook/MSO quirks, cross-client rendering issues, and repetitive campaign tasks.

## Tools

- **HTML Validator** — a live-highlighting HTML/CSS editor with real-time validation: flags unclosed/mismatched tags, unquoted attribute values, unknown tags, missing `alt` attributes, and inline CSS issues (unbalanced braces, duplicate properties), with click-to-jump navigation from each warning to the offending line.
- **CSS Minifier** — byte-safe CSS minification (whitespace and optional comment stripping only, no value rewriting) with a built-in semantic verification pass that confirms the minified output is identical to the source, and preserves Outlook/MSO conditional comments.
- **Body Text to HTML Converter** — converts plain body text into email-ready HTML: wraps selected text in `<strong>`, auto-superscripts trademark symbols and footnote numbers, and converts line breaks to `<br>` tags.
- **T&Cs HTML Add Link** — a rich-text editor for terms & conditions copy that lets you select text and insert grey or blue styled links (or remove them), then outputs clean, email-safe HTML.
- **T&Cs HTML to Plain Text Converter** — converts HTML terms & conditions blocks into clean plain text, expanding links to "text (url)" format and normalizing whitespace/line breaks.
- **HTML Diff Tool** — compares two HTML snippets side by side with line- and word-level highlighting, an "ignore whitespace" toggle, and added/removed/modified line counts.
- **eDM LLN Renamer** — bulk-renames campaign files and folders inside a zip by replacing old LLN codes (auto-detected from filenames and image prefixes) with a new one, updating both file paths and in-file references, then re-downloads the zip.

## Why

Email development involves a lot of manual, repetitive, and error-prone steps — Outlook conditional comments, MSO-specific syntax, and client-specific rendering constraints (like Gmail's CSS size limits). These tools remove friction from that workflow.

## Stack

Each tool is a single self-contained HTML/CSS/JS file — no build step, no external dependencies (aside from a bundled JSZip in the renamer). Just open in a browser.
