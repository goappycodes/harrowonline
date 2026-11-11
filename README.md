# Harrow Online Hub: front end

Plain HTML and CSS for **Harrow Online Hub** (powered by Harrow Online Newsletter), built from the approved dev brief
(`01 Harrow Online Hub - Dev Brief`, with the Hub parts of briefs 02 and 03). It is the design and markup reference for the
custom WordPress theme that will run at `harrowonline.org/hub`.

Live preview: https://harrowonline.vercel.app/hub/ (noindex). Every page is listed at [/hub/pages.html](https://harrowonline.vercel.app/hub/pages.html).
The previous data-only preview is kept on the `old` branch.

## What's here

| Path | What it is |
| --- | --- |
| `site/` | **The built site** (what Vercel serves). The Hub lives in `site/hub/`, matching `harrowonline.org/hub/`; `/` redirects there. |
| `site/hub/assets/css/hub.css` | All styles. Tokens, header, footer, buttons and newsletter band are ported from the live theme (`Newspaper-child/assets/css/ho-brand.css`). |
| `site/hub/assets/js/hub.js` | Menu, search panel, filters, billing toggle, offer unlock, and the directory search over all listings. No dependencies. |
| `site/hub/assets/img/icons.svg` | Icon sprite (`<use href="/assets/img/icons.svg#i-pin">`). |
| `site/hub/business/` | 2,590 generated business profiles, one per listing |
| `site/hub/category/`, `site/hub/area/` | 24 category and 19 area archives (generated) |
| `site/hub/data/hub-index.json` | Compact listing index used by search |
| `src/pages/` | Hand-written page bodies (homepage, pricing, events, account, etc.) |
| `src/partials/` | Shared header, Hub bar, footer, newsletter strip and sample cards. `gen-*.html` are generated. |
| `build.py` | Assembles `src/` + generated pages into `site/` |
| `hub_data.py` | Draft local categories (Overture → 24 categories) and areas (map pin → 19 Harrow areas) |
| `gen_listings.py` | Generates profiles, archives and the search index from `data/listings.json` |
| `data/`, `scripts/` | Overture Maps listings (~2,600 Harrow businesses) and the scripts that produced them |

## Build and run

Needs Python 3.10+ only.

```bash
python build.py              # full build: pages + 2,590 profiles + archives + search index
python build.py --pages-only # just the hand-written pages (fast)
python build.py --watch      # rebuild pages on save
python -m http.server 8000 --directory site
```

Open http://localhost:8000/hub/pages.html. Use a local server rather than opening files directly: links and the icon sprite
use absolute paths (`/hub/assets/...`).

To refresh the listing data, run `scripts/1_fetch_overture.py`, `2_filter_export.py`, `3_build_site_data.py` (needs `duckdb`,
`pandas`, `numpy`), then `python build.py`.

## Content in this preview

- **Real:** the 2,590 business listings (Overture Maps open data). All are shown as **Unclaimed** and `noindex, follow`, as brief 03 requires for imported listings.
- **Samples:** featured businesses (Hill Street Kitchen, Wealdstone Fitness Studio, etc.), offers, events, classes, the signed-in account and all figures. A note under the Hub bar says so on every page.
- Categories and areas are drafts for the client to approve (brief 01, 6.1). Areas come from the nearest area centre to each map pin, so a few edge cases will need moving.

## Moving to WordPress

- `src/partials/header.html`, `hubbar.html`, `footer.html`, `nl-strip.html` → `header.php`, `footer.php` and template parts.
- Each `src/pages/*.html` → a page template; generated pages → `single-listing.php`, `taxonomy-listing_category.php`, `taxonomy-area.php`.
- `.ph` blocks are image placeholders; swap for `<img>` / `wp_get_attachment_image()`.
- The client-side search in `hub.js` is a stand-in; in WordPress, render results server-side with the same markup (`.biz-row`).
- The base path is one constant, `BASE = "/hub"` in `build.py`: page sources use root-absolute links and the build prefixes them. Keep the same rule in WordPress (brief 01, section 1).
- Forms post nowhere in the preview. Newsletter forms map to `POST /wp-json/ho/v1/subscribe` with a `signup_source`.

## Attribution

Business data © Overture Maps Foundation and its sources (BrightQuery, Meta, Microsoft, Uberall, DAC, PinMeTo; CDLA Permissive 2.0). See `/data-sources.html`.
