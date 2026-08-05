# Portfolio v3 — Local JSON-driven PHP site

Overview and quick instructions for the new plain-PHP structure.

Structure highlights:
- `src/index.php` — main entry (existing)
- `src/pages/` — page files that include sections
- `src/partials/` — `header.php`, `footer.php`, `nav.php`, `meta.php`
- `src/sections/` — reusable section renderers (e.g., `hero.php`, `projects.php`)
- `src/assets/` — `styles.css` and `app.js` (global)
- `src/data/content.json` — single content source (edit directly)
- `src/data/visitors.json` — visitor tracking (append-only)
- `src/media/images/`, `src/media/videos/` — media storage
- `src/lib/` — helpers `json-helpers.php`, `content-loader.php`, `visitor-tracker.php`

Editing content:
- Open `src/data/content.json` in your editor and update `pages` → `<page-slug>` → `sections` array.
- Add media files to `src/media/images` or `src/media/videos` and reference paths in `content.json`.

Visitor tracking:
- Entries are appended to `src/data/visitors.json`. This is a simple file-based tracker for v3.
