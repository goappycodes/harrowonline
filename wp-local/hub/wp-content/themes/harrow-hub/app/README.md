# Hub data layer (`app/`)

Every page of the Hub theme, and every route of the Hub API, gets its data from the same **controller functions**.
A controller returns one fixed data shape in two modes:

| Mode | Who calls it | Returns |
| --- | --- | --- |
| `HUB_WEB` (default) | page templates (`page-templates/`, `single-*.php`) | plain PHP arrays to loop over |
| `HUB_API` | REST routes (`routes.php`), for the app | the same data as JSON (`WP_REST_Response`) |

```php
// In a template
$offers = hub_offers_list( $_GET );

// In a route: GET /wp-json/hub/v1/offers?cat=restaurants
hub_route( '/offers', 'GET', function ( WP_REST_Request $req ) {
	return hub_offers_list( $req->get_params(), HUB_API );
} );
```

Plain procedural PHP: no classes. Every function starts with `hub_`.

## Layout

```
app/
├── bootstrap.php          loads core, setup and every module (add new modules to its list)
├── core/
│   ├── debug.php          ?hub_debug=1, hub_debug_log()
│   ├── respond.php        HUB_WEB / HUB_API, hub_respond(), hub_respond_error()
│   ├── request.php        hub_param_slug/bool/int(): read $_GET and API params the same way
│   └── url.php            hub_media_url()
├── setup/
│   ├── rest-routes.php    namespace hub/v1, hub_route()
│   ├── accounts.php       sign-in redirects for /account/ pages, sign-out, wp-admin Users: plan and business fields
│   ├── billing.php        /billing/checkout/ and /billing/portal/ (send to Stripe), Payments log in wp-admin
│   ├── claims.php         claim pages' sign-in and email link, wp-admin > Claims (review, approve, reject)
│   ├── add-business.php   /add-business/ sign-in; Pending Review -> Published (owner gets it) or Trash (declined)
│   └── submissions.php    account offer/event/class forms: saving, Publish or Trash emails, £15 event payment
└── modules/
    ├── businesses/        data.php  rules.php  shape.php  controller.php  routes.php
    ├── events/            one-off events and weekly classes (What's On)
    ├── offers/
    ├── categories/
    ├── areas/
    ├── account/           sign-in, register, reset, the signed-in account and its business
    ├── billing/           Stripe: checkout, customer portal, webhook (sets the business's plan)
    ├── claims/            claiming a business: proof (email, phone, document), staff approve or reject
    └── submissions/       offers, events and classes from the account pages: who may, drafts, Pending Review
parts/                     display only: each part gets arrays in $args and prints them
├── account/               settings (the settings page's forms)
├── businesses/            row, card, ticker, event-row, profile, profile-imported, directory
├── events/                row (What's On list), card, class-card, single (event page), class-single
└── offers/                card, tile, card-full, unlock-box, filters, single
inc/view.php               small markup helpers for parts (hub_view_*): name with its status mark, photo, address,
                           "Open today"... They take a shape and print it; no queries.
```

## The five files of a module

| File | Does | Naming | May call |
| --- | --- | --- | --- |
| `data.php` | the **only** place that touches WordPress (WP_Query, meta, ACF, terms) | `hub_{module}_db_*` | WordPress |
| `rules.php` | business rules on plain values: what is live, what is shown, the order | `hub_{module}_rule_*`, constants `HUB_{MODULE}_*` | nothing (pure PHP) |
| `shape.php` | turns a post into the module's one array shape; the comment at the top lists every field | `hub_{singular}_shape*` | rules |
| `controller.php` | the functions pages and routes call; `$mode` is always the last argument | `hub_{module}_list`, `hub_{singular}_get`, ... | its own data, rules, shape; **other modules' controllers only** |
| `routes.php` | REST routes, each calling a controller in `HUB_API` mode | | its controller |

Functions starting with `_hub_` are internal helpers of their module's controller.

## Rules that keep it easy to change

1. **Templates and parts never query WordPress.** A template calls a controller, then hands the arrays to parts.
2. **Outside a module, call only its `controller.php` functions.** Offers needs its businesses, so it calls
   `hub_businesses_summaries( $ids )`, never `hub_businesses_db_record()`. Controllers may call each other; shapes
   must not (pass what they need in, as offers does with each offer's business).
   Two modules that need each other must not loop: a business's offer count is added by the businesses controller
   (`counts`), never by its shape, because an offer's shape includes its business.
3. **One shape per module, used everywhere.** The listing card, the profile card, the offer page and the app all
   read the same `offer` array. Add a field once in `shape.php` and it's available everywhere (and in the API).
4. **Shapes hold plain data:** text without HTML or entities, full unescaped URLs, booleans, `null` for "none".
   Parts escape when they print (`esc_html`, `esc_attr`, `esc_url`).
5. **Never put private data in a shape.** Subscriber-only offer codes are `null` in the shape; the page shows the
   unlock form and hub.js fetches the code after the email is confirmed.
6. **Controllers return `hub_respond( $data, $mode )`** or, when nothing is found,
   `hub_respond_error( $code, $message, $mode )`: `null` for a template (show the 404), a JSON error for the API.

## Adding a module (e.g. events)

1. Copy `modules/offers/` to `modules/events/` and rename the functions (`hub_offers_` → `hub_events_`, `hub_offer_` → `hub_event_`).
2. Write `data.php` first (queries), then `shape.php` (list every field in its top comment), then `rules.php`.
3. Add the controller functions the pages need, with the same param names the page URLs use (`?cat=`, `?area=`).
4. Add the routes, then add `'events'` to the module list in `bootstrap.php`.
5. Move the markup into `parts/events/`, switch the templates to the controller, and add converted pages to
   `HAND_EDITED` in `tools/build_theme.py` so the prototype build never overwrites them.
6. Remove the old code from `inc/` once nothing calls it.

## Debugging

- **See exactly what a page gets:** open the matching API URL. `/hub/wp-json/hub/v1/offers?cat=restaurants`
  is what `/hub/offers/?cat=restaurants` renders.
- **See every controller call a page made:** add `?hub_debug=1` to the page URL (works for admins, or for anyone
  while `WP_DEBUG` is on). The browser console shows each call (controller, mode, time since the request started,
  peak memory, full result), and the same JSON is in `<script id="hub-debug">` at the end of the page.
- **Why did something 404?** Controllers log the reason with `hub_debug_log()` to `wp-content/debug.log`
  (local `wp-config.php` has `WP_DEBUG_LOG` on).
- **Try a controller from the command line** (from `wp-local/hub/`):

  ```bash
  php -r 'define("WP_USE_THEMES", false); $_SERVER["HTTP_HOST"] = "localhost:8020"; require "wp-load.php";
          print_r( hub_offer_get( "first-session-free" ) );'
  ```

## API so far

| Route | Controller |
| --- | --- |
| `GET /wp-json/hub/v1/businesses?cat=&area=&limit=&offset=` | `hub_businesses_list()` |
| `GET /wp-json/hub/v1/businesses/{id or slug}` | `hub_business_get()` |
| `GET /wp-json/hub/v1/businesses/featured`, `/businesses/newest` | `hub_businesses_featured()`, `hub_businesses_newest()` |
| `GET /wp-json/hub/v1/events/whats-on`, `/events/month?month=2026-11`, `/events/weekend` | `hub_events_whats_on()`, `hub_events_month()`, `hub_events_weekend()` |
| `GET /wp-json/hub/v1/events?from=&to=&cat=&area=&featured=1`, `/events/{id or slug}` | `hub_events_list()`, `hub_event_get()` |
| `GET /wp-json/hub/v1/classes?type=&area=&day=&age=`, `/classes/{id or slug}` | `hub_classes_list()` (the Classes and activities page order), `hub_class_get()` |
| `GET /wp-json/hub/v1/offers?cat=&area=&sub=1&limit=&offset=` | `hub_offers_list()` |
| `GET /wp-json/hub/v1/offers/{id or slug}` | `hub_offer_get()` |
| `GET /wp-json/hub/v1/businesses/{id}/offers` | `hub_offers_for_business()` |
| `GET /wp-json/hub/v1/categories`, `/categories/{slug}` | `hub_categories_list()`, `hub_category_get()` |
| `GET /wp-json/hub/v1/areas`, `/areas/{slug}` | `hub_areas_list()`, `hub_area_get()` |
| `GET /wp-json/hub/v1/account/me`; `POST /account/sign-in`, `/account/register`, `/account/reset`, `/account/sign-out` | `hub_account_current()`, `hub_account_sign_in()`, `hub_account_register()`, `hub_account_reset_request()`, `hub_account_sign_out()` |
| `GET /wp-json/hub/v1/billing/catalogue`, `/billing/status`; `POST /billing/checkout`, `/billing/portal` | `hub_billing_catalogue()`, `hub_billing_status()`, `hub_billing_checkout_plan()` / `_item()`, `hub_billing_portal()` |
| `POST /wp-json/hub/v1/billing/webhook` (Stripe only, checked by signature) | `hub_billing_webhook()` |

Content routes are public and read-only. Account and billing routes that need a signed-in user use the permission
callback (the 4th argument of `hub_route()`). From JavaScript, send WordPress's REST nonce (`X-WP-Nonce`) or the
request counts as signed out.

## Modules still to move here

- **Search** (`page-templates/search.php` and hub.js) reads the prototype's `assets/data/hub-index.json`; it should
  move to the API.
- **Homepage categories and areas**, the categories page, the account pages' forms and lists (listing, offers,
  events, classes: still the prototype's sample content; only the account name, greeting and billing are real) and the newsletter band
  (`inc/business-acf.php`) are still old-style.
