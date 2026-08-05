# Portfolio v3 — plain PHP + JSON

Portfolio v3 uses plain PHP includes. It has no Laravel, Composer, database, CMS, build step, or package installation.

## Folder structure

```text
portfoliov3/
├── compose.yaml                  # optional local Apache/PHP server
├── README.md
└── src/                          # web root
    ├── index.php                 # page composer; includes modules only
    ├── api/
    │   └── track-visitor.php     # asynchronous visitor append endpoint
    ├── assets/
    │   ├── styles.css            # the one global stylesheet
    │   └── app.js                # the one global JavaScript file
    ├── data/
    │   ├── content.json          # the one editable content source
    │   ├── visitors.json         # generated visitor log; intentionally ignored by Git
    │   └── .htaccess             # blocks public visitor-log access on Apache
    ├── lib/                      # reusable PHP functions only
    │   ├── bootstrap.php
    │   ├── content-loader.php
    │   ├── json-helpers.php
    │   └── visitor-tracker.php
    ├── media/
    │   ├── images/               # jpg, jpeg, png, webp, gif, svg
    │   └── videos/               # mp4, webm
    └── sections/                 # homepage modules
        ├── hero.php
        ├── analytics.php
        ├── projects.php
        ├── milestones.php
        ├── industry-experiences.php
        └── portfolio.php
```

Use subfolders when media grows, for example `media/images/projects/portfolio-v3/` or `media/videos/projects/portfolio-v3/`. Store only media paths in `content.json`.

## Edit content

Edit `src/data/content.json` in VS Code or Notepad. The homepage reads this file once in PHP and passes the same data to `app.js`; there is no duplicate hardcoded card or skills list in JavaScript.

- `site`: page title, name, role, location, email, favicon, and footer.
- `navigation`: category labels and descriptions.
- `ui`: chart, card-action, and pagination labels.
- `profile_summary`: introduction paragraph.
- `tech_stack`: skill tags.
- `projects`, `milestones`, `industry_experiences`: card arrays.

Keep JSON commas and quotes valid. A quick validation command is:

```powershell
Get-Content -Raw src/data/content.json | ConvertFrom-Json | Out-Null
```

## Visitor tracking

After the page loads, `app.js` asynchronously calls `api/track-visitor.php`. The endpoint creates `src/data/visitors.json` when needed and adds one entry using a file lock, without delaying the visible page. Public IP addresses are anonymized before storage. For public IPs, PHP requests location data from `https://ipapi.co/{ip}/json/`; failures do not stop the page. Previous location results are reused for the same anonymized IP range.

The visitor chart is calculated from this JSON file. The project chart is calculated from the project `techStack` values in `content.json`.

The visitor log is ignored by Git so real analytics and personal data are never committed. The web-server user must have write permission for `src/data/`. Apache uses `src/data/.htaccess` to deny direct downloads. If deploying behind Nginx or another server, add an equivalent rule blocking `/data/visitors.json`.

## Run locally

With Docker:

```powershell
docker compose up -d
```

Then open `http://127.0.0.1:8082`.

Or with PHP installed:

```powershell
php -S 127.0.0.1:8082 -t src
```

The PHP development server does not process `.htaccess`; do not expose it publicly.

## Why there is no vendor folder

`vendor/` is Composer's dependency directory. It was needed by the previous Laravel installation, but none of this plain-PHP code imports Composer packages. Moving it into a PHP file would not help; removing it is the correct setup for v3. If a future feature genuinely needs a third-party PHP package, Composer can recreate `vendor/` from a `composer.json` file.
