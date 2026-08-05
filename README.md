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
    │   ├── github-contributions.php # server-side GitHub GraphQL endpoint
    │   └── track-visitor.php     # asynchronous visitor append endpoint
    ├── assets/
    │   ├── styles.css            # the one global stylesheet
    │   └── app.js                # the one global JavaScript file
    ├── content/
    │   └── readmes/projects/     # local Markdown docs for non-Git projects
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

Edit `src/data/content.json` in VS Code or Notepad. The homepage reads this file once in PHP and passes the same data to `app.js`; there is no duplicate hardcoded card or skills list in JavaScript. Local Markdown files are used only for long-form non-Git project documentation.

- `site`: page title, name, role, location, email, favicon, and footer.
- `navigation`: category labels and descriptions.
- `ui`: chart and pagination labels.
- `github`: GitHub username, profile URL, graph title, and graph wording.
- `profile_summary`: introduction paragraph.
- `tech_stack`: skill tags.
- `projects` and `milestones`: card arrays.
- `industry_experiences`: role-based timeline entries with text highlights.

Each card has an `actions` array. External actions remain disabled until an HTTP(S) URL is added:

```json
{
  "title": "Project ABC",
  "description": "Project summary.",
  "actions": [
    {
      "label": "readme",
      "type": "external",
      "url": "https://github.com/MohamadAfizi/projectabc"
    },
    {
      "label": "view",
      "type": "external",
      "url": "https://fizzyjamal.com/projectabc"
    }
  ]
}
```

For a non-Git project, use a local Markdown action:

```json
{
  "label": "readme",
  "type": "modal",
  "source": "content/readmes/projects/project-name.md"
}
```

Modal sources must stay inside `src/content/readmes/` and use the `.md` extension. Projects use `readme` and `view` actions, learning paths use `profile`, and certifications or completed courses use `certificate`.

Industry Experience uses a separate text-only timeline structure:

```json
"industry_experiences": {
  "keyAchievements": [
    {
      "title": "Workflow enhancement",
      "summary": "Outcome-focused and privacy-safe achievement."
    }
  ],
  "roles": [
    {
      "from": "Dec 2024",
      "to": "Present",
      "role": "Application Analyst & Developer",
      "scope": "A short, privacy-safe summary of the role.",
      "current": true
    }
  ]
}
```

To add a key achievement, insert another object inside `keyAchievements`. To add an experience, insert another object inside `roles`. Separate adjacent objects with commas. The page automatically creates achievement numbers, timeline dots, and connecting line segments.

Roles follow their JSON order, so put the newest role first. Set only the active role to `"current": true`; its dot receives the filled glow while past-role dots stay hollow. Use `"to": "Present"` for the active role and a month plus year for completed roles. There is no hard-coded three-role limit.

The timeline intentionally has no card actions, subcategory controls, or pagination.

Keep JSON commas and quotes valid. A quick validation command is:

```powershell
Get-Content -Raw src/data/content.json | ConvertFrom-Json | Out-Null
```

## Visitor tracking

After the page loads, `app.js` asynchronously calls `api/track-visitor.php`. The endpoint creates `src/data/visitors.json` when needed and adds one entry using a file lock, without delaying the visible page. Public IP addresses are anonymized before storage. For public IPs, PHP requests location data from `https://ipapi.co/{ip}/json/`; failures do not stop the page. Previous location results are reused for the same anonymized IP range.

The visitor chart is calculated from this JSON file. Its existing data and presentation are independent from the GitHub contribution slide.

## GitHub contributions

Visitor Analytics alternates every 10 seconds with the account-wide GitHub contribution calendar for `MohamadAfizi`. The browser calls `api/github-contributions.php`, which queries GitHub's official GraphQL API and caches the sanitized response in the visitor's native PHP session for 15 minutes. No extra project data file is created.

The endpoint requires a GitHub token in the server environment. Never put the token in PHP, JavaScript, `content.json`, or Git.

In PowerShell, configure the token and start PHP from the same terminal:

```powershell
$env:GITHUB_TOKEN = "your-token"
php -S localhost:8000 -t src
```

For Docker, set the variable in the terminal before starting the container:

```powershell
$env:GITHUB_TOKEN = "your-token"
docker compose up -d
```

If the token expires or GitHub is unavailable, the GitHub slide shows a profile link while Visitor Analytics and the rest of the site continue normally.

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
