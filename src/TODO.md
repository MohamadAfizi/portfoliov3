# Portfoliov3 CMS TODO

## Step 1: Validate current state
- [x] Confirm `projects/portfoliov3/contentmanagement.php` contains CMS UI + CRUD
- [x] Inspect `projects/portfoliov3/index.php` (profile summary + footer)
- [x] Inspect `projects/portfoliov3/assets/app.js` (hardcoded profile summary + hardcoded skills tags)

## Step 2: Remove “Back to site” button in CMS
- [x] Remove the `href="/"` back link from `contentmanagement.php`


## Step 3: Add CMS-editable “profile summary”
- [x] Extend `data/content.json` with `profile_summary`
- [ ] Update `contentmanagement.php` to provide UI to edit it
- [ ] Update `action=list` output to include it
- [x] Update homepage (`assets/app.js` or `index.php`) to render `profile_summary` from CMS


## Step 4: Add CMS-editable “tech stack” skills tags
- [x] Extend `data/content.json` with `tech_stack_skills` (string list)
- [ ] Update `contentmanagement.php` to provide UI to edit it
- [ ] Update `action=list` output to include it
- [x] Update homepage (`assets/app.js`) to render skills from CMS


## Step 5: Wire endpoint + test
- [ ] Login → edit profile summary and tech stack skills → save
- [ ] Refresh homepage → verify changes

