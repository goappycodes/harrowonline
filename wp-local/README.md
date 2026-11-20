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
| **Data layer: controllers for pages and the API** (read `app/README.md` first) | `harrow-hub/app/` |
| Display pieces that print controller data (business rows and profiles, events and classes, offers) | `harrow-hub/parts/` |
| Hub API (same data as the pages, JSON) | `/hub/wp-json/hub/v1/` (e.g. `/home`, `/pages/events`, `/pages/classes`, `/pages/offers`, `/pages/pricing`, `/pages/advertise`, `/businesses`, `/events/whats-on`, `/classes`, `/offers`, `/categories`, `/areas`) |
| Page templates (one per prototype page) | `harrow-hub/page-templates/` |
| Header, footer (edited by hand; `build_theme.py` no longer regenerates them) | `harrow-hub/header.php`, `footer.php` |
| Privacy notice, Terms of use and Data sources: the text is each Page's own editor (“On this page” is made from its Heading 2s); the title, intro, updated line and draft note are its Page text box. Starting text: `harrow-hub/content/*.html` | `harrow-hub/parts/pages/document.php`, `app/modules/pages/` |
| wp-admin colours: the **Harrow Hub** admin colour scheme (WordPress's Default with the Hub red; everyone's unless they pick another in Users > Profile). After a WordPress update: `python tools/build_admin_colors.py` | `harrow-hub/inc/admin/admin-colors.php`, `harrow-hub/admin/hub-admin-colors.css` |
| Main menu and social icons: **Appearance > Menus** (locations “Main menu” and “Social links”; an item's CSS class `icon-plus`, `icon-star`… adds that icon) | `harrow-hub/inc/menus.php` |
| Footer: **Appearance > Widgets** (classic screen): under the logo, link columns, newsletter, topics row, badges, legal text. An empty area shows today's content | `harrow-hub/inc/widgets.php` |
| Today's menus and footer widgets, created once (`php -r` with `hub_setup_menus_widgets()`, or on switching the theme on); nothing is overwritten | `harrow-hub/inc/menus-setup.php` |
| Page settings and helpers (`hub_url`, `hub_asset`, current menu item, title) | `harrow-hub/inc/page.php` |
| Which template each WordPress Page uses | `harrow-hub/inc/page-map.php` |
| Database | `hub/wp-content/database/hub.sqlite` |
| Content types and URLs (businesses, offers, events, classes, categories, areas) | `harrow-hub/inc/content-types.php` |
| Templates for each content type | `harrow-hub/single-hub_*.php`, `taxonomy-hub_*.php` |
| wp-admin boxes (native meta boxes, no plugin): Business, Offer, Event and Class details, an offer's or class's Business, Hub settings > Newsletter | `harrow-hub/inc/admin/` (script and styles in `harrow-hub/admin/`) |
| Homepage text, links, tiles and section switches (the Homepage box on the Home page, Pages > Home; empty fields use today's wording) | `harrow-hub/inc/admin/home-box.php`, `harrow-hub/app/modules/home/` |
| Page text: What's On, Classes and activities, Local Offers, Pricing and Advertise in the newsletter, their text, links and panels (the Page text box on each of those Pages; the £ amounts on Pricing and Advertise come from `app/modules/billing/rules.php`; Advertise's booking dates follow today's date, with a list of dates with no issue in its box; empty fields use today's wording). To add a page, see `app/modules/pages/rules.php` | `harrow-hub/inc/admin/page-text-box.php`, `harrow-hub/app/modules/pages/` |
| A business category's Icon (homepage Popular categories cards) and Intro (under the category page's heading): Businesses > Categories | `harrow-hub/inc/admin/category-fields.php`, `app/modules/categories/` |
| How pages read those fields | `harrow-hub/app/modules/*/data.php` |
| Fill the business, offer, event and class fields from the imported data (`php wp-local/hub/fields-migrate-local.php`; `import-local.php` runs it too) | `harrow-hub/inc/fields-migrate.php` |
| Newsletter band (option `hub_newsletter`) | `harrow-hub/inc/newsletter.php` |
| Page titles and descriptions for search engines and shared links: the **Yoast SEO** plugin's box on each page, business, offer, event and class. Empty Yoast fields use the template's own title and description, so nothing changes until someone fills them in | `hub/wp-content/plugins/wordpress-seo/` (committed, active in `hub.sqlite`), `harrow-hub/inc/seo.php` |

## Working on it

- **Theme from the prototype:** `python tools/build_theme.py` regenerates the page templates (not the hand-edited ones)
  from `src/` (the static prototype). The header and footer are edited by hand since the menus and widgets. After adding a page, run `php wp-local/hub/setup-local.php` to create its
  WordPress Page, then commit the theme and `hub.sqlite`.
- **Hub content (businesses, offers, events, classes):** `python tools/export_wp_data.py` writes
  `data/wp-hub-data.json` from the prototype's data, then `php wp-local/hub/import-local.php` replaces all Hub
  content in WordPress with it (about 3 minutes). Post types: `hub_business`, `hub_offer`, `hub_event`, `hub_class`;
  taxonomies: `hub_category`, `hub_area`; each post's full record is in its `_hub` meta.
- **Classes and activities page cards** (short text, place on the page, wording) come from `tools/classes_data.py`
  (each class's `card` in the export; Kodak FC's teams are listed there only, with no page of their own). After
  changing it: `python tools/export_wp_data.py`, then `php wp-local/hub/update-classes-local.php` (updates the
  classes without a full import), then commit `hub.sqlite`.
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

## Accounts and payments (Stripe)

- **Accounts** (`app/modules/account/`, `app/setup/accounts.php`): sign-in, register, forgot password and
  sign-out (`/hub/sign-out/`) are real WordPress logins. Every `/hub/account/...` page needs a signed-in user. Every
  Hub account is a WordPress **Subscriber** and can't open wp-admin. Two user fields say what it is: **account
  type** (`hub_account_type`: business owner or organiser) and **plan** (`hub_plan`: Basic, Featured or Local
  Partner, business owners only; new ones start on Basic). Each account is linked to at most one business. In
  wp-admin under Users, the list has *Hub account* and *Plan* columns and Basic / Featured / Local Partner filters.
  Each user's page has **Plan** and **Business** (post ID or slug).
- **The plan follows the business.** Stripe payments, plan edits on the business, and linking or unlinking an
  owner all update the user's Plan, and an owner with no business is Basic. Changing Plan on the user changes the
  business's plan too, but a business paying through Stripe gets Stripe's plan back on its next billing event, so
  change those in Stripe. A local test owner linked to AJ Pizza's (Featured) is in `hub/LOCAL-ADMIN.txt`.
- **Claiming a business** (`app/modules/claims/`, `app/setup/claims.php`): "Claim this business" opens
  `/hub/claim/?listing={slug}`, which needs a signed-in account with a confirmed email. It refuses businesses that
  are already claimed, accounts that already manage one, and a second claim while one is waiting (5 a day). There
  are three ways to prove it:
  - **Email:** a link is sent to an address on the business website's own domain. Shared hosts such as Facebook
    or Wix don't count.
  - **Phone:** staff call the number on the listing.
  - **Document:** the upload is a PDF, JPG or PNG of 10MB or less. It's stored in `wp-content/hub-private/`
    (gitignored and blocked in nginx), and staff download it from wp-admin.

  Staff are emailed about each new claim (the site admin email) and review it in **wp-admin > Claims**, which shows
  a count while claims are waiting.
  - **Approve:** the business becomes claimed on Basic, the account manages it, and they get an email.
  - **Reject:** they get an email with the note, and they can claim again.

- **Adding a business** (`/hub/add-business/`, businesses module, `app/setup/add-business.php`) needs a signed-in
  account with a confirmed email (3 a day). Owners add their own business; with `?recommend=1`, readers suggest
  one without becoming its owner (fewer fields). An owner can't add one while they manage a business or have a
  claim or added business waiting.
  - **What's checked:** the same name and postcode as a live or waiting business is refused, with a link to claim
    the live one.
  - **What's saved:** the business is created as **Pending Review** with the usual ACF fields (opening hours as
    "9am to 5:30pm"), categories and area, and the photo in the Media Library as its listing photo. Facilities
    are post meta `_hub_facilities` until the listing editor has a field for them.
  - **Emails:** the person gets a receipt and the site admin a "New business to check" email.
  - **Staff review** it in wp-admin > Businesses > Pending; a box shows who added it and how.
    - **Publish:** an owner gets it, claimed on the Basic plan and linked to their account.
    - **Move to Trash:** declines it.

    Both email the person who added it.
- **Offers, events and classes from accounts** (`app/modules/submissions/`, `app/setup/submissions.php`): every
  "Add an offer / event / class" button opens `/hub/account/{offers,events,classes}/edit/`. **Send for approval**
  saves it as **Pending Review**; **Save draft** saves a Draft. Who can add what follows the Pricing page:
  - **Offers:** Featured and Local Partner businesses.
  - **Classes:** Featured and Local Partner businesses, and organisers (free).
  - **Events:** the same, plus a Basic business can add one for £15. It's saved as Pending Review and marked unpaid
    until the Stripe payment's webhook marks it paid; staff see "not paid yet" on it.

  Owners can change their drafts and pending items (`?id=`). Live ones are locked: they email us. New items get the
  same records as the imported ones (`_hub`), their business link, and a photo for events and classes. Staff review
  them in wp-admin > Offers / Events / Classes > Pending, with a "Sent from the website" box.
  - **Publish:** puts it live.
  - **Move to Trash:** declines it.

  Both email the person, unless they turned those emails off in Settings.

  The account list pages (`account/offers/` and so on) still show the prototype's sample items.
- **Settings** (`/hub/account/settings/`, `parts/account/settings.php`): name; email, which changes only when a
  link sent to the new address is clicked (the login changes with it); password, which needs the current one,
  keeps this device signed in and gets WordPress's "Password changed" email; email choices; newsletter editions,
  saved on the account for the newsletter module's Beehiiv sync; and closing the account. Closing needs the
  password and a tick box, and is blocked while a paid plan is active. It deletes the account and puts the business
  back to unclaimed: its details stay on the Hub, and its content is hidden, not deleted.
- **Sign-out** (`/hub/sign-out/`) needs the token in the account menu's link (`hub_view_sign_out_url()`). Without
  it, for example from an old link or another site, the dashboard asks "Sign out?" instead.
- **Test accounts:** `php wp-local/hub/seed-test-accounts-local.php` makes one account of each kind, each with its
  own data. The passwords go into `hub/LOCAL-ADMIN.txt`. Running it again starts them afresh; it works locally only.
  - `owner@harrowonline.test` is Featured (AJ Pizza's), with live, pending and draft offers, an event and a class.
  - `partner@` is Local Partner (Stanmore Family Dental).
  - `basic@` is Basic (Pinner Village Florist), with a £15 event not paid yet.
  - `organiser@` is an organiser (Harrow Chess Club), with an event and classes.
  - `newcomer@` has no business yet.
  - `claimer@` has a claim waiting in wp-admin > Claims.
- **Passwords:** "Forgotten your password?" uses WordPress's own reset. The email (sent by WordPress, with Hub wording)
  links to `/hub/forgot-password/?key=...`, where people choose a new password and are signed in. Links work once
  and last an hour. WordPress's own `wp-login.php` register, lost-password and reset screens redirect to the Hub
  pages; its plain sign-in form stays for staff. Each new sign-up sends WordPress's "New User Registration" email
  to the site admin.
- **Confirming email:** registering sends a "Confirm your email" link that works for 7 days, and new accounts
  can sign in straight away. The dashboard asks them to confirm and has a **Resend the email** button (3 an hour).
  Paying for a plan or an item needs a confirmed email; resetting the password also confirms it. Accounts made
  before this count as confirmed (user meta `hub_email_confirmed`).
- **Attempt limits** (`HUB_ACCOUNT_LIMITS` in `app/modules/account/rules.php`), for the Hub's forms and
  `wp-login.php` alike:
  - 5 wrong passwords per email and IP, or 30 per IP, then 15 minutes' wait. A right password doesn't get in during
    the wait, and signing in successfully resets the count.
  - 3 reset emails per address and 10 reset requests per IP an hour.
  - 5 new accounts per IP an hour.

  If you lock yourself out locally, delete the `hub_lim_` transients or wait. Behind a proxy such as Cloudflare,
  the IP must come from the proxy's header (`hub_account_db_ip()`).
- **Sending email** (`wp-content/mu-plugins/hub-mail.php`): mail comes from "Harrow Online
  <hub@harrowonline.org>". On a server, set an SMTP service in Forge > Site > Environment: `SMTP_HOST`,
  `SMTP_PORT` (587), `SMTP_SECURE` (`tls`), `SMTP_USER`, `SMTP_PASS`, and optionally `MAIL_FROM` and
  `MAIL_FROM_NAME`. Postmark, Brevo and Amazon SES all work. The service must verify the sending domain
  (SPF and DKIM DNS records on harrowonline.org), or mail lands in spam. Failed sends go to the PHP error log.
- **Emails locally:** nothing is sent. `wp-content/mu-plugins/hub-local-mail.php` saves each email as a text file in
  `hub/wp-content/mail-log/` (gitignored, and blocked in nginx), only when `WP_ENVIRONMENT_TYPE` is `local` and no
  `SMTP_HOST` is set.
- **Payments** (`app/modules/billing/`, `app/setup/billing.php`) use Stripe Checkout and the Stripe customer
  portal through the official `stripe/stripe-php` library (installed in the theme's `vendor/` by Composer; commit
  it). Plans (Featured, Local Partner; monthly or yearly) and one-off items (event listing, 7-day feature,
  newsletter adverts) are listed in `app/modules/billing/rules.php`; their **prices are set in wp-admin > Hub settings >
  Prices** (prices include VAT). Saving there sends them to Stripe: a changed amount becomes a new Stripe price that
  new sign-ups and purchases pay, and businesses already on a plan keep theirs. The amounts in `rules.php` are only
  the defaults for a site where nobody has saved prices yet.
- **Keys** go in the `.env` file in the repository root (never committed): `STRIPE_SECRET_KEY`,
  `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`. Use **test** keys (`sk_test_`, `pk_test_`) everywhere except
  live. Without real keys, the payment buttons go to the account billing page with "Online payments aren't
  switched on yet", and nothing calls Stripe.
- **First time with a Stripe account:** `php wp-local/hub/stripe-setup-local.php` creates the products and prices
  in Stripe (looked up by `hub_{plan}_{monthly|annual}` and `hub_{item}_once`) and the Hub's own customer portal
  settings: switch between Featured and Local Partner (monthly or yearly), update the card, see invoices, cancel at
  the end of the period with a reason. Running it again is safe (it updates them). Test and live mode each need it
  once: they are separate in Stripe.
- **Webhook:** Stripe tells the Hub about payments at `/hub/wp-json/hub/v1/billing/webhook`. The Hub uses these
  events to set the business's plan, and it never sets a plan from the browser. The events:
  `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`,
  `customer.subscription.deleted`, `invoice.paid`, `invoice.payment_failed` and `charge.refunded`.
  Locally (Stripe CLI 1.55 and later need the events listed):
  `stripe listen --events checkout.session.completed,customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,invoice.paid,invoice.payment_failed,charge.refunded --forward-to localhost:8020/hub/wp-json/hub/v1/billing/webhook`,
  with `STRIPE_API_KEY` set to the test secret key, then put the `whsec_` value it prints in `.env`.
- **Payments log:** wp-admin > Payments lists every payment and invoice the webhook has received (read only).
  A refund made in Stripe marks its payment "Refunded" or "Part refunded"; it doesn't change the plan (cancel in
  the portal for that), and a refunded £15 event or featured spot stays as it is until staff change it.
- **Launch switches** (`app/modules/billing/rules.php`): `HUB_BILLING_PLAN_CHANGES` is off for launch, so a
  business can buy its first plan online but not switch plan or billing period (staff do that on request; the
  portal still updates cards, shows invoices and cancels). `HUB_BILLING_NEWSLETTER_ADVERTS` is off, so
  `/advertise/newsletter/` redirects to harrowonline.org/advertise and adverts can't be bought online. To turn either
  back on: set it to `true`, deploy, and run the Stripe setup once in each Stripe mode (it updates the portal).
- **Billing page:** the account's card and last 12 invoices (with Stripe's PDFs) come from Stripe, kept for 10
  minutes and refreshed when a webhook arrives for the customer.
- **Billing emails:** the Hub emails the account holder when a renewal payment fails (first failure only; Stripe
  retries for a few days, and the plan stays on meanwhile) and when a plan ends. For receipts, turn on Stripe's own emails (Settings > Customer emails > Successful payments), and leave its failed-payment emails off to avoid sending two.
- **Test the webhook without Stripe:** `php wp-local/hub/billing-webhook-test-local.php` signs sample events and
  checks subscribe, plan switch, failed and retried payments, cancel, plan end, one-off items and the emails, on a
  temporary business it deletes afterwards. It needs no keys and makes no network calls.
- **Stripe on a server (dev, staging or live), once per server:**
  1. **Keys.** In Forge, Site > Environment (the server's `.env`): `STRIPE_SECRET_KEY` and `STRIPE_PUBLISHABLE_KEY`
     from Stripe's Developers > API keys. Test keys (`sk_test_`, `pk_test_`) on dev and staging; live keys only on
     the live site. Never put keys in `wp-config.php` or any committed file.
  2. **Products, prices and portal.** On the server, in the site folder:
     `php wp-local/hub/stripe-setup-local.php` (the site's PHP version). It should end with "billing portal:
     created" (or "updated"). The dev server's database is replaced on each deploy; the Hub finds its portal
     settings in Stripe again by itself, so this is needed once per Stripe mode, not after every deploy.
  3. **Webhook endpoint.** Stripe dashboard (in the same mode as the keys) > Developers > Webhooks > Add endpoint:
     `https://{site}/hub/wp-json/hub/v1/billing/webhook`, with the seven events listed above. Copy its signing
     secret into the server's `.env` as `STRIPE_WEBHOOK_SECRET`.
  4. **Check.** Stripe dashboard > the endpoint > Send test webhook (`invoice.paid`): it should answer 200. A 400
     means the signing secret doesn't match. Then buy a plan with the test card 4242 4242 4242 4242 and see the
     plan change on the account's billing page and in wp-admin > Payments.
  5. **Dashboard settings** (live especially): Billing > Revenue recovery: after the last retry, **cancel the
     subscription** (the Hub's failed-payment email says the listing moves to Basic); Settings > Customer emails:
     successful payments on, failed payments off (the Hub sends its own); Settings > Branding: logo, colour #DF2028,
     support email hub@harrowonline.org; Settings > Business details: address (and VAT number, if registered) for
     invoices.
- **Dev server caveat:** each deploy replaces the server's database with the committed one, so accounts, plans and
  payments made on the dev server are lost on the next push. Do payment testing locally until staging has its
  own database.

## Test site (SiteGround)

https://hub.harrowonline.org/hub/ is the launch test site, on SiteGround (the same account as harrowonline.org: only
`~/www/hub.harrowonline.org/` belongs to the Hub).

- **Deploys:** every push to `main` that changes `wp-local/hub/` runs `.github/workflows/deploy-siteground.yml`,
  which copies the **code only** over SSH (rsync). The site's own SQLite database, uploads, mail log, private claim
  files, debug log and `.htaccess` are never overwritten or deleted, so accounts and test data made on the site
  survive deploys. `LOCAL-ADMIN.txt` is never uploaded. Run it by hand from GitHub > Actions > "Deploy to SiteGround
  test site" > Run workflow.
- **Secrets** (GitHub > Settings > Secrets and variables > Actions): `SITEGROUND_SSH_KEY` (its public half is the
  "github-actions-hub-deploy" key in Site Tools > Devs > SSH Keys Manager), `SITEGROUND_SSH_HOST`,
  `SITEGROUND_SSH_USER`, `SITEGROUND_SSH_PORT`, `SITEGROUND_KNOWN_HOSTS`.
- **Settings:** `~/www/hub.harrowonline.org/.env` (outside `public_html`), read by `wp-config.php` like the Forge
  site's. Access rules: `public_html/hub/.htaccess` (the nginx rules of `forge/nginx-hub.conf`, for Apache).
- **Database:** the deploy never changes it. To put the committed database there (losing the site's own accounts
  and test data), copy `wp-local/hub/wp-content/database/hub.sqlite` over SSH on purpose.
- **Cache:** SiteGround's Dynamic Cache only caches sites with its Speed Optimizer plugin, which the Hub doesn't
  have. If an old page ever shows, flush it in Site Tools > Speed > Caching > Dynamic Cache.

## Dev server (Laravel Forge)

> **Retired (20 Nov 2026):** the Forge dev site is no longer used; the test site is SiteGround (above). Its notes stay
> here for reference. To stop it deploying, turn off push-to-deploy (or delete the site) in Forge.

The same WordPress runs on Laravel Forge (server `appy-dev`, site `harrowwp.on-forge.com`):
https://harrowwp.on-forge.com/hub/. It deploys from `main` with the committed SQLite database.

- **Site:** repository `goappycodes/harrowonline`, branch `main`, root directory `/`, web directory `/wp-local`, so
  WordPress is at `/hub/` as here. Nginx rules (send `/` to `/hub/`, block the database, logs and the local CLI
  scripts (any new `*-local.php` script must be added there too), `noindex` header, extra HTTP password on wp-admin): `forge/nginx-hub.conf`, applied to the site's nginx
  config on the server.
- **Deploys:** push-to-deploy is on, so every push to `main` deploys. Forge's deploy script fetches the branch and runs
  `forge/deploy.sh` from git (reset to the commit, clear SQLite's write-ahead log, reload PHP).
- **Settings:** `wp-config.php` reads every server setting from the environment or the `.env` file in the
  repository root (Forge: Site > Environment, never committed): `WP_HOME` (with `/hub`; may list several
  comma-separated addresses, each request uses the one matching its host), the eight keys and salts, `WP_ENVIRONMENT_TYPE`, `WP_DEBUG`, `DISALLOW_FILE_MODS`, and the database (`DB_PATH`, or `DB_ENGINE=mysql` with
  `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`). With no `.env` the local values above apply.
- **Content comes from git.** Each deploy replaces the server's database with the committed `hub.sqlite`, so
  changes made in the server's wp-admin are lost on the next deploy. Change content locally and commit it.
- **Old SQLite on the server:** `appy-dev` runs Ubuntu 20.04 (SQLite 3.31; the plugin wants 3.37+), so its `.env` sets
  `WP_SQLITE_UNSAFE_ENABLE_UNSUPPORTED_VERSIONS=true`, the SQLite plugin's own back-compat mode for SQLite 3.27+.
- **Logins:** wp-admin and the login page first ask for an extra HTTP username and password (kept out of git:
  `.htpasswd-hub` in the site folder on the server, the plain values with the project's private credentials).
  Behind it, the WordPress users and passwords are the ones in the committed database, i.e. the local ones.
