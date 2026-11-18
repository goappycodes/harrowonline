# Harrow Online Hub: local WordPress

The Hub runs on its own WordPress, served at `/hub` exactly as it will be at **https://harrowonline.org/hub/**.
Everything needed to run it is in git: WordPress 7.1.4, the SQLite Database Integration plugin, the
`harrow-hub` theme, a local `wp-config.php` and the database (`hub/wp-content/database/hub.sqlite`).
No MySQL or other server is needed.

## Run it

Needs PHP 8.1 or later with the `pdo_sqlite` extension (`php -m` lists it).

```bash
php -S localhost:8020 -t wp-local wp-local/router.php
```

Then open http://localhost:8020/hub/. Admin: http://localhost:8020/hub/wp-admin/ (login in `hub/LOCAL-ADMIN.txt`,
local only). In the Claude desktop app, start the **wp-local** preview instead.

## Where things are

| What | Where |
| --- | --- |
| Theme (all Hub markup, styles and scripts) | `hub/wp-content/themes/harrow-hub/` |
| Page templates (one per prototype page) | `harrow-hub/page-templates/` |
| Header, footer | `harrow-hub/header.php`, `footer.php` |
| Page settings and helpers (`hub_url`, `hub_asset`, current menu item, title) | `harrow-hub/inc/page.php` |
| Which template each WordPress Page uses | `harrow-hub/inc/page-map.php` |
| Database | `hub/wp-content/database/hub.sqlite` |
| Content types and URLs (businesses, offers, events, classes, categories, areas) | `harrow-hub/inc/content-types.php` |
| Content page markup (profiles, archives, offer, event and class pages) | `harrow-hub/inc/render-*.php` |
| Templates for each content type | `harrow-hub/single-hub_*.php`, `taxonomy-hub_*.php` |
| ACF fields (business details, offer/class business, Hub settings > Newsletter), written in PHP | `harrow-hub/inc/acf-fields.php` |
| How pages read those fields; the newsletter band | `harrow-hub/inc/business-acf.php` |
| Copy imported data into the fields (`php wp-local/hub/acf-migrate-local.php`; `import-local.php` runs it too) | `harrow-hub/inc/acf-migrate.php` |

## Working on it

- **Theme from the prototype:** `python tools/build_theme.py` regenerates the header, footer and page templates
  from `src/` (the static prototype). After adding a page, run `php wp-local/hub/setup-local.php` to create its
  WordPress Page, then commit the theme and `hub.sqlite`.
- **Hub content (businesses, offers, events, classes):** `python tools/export_wp_data.py` writes
  `data/wp-hub-data.json` from the prototype's data, then `php wp-local/hub/import-local.php` replaces all Hub
  content in WordPress with it (about 3 minutes). Post types: `hub_business`, `hub_offer`, `hub_event`, `hub_class`;
  taxonomies: `hub_category`, `hub_area`; each post's full record is in its `_hub` meta.
- **The preview's date:** offers and events are worked out from the option `hub_today` (2026-11-11, the sample
  data's "today"). Delete the option to use the real date.
- **Check against the prototype:** with the server running, `python tools/compare_theme.py` (hand-written pages) and
  `python tools/compare_content.py` (businesses, categories, areas, offers, events, classes) compare each page's
  content and title with the static build in `site/hub/`.
- **The database is one binary file.** Git can't merge two people's changes to it. Before changing content or
  settings in wp-admin, pull first; commit `hub.sqlite` straight after, and tell the team. If two changes clash,
  keep one version and redo the other change.
- **Local only:** `wp-config.php`, its keys and the admin login are for local development. Staging and live have
  their own config and a MySQL database.

## Dev server (Laravel Forge)

The same WordPress runs on Laravel Forge (server `appy-dev`), deployed from git with the committed SQLite database.

- **Site:** repository `goappycodes/harrowonline`, web directory `/wp-local`, so WordPress is at `/hub/` as here.
  Nginx rules (send `/` to `/hub/`, block the database, logs and the local CLI scripts, `noindex` header):
  `forge/nginx-hub.conf`. Deploy script: `forge/deploy.sh`.
- **Settings:** `wp-config.php` reads every server setting from the environment or the `.env` file in the
  repository root (Forge: Site > Environment, never committed): `WP_HOME` (with `/hub`), the eight keys and salts,
  `WP_ENVIRONMENT_TYPE`, `WP_DEBUG`, `DISALLOW_FILE_MODS`, and the database (`DB_PATH`, or `DB_ENGINE=mysql` with
  `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`). With no `.env` the local values above apply.
- **Content comes from git.** Each deploy replaces the server's database with the committed `hub.sqlite`, so
  changes made in the server's wp-admin are lost on the next deploy. Change content locally and commit it.
- **Logins:** the users and passwords are the ones in the committed database, i.e. the local ones.
