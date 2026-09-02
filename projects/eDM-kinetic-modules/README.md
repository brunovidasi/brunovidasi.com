# eDM Kinetic Modules

This repository contains three email module concepts built as self-contained HTML/CSS examples for email marketing:

- [accordian-3-items](accordian-3-items/) — a 3-panel accordion-style module
- [carousel-5-items](carousel-5-items/) — a 5-slide carousel-style module
- [tabs-4-items](tabs-4-items/) — a 4-tab switcher module

## What they have in common

All three projects are designed to show how interactive email patterns can be built without JavaScript or AMP for Email. Each one is a standalone HTML email mockup, using CSS-based tricks such as the checkbox hack and sibling selectors to create tap/click interactions.

They all share the same approach:

- table-based HTML email structure
- no external JavaScript
- pure HTML/CSS behavior
- placeholder branding and marketing copy
- a fallback state for email clients that do not support the advanced CSS pattern

## Why these examples matter

The goal is to demonstrate that motion and interactivity can still be created in email while keeping the content readable in clients with limited CSS support. The interactive version works in supported clients, while the fallback version keeps the full content visible and usable in weaker email environments.

Each folder includes its own `index.html` and README with more detail on that specific module.
