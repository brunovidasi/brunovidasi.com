# Gerador de CPF

A small browser-based tool that generates mathematically valid Brazilian CPF numbers (correct check digits) for software testing and filling out fictitious forms — with an optional fiscal region / state selector.

## Files

- `index.html` — markup/structure
- `style.css` — styling (paper/official-document theme)
- `script.js` — CPF generation logic and UI behavior

## Usage

Open `index.html` in any modern browser. No build step, server, or dependencies required (the only external resource is a Google Fonts stylesheet import).

1. Pick a state/fiscal region, or leave it on "Aleatório" for a random one.
2. Choose how many CPFs to generate and which format (`000.000.000-00` or plain digits).
3. Click **Gerar**. Use the copy buttons to copy a single result or the whole batch.

## How it works

Each CPF's first 8 digits are randomized, the 9th digit encodes the chosen fiscal region (per Receita Federal's regional digit table), and the final two check digits are computed with the standard CPF modulo-11 algorithm — so every generated number passes CPF validation.

## Disclaimer

For software testing purposes only. Generated numbers are mathematically valid but do not belong to any real, registered individual. Do not use for illegal purposes.
