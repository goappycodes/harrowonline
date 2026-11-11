# Harrow Online Hub: front end

Plain HTML and CSS for **Harrow Online Hub** (powered by Harrow Online Newsletter), built from the approved dev brief
(`01 Harrow Online Hub - Dev Brief`, with the Hub parts of briefs 02 and 03). It is the design and markup reference for the
custom WordPress theme that will run at `harrowonline.org/hub`.

Live preview: https://harrowonline.vercel.app/hub/ (noindex). Every page is listed at [/hub/pages/](https://harrowonline.vercel.app/hub/pages/).
The previous data-only preview is kept on the `old` branch.

## What's here

| Path | What it is |
| --- | --- |
| `site/` | **The built site** (what Vercel serves). The Hub lives in `site/hub/`, matching `harrowonline.org/hub/`; `/` redirects there. |
| `site/hub/assets/css/hub.css` | All styles. Tokens, header, footer, buttons and newsletter band are ported from the live theme (`Newspaper-child/assets/css/ho-brand.css`). |
| `site/hub/assets/js/hub.js` | Menu, search panel, filters, billing toggle, offer unlock, and the directory search over all listings. No dependencies. |
| `site/hub/assets/img/icons.svg` | Icon sprite (`<use href="/assets/img/icons.svg#i-pin">`). |
| `site/hub/business/{slug}/` | 2,602 generated business profiles, one folder per production URL |
| `site/hub/category/`, `site/hub/area/` | 24 category and 19 area archives (generated) |
| `site/hub/data/hub-index.json` | Compact listing index used by search |
| `src/pages/` | Hand-written page bodies (homepage, pricing, events, account, etc.) |
| `src/partials/` | Shared header, Hub bar, footer, newsletter strip and sample cards. `gen-*.html` are generated. |
| `build.py` | Assembles `src/` + generated pages into `site/hub/{url}/index.html` |
| `seo.py` | URL map, canonical/robots/Open Graph head, JSON-LD graph and the index rules (brief 03) |
| `sitemap.py` | Hub XML sitemaps (indexable pages only) |
| `tools/seo_audit.py` | Checks every built page: meta, canonical, schema, headings, alt text, link text, labels |
| `tools/make_icons.py` | Favicon, app icons, share image (`og-default.png`) and `site.webmanifest` |
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

Open http://localhost:8000/hub/pages/. Use a local server rather than opening files directly: links and the icon sprite
use absolute paths (`/hub/assets/...`).

To refresh the listing data, run `scripts/1_fetch_overture.py`, `2_filter_export.py`, `3_build_site_data.py` (needs `duckdb`,
`pandas`, `numpy`), then `python build.py`.

## Content in this preview

- **Real:** the 2,590 business listings (Overture Maps open data). All are shown as **Unclaimed** and `noindex, follow`, as brief 03 requires for imported listings.
- **Claimed samples:** 12 fictional claimed businesses in `data/claimed.json` (Basic, Enhanced, Premium and Local Partner)
  with complete profiles, Local Offers and events. `gen_claimed.py` turns them into profiles laid out per plan, ranks them
  above unclaimed listings, and builds the Offers page list. Phone numbers use Ofcom's reserved drama range; each profile
  carries a "Sample listing" badge. Add or edit businesses there, then run `python build.py` (and `tools/gen_events.py`
  if you add events to the What's On calendar).
- **Samples:** featured businesses (Hill Street Kitchen, Wealdstone Fitness Studio, etc.), offers, events, classes, the signed-in account and all figures.
- Categories and areas are drafts for the client to approve (brief 01, 6.1). Areas come from the nearest area centre to each map pin, so a few edge cases will need moving.

## Moving to WordPress

- `src/partials/header.html`, `hubbar.html`, `footer.html`, `nl-strip.html` → `header.php`, `footer.php` and template parts.
- Each `src/pages/*.html` → a page template; generated pages → `single-listing.php`, `taxonomy-listing_category.php`, `taxonomy-area.php`.
- `.ph` blocks are image placeholders; swap for `<img>` / `wp_get_attachment_image()`.
- The client-side search in `hub.js` is a stand-in; in WordPress, render results server-side with the same markup (`.biz-row`).
- The base path is one constant, `BASE = "/hub"` in `build.py`: page sources use root-absolute links and the build prefixes them. Keep the same rule in WordPress (brief 01, section 1).
- Forms post nowhere in the preview. Newsletter forms map to `POST /wp-json/ho/v1/subscribe` with a `signup_source`.

## SEO (ready for WordPress)

Everything below is in the built HTML, so each template can be copied with its SEO intact. Rules come from brief 03, S3.

- **URLs:** pages are built as folders that match production (`/hub/events/`, `/hub/business/{name-area}/`); the map is
  `seo.PAGE_PATHS`. Source pages still link to `/x.html`; the build rewrites links. Old `.html` preview links 301 (`vercel.json`).
- **Head on every page:** unique `<title>` (`{Business} in {Area} | Harrow Online Hub` etc.), meta description (trimmed to
  158 characters), `robots`, absolute `canonical` on `https://harrowonline.org/hub/…`, Open Graph + X card with
  `og-default.png`, theme colour `#DF2028`, favicon, Apple touch icon and web app manifest.
- **Index rules:** unclaimed listings `noindex, follow`; claimed listings index only when filled in (50+ word description
  and hours or a photo; Harrow Town Centre Traders is left short to show the rule); category/area archives `noindex` until
  they have 5+ indexable listings and intro text; search, filters, month view, account, claim, add-business, sign-in and
  other utility pages `noindex`. Thresholds are constants at the top of `seo.py`.
- **Structured data:** one JSON-LD `@graph` per page: the shared `NewsMediaOrganization` (`https://harrowonline.org/#organization`,
  same details as Yoast), the Hub `WebSite` with a `SearchAction`, a `WebPage` (or `CollectionPage`, `SearchResultsPage`,
  `ItemPage`, `AboutPage`, `ContactPage`), a `BreadcrumbList` built from the visible breadcrumb (one source), and the page's
  own node: the most specific `LocalBusiness` type with address, geo, phone, opening hours and offers on indexable profiles;
  `Event` (with `eventSchedule` for classes); `ItemList` on archives.
- **Sitemaps:** `/hub/sitemap_index.xml` → page, business and event sitemaps, indexable canonical URLs only. Add
  `Sitemap: https://harrowonline.org/hub/sitemap_index.xml` to the main robots.txt.
- **Markup:** one `<h1>` per page and no skipped heading levels; landmarks (`header`, `nav`, `main`, `footer`); `<time datetime>`
  on event dates; `<address>` on business contact details; descriptive link text (no bare "View" or "See all"); every form
  field labelled. Run `python tools/seo_audit.py` after any change (it exits non-zero on a problem).
- **Preview only:** the Vercel preview sends `X-Robots-Tag: noindex, nofollow` on every response, so nothing here gets indexed.

## Phones

`hub.css` section 18 is the phone layer (767px and below; tablet and desktop are unchanged): compact spacing, list-style
cards, swipe rails for categories and photos, one-line breadcrumbs, 16px form fields (no iOS zoom), a compact sticky
save bar, and a bottom tab bar (`src/partials/tabbar.html`: Hub, Search, Offers, What's On, Account) on Hub pages.

## Attribution

Business data © Overture Maps Foundation and its sources (BrightQuery, Meta, Microsoft, Uberall, DAC, PinMeTo; CDLA Permissive 2.0). See `/hub/data-sources/`.

## Header, footer and logo

- `src/partials/header.html` and `footer.html` are copied verbatim from the live harrowonline.org markup by
  `tools/sync_live_chrome.py`. On Hub pages the only differences are the Hub logo (linking to the Hub homepage), an account
  icon in the header, and one footer line with the Overture Maps attribution and the Hub privacy/terms links. Re-run it
  if the live header or footer changes.
- `site/hub/assets/img/hub-logo.png` and `hub-logo-white.png` are the live logos with a red "HUB" block beside "HARROW",
  made by `tools/make_hub_logo.py` (2x images, displayed at the live logo's height).
- The Hub bar under the header (Businesses, Categories, Areas, Offers, What's On, Pricing) is the Hub's own menu.
