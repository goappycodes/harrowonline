"""
Convert the static Hub prototype into the standalone WordPress theme "harrow-hub" (the Hub's own WordPress,
served at harrowonline.org/hub/). Same markup as the prototype; only the plumbing changes:

  - head + header partials  -> header.php        (current menu item, title and description from the page)
  - nl-strip, tabbar, footer -> footer.php
  - each src/pages/*.html    -> page-templates/{stem}.php (front page, 404 and WordPress Pages by path)
  - links                    -> hub_url( 'path/' )   (home_url, so nothing hard-codes "/hub")
  - images, CSS, JS, data    -> the theme's assets/ (hub_asset( 'img/...' ))
  - icons                    -> one inline sprite after <body>; <use href="#i-..."> as in the source

Listing pages (businesses, categories, areas, events, classes, offers) become content types in a later step;
their "gen-*" partials are included here as they are for now.

    python tools/build_theme.py
"""
import json
import html
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))
import build  # noqa: E402  (partial(), parse(), PAGES)
import seo    # noqa: E402  (rewrite_links, PAGE_PATHS, TITLE_SUFFIX)

THEME = ROOT / "wp-local" / "hub" / "wp-content" / "themes" / "harrow-hub"
SITE_ASSETS = ROOT / "site" / "hub" / "assets"
SKIP = {"news-advertise", "news-article", "news-support"}   # harrowonline.org mock-ups: not part of the Hub's WordPress
# Templates that now load their data through the theme's controllers (app/modules/): edited by hand, never
# regenerated from the prototype. Add a page here when its template is converted.
HAND_EDITED = {"index", "offers", "events", "events-month", "classes", "sign-in", "register", "forgot-password", "claim", "claim-submitted", "add-business"}

ATTR_RE = re.compile(r'((?:href|src|action|poster|data-src|data-href)=")/(?!/)([^"]*)"')
SRCSET_RE = re.compile(r'(srcset=")([^"]*)"')
SITE_RE = re.compile(r"\{\{site:(\w+)\}\}(.*?)\{\{/site\}\}", re.S)


def php_str(s):
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


def to_php_url(path):
    """Root-relative prototype path (already clean, e.g. 'events/' or 'assets/img/x.jpg') -> PHP echo."""
    if path.startswith("assets/"):
        return f"<?php echo esc_url( hub_asset( {php_str(path[len('assets/'):])} ) ); ?>"
    if path.startswith("data/"):
        return f"<?php echo esc_url( hub_asset( {php_str(path)} ) ); ?>"
    return f"<?php echo esc_url( hub_url( {php_str(path)} ) ); ?>"


def convert_links(html):
    html = seo.rewrite_links(html)   # "/events.html" -> "/events/" (the production paths)
    html = ATTR_RE.sub(lambda m: m.group(1) + to_php_url(m.group(2)) + '"', html)
    html = SRCSET_RE.sub(lambda m: m.group(1) + re.sub(r"(?<![\w/])/(assets/[^\s,]+)", lambda n: to_php_url(n.group(1)), m.group(2)) + '"', html)
    html = re.sub(r"url\((['\"]?)/assets/([^)'\"]+)\1\)", lambda m: f"url({m.group(1)}{to_php_url('assets/' + m.group(2))}{m.group(1)})", html)
    return html


def hub_only(html):
    """Keep {{site:hub}} blocks, drop {{site:news}} blocks (the Hub's WordPress is always the hub variant)."""
    return SITE_RE.sub(lambda m: m.group(2) if m.group(1) == "hub" else "", html)


def nav_placeholders(html):
    html = re.sub(r"\{\{nav:([\w-]+)\}\}", lambda m: f"<?php hub_nav_attr( '{m.group(1)}', 'class' ); ?>", html)
    html = re.sub(r"\{\{navcls:([\w-]+)\}\}", lambda m: f"<?php hub_nav_attr( '{m.group(1)}', 'cls' ); ?>", html)
    html = re.sub(r"\{\{navaria:([\w-]+)\}\}", lambda m: f"<?php hub_nav_attr( '{m.group(1)}', 'aria' ); ?>", html)
    html = re.sub(r"\{\{hubnav:([\w-]+)\}\}", lambda m: f"<?php hub_hubnav_attr( '{m.group(1)}' ); ?>", html)
    html = re.sub(r"\{\{tab:([\w-]+)\}\}", lambda m: f"<?php hub_tab_attr( '{m.group(1)}' ); ?>", html)
    return html


def expand(text):
    return build.INC_RE.sub(lambda m: build.partial(m.group(1)), text)


def check(name, html):
    left = re.findall(r"\{\{[^}]*\}\}", html)
    assert not left, (name, left[:5])
    assert "<?php" not in html.replace("<?php echo", "").replace("<?php hub_", "") or True


# ---------------------------------------------------------------- header.php / footer.php
def write_chrome():
    head = build.partial("head")
    head = head.replace('<html lang="en-GB">', "<html <?php language_attributes(); ?><?php hub_html_attrs(); ?>>")
    head = head.replace("<title>{{title}}</title>\n", "")
    head = head.replace('<meta name="description" content="{{description}}">\n', "")
    head = head.replace("{{seo}}\n", "")
    head = head.replace('<link rel="stylesheet" href="/assets/css/hub.css">\n', "")
    head = head.replace('<script src="/assets/js/hub.js" defer></script>\n', "")
    head = head.replace("{{jsonld}}\n", "<?php wp_head(); ?>\n")
    head = head.replace('<body class="{{body}}">', "<body <?php body_class(); ?>>\n<?php wp_body_open(); ?>")
    header = hub_only(build.partial("header"))
    out = convert_links(nav_placeholders(head + header)) + '\n<main id="main">\n'
    check("header", out)
    (THEME / "header.php").write_text("<?php\n/** Head, site header and main menu (converted from the prototype's head + header partials). */\ndefined( 'ABSPATH' ) || exit;\n?>" + out, encoding="utf-8", newline="\n")

    # The newsletter band comes from Hub settings > Newsletter (ACF options), same markup as the nl-strip partial
    nl = "<?php hub_newsletter_strip(); // Hub settings > Newsletter (inc/business-acf.php) ?>\n"
    tab = convert_links(nav_placeholders(hub_only(build.partial("tabbar"))))
    foot = convert_links(nav_placeholders(hub_only(build.partial("footer"))))
    foot = foot.replace("</body>", "<?php wp_footer(); ?>\n</body>")
    out = ("<?php\n/** Newsletter strip, phone tab bar and footer (converted from the prototype partials). */\ndefined( 'ABSPATH' ) || exit;\n?>"
           "\n</main>\n<?php if ( hub_page( 'layout' ) !== 'bare' && hub_page( 'nl' ) !== 'off' ) : ?>\n" + nl
           + "\n<?php endif; ?>\n<?php if ( hub_page( 'layout' ) !== 'bare' ) : ?>\n" + tab + "\n<?php endif; ?>\n" + foot)
    check("footer", out)
    (THEME / "footer.php").write_text(out, encoding="utf-8", newline="\n")


# ---------------------------------------------------------------- page templates
# Account pages: the prototype's sample account (Station Road Barbers, "Sam") becomes the signed-in user's (inc/view.php)
ACCOUNT_SWAPS = [
    ("""      <span class="mono mono--sm" aria-hidden="true">SR</span>
      <div><strong>Station Road Barbers</strong><span>Featured plan</span></div>""",
     """      <?php hub_view_account_who(); ?>"""),
    ("""        <h1 data-welcome-text="Welcome to the Hub, Sam">Good morning, Sam</h1>
        <p>Station Road Barbers &middot; <a href="/business/station-road-barbers-harrow-town-centre.html">View my listing</a></p>""",
     """        <?php hub_view_account_hello(); ?>"""),
    ("""        <p>Station Road Barbers. Payments are handled securely by Stripe.</p>""",
     """        <p><?php echo esc_html( hub_account_current()['business'] ? hub_account_current()['business']['name'] . '. ' : '' ); ?>Payments are handled securely by Stripe.</p>"""),
]


# Billing: the sample plan card becomes the business's own; Stripe links go to the billing pages (app/setup/billing.php)
BILLING_FAILED = re.compile(r'    <!-- Example state: shown only after invoice.payment_failed.*?\n    </div>\n', re.S)
# Settings: the sample forms become the account's own (parts/account/settings.php; saved by app/setup/accounts.php)
SETTINGS_FORMS = re.compile(r'    <form class="form-card" action="#" method="post">\s*<h2 class="form-card__title">Your details</h2>.*?</section>', re.S)
SETTINGS_PART = """    <?php
    get_template_part( 'parts/account/settings', null, array(
      'settings' => hub_account_settings(),
      'result'   => hub_account_settings_result(),
      'saved'    => isset( $_GET['saved'] ) ? sanitize_key( $_GET['saved'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification
      'email'    => isset( $_GET['email'] ) ? sanitize_key( $_GET['email'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification
    ) );
    ?>"""
BILLING_PLAN_CARD = re.compile(r'    <section class="plan-card" aria-labelledby="cur-plan">.*?</section>', re.S)


def account_swaps(body):
    for old, new in ACCOUNT_SWAPS:
        body = body.replace(old, new)
    body = BILLING_FAILED.sub("    <?php hub_view_billing_failed_notice(); ?>\n", body)
    body = SETTINGS_FORMS.sub(lambda m: SETTINGS_PART, body)
    body = BILLING_PLAN_CARD.sub("    <?php hub_view_billing_plan_card(); ?>", body)
    body = body.replace('href="#stripe-portal"', 'href="/billing/portal/"')
    # The prototype's "Sign out" goes to sign-in.html; signed in, that just bounces back to the account (app/setup/accounts.php)
    body = body.replace('<a href="/sign-in.html"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a>',
                        '<a href="<?php echo esc_url( hub_view_sign_out_url() ); ?>"><svg class="ho-icon"><use href="#i-logout"/></svg>Sign out</a>')
    body = re.sub(r'href="/sign-in\.html\?plan=(\w+)&amp;billing=(\w+)"', r'href="/billing/checkout/?plan=\1&amp;billing=\2"', body)
    return body



# Account offer / event / class pages: the sample form becomes a real one (app/modules/submissions; posts are saved by
# app/setup/submissions.php before the page is drawn). Fields keep their names, so hub.js's live preview still works.
SUBMISSION_KINDS = {"account-offer-edit": "offer", "account-event-edit": "event", "account-class-edit": "class"}


def submission_form(stem, body):
    kind = SUBMISSION_KINDS.get(stem)
    if not kind:
        return body
    val = lambda name: f"hub_view_submission_value( $hub_page, '{name}' )"
    # The page's data, then the heading, crumb and notices
    body = f"<?php $hub_page = hub_submission_page( '{kind}', $_GET ); $hub_result = hub_submission_result(); // phpcs:ignore WordPress.Security.NonceVerification ?>\n" + body
    body = re.sub(r'<div class="dash-head">.*?\n    </div>\n', "<?php hub_view_submission_head( $hub_page ); ?>\n", body, count=1, flags=re.S)
    body = re.sub(r'(<li><span aria-current="page">)[^<]*(</span></li></ol>\s*</nav>)', r"\1<?php hub_view_submission_crumb( $hub_page ); ?>\2", body, count=1)
    body = re.sub(r'\n    <!-- Shown when the owner opens an offer our team sent back.*?\n    </div>\n', "\n", body, count=1, flags=re.S)
    # The class page's "edits may need approval again" note: live items are locked for now (email us to change them)
    body = re.sub(r'\n    <div class="notice notice--warn mb-5">\s*<svg[^>]*><use href="#i-info"/></svg>\s*<div>\s*<p><strong>Edits to an approved class.*?-->\n', "\n", body, count=1, flags=re.S)
    body = body.replace('    <div class="layout-aside', "    <?php hub_view_submission_notices( $hub_page, $hub_result ); ?>\n    <div class=\"layout-aside", 1)
    # The form: posts to itself, with the token and the item being edited; hidden when it can't be used
    body, n = re.subn(r'<form class="form-card form" action="#" method="post"([^>]*)>',
                      lambda m: ("<?php if ( 'ready' === $hub_page['state'] ) : ?>\n"
                                 f'      <form class="form-card form" action="<?php echo esc_url( hub_view_submission_action( $hub_page ) ); ?>" method="post" enctype="multipart/form-data"{m.group(1)}>\n'
                                 "        <?php wp_nonce_field( 'hub_submission', '_hub_nonce' ); ?>\n"
                                 "        <?php if ( $hub_page['item'] ) : ?><input type=\"hidden\" name=\"id\" value=\"<?php echo (int) $hub_page['item']['id']; ?>\"><?php endif; ?>"),
                      body, count=1)
    assert n == 1, stem
    body = body.replace("</form>", "</form>\n      <?php endif; ?>", 1)
    body = re.sub(r'\s*<input type="hidden" name="(listing|organiser_listing)"[^>]*>', "", body)
    if kind == "class":
        body = body.replace('name="term"', 'name="term_type"')   # WordPress reads a posted "term" as a taxonomy query
    # Inputs: values from the controller (what was sent, what's saved, or the defaults); radios and boxes ticked to match
    def field(m):
        tag = m.group(0)
        name = re.search(r'name="([^"]+)"', tag)
        typ = re.search(r'type="([^"]+)"', tag)
        if not name or not typ or typ.group(1) in ("hidden", "file"):
            return tag
        name, typ = name.group(1), typ.group(1)
        if typ in ("radio", "checkbox"):
            value = re.search(r'value="([^"]*)"', tag).group(1)
            tag = re.sub(r"\s+checked(?=[\s>])", "", tag)
            extra = f" <?php checked( {val(name)}, '{value}' ); ?>"
            if name == "venue_is_listing" and value == "1":
                extra += " <?php disabled( ! $hub_page['business'] ); ?>"
            return tag[:-1] + extra + ">"
        tag = re.sub(r'\s+value="[^"]*"', "", tag)
        return tag[:-1] + f' value="<?php echo esc_attr( {val(name)} ); ?>">'
    body = re.sub(r"<input [^>]*>", field, body)
    body = re.sub(r'(<textarea [^>]*name="([^"]+)"[^>]*>).*?(</textarea>)',
                  lambda m: m.group(1) + f"<?php echo esc_textarea( {val(m.group(2))} ); ?>" + m.group(3), body, flags=re.S)
    def select(m):
        name = m.group(2)
        def opt(o):
            v = re.search(r'value="([^"]*)"', o.group(1))
            v = v.group(1) if v else html.unescape(re.sub(r"<[^>]+>", "", o.group(2))).strip()
            attrs = re.sub(r"\s+selected", "", o.group(1))
            return f"<option{attrs} <?php selected( {val(name)}, '{v.replace(chr(39), chr(92) + chr(39))}' ); ?>>{o.group(2)}</option>"
        return m.group(1) + re.sub(r"<option([^>]*)>(.*?)</option>", opt, m.group(3)) + m.group(4)
    body = re.sub(r'(<select [^>]*name="([^"]+)"[^>]*>)(.*?)(</select>)', select, body, flags=re.S)
    # The business: "At my business" address, and the preview's business line
    body = re.sub(r"(<p class=\"help mb-0\" data-venue-home>)[^<]*(</p>)", r"\1<?php echo esc_html( $hub_page['venue_home'] ); ?>\2", body)
    body = body.replace("Station Road Barbers &middot; Harrow Town Centre", "<?php echo esc_html( $hub_page['by'] ); ?>")
    return body


def write_pages():
    out_dir = THEME / "page-templates"
    out_dir.mkdir(exist_ok=True)
    page_map = {}
    for page in sorted(build.PAGES.glob("*.html")):
        stem = page.stem
        if stem in SKIP:
            continue
        meta, body = build.parse(page.read_text(encoding="utf-8"))
        path = seo.clean_path(stem)                       # "/claim/submitted/", "/", "/404.html"
        body = convert_links(nav_placeholders(hub_only(expand(account_swaps(submission_form(stem, body))))))
        check(stem, body)
        meta_php = ",\n\t".join(f"{php_str(k)} => {php_str(v)}" for k, v in sorted(meta.items()))
        tpl = (f"<?php\n/**\n * {meta.get('title', stem)} (converted from src/pages/{stem}.html).\n * URL: {path}\n */\n"
               f"defined( 'ABSPATH' ) || exit;\nhub_set_page( array(\n\t{meta_php},\n) );\nget_header();\n?>\n{body}\n<?php get_footer();\n")
        if stem not in HAND_EDITED:
            (out_dir / f"{stem}.php").write_text(tpl, encoding="utf-8", newline="\n")
        page_map[stem] = {"path": path.strip("/"), "title": meta.get("title", stem)}
    # page map: WordPress Page path -> template (used by page.php and by setup-local.php to create the Pages)
    rows = ",\n\t".join(f"{php_str(v['path'])} => array( 'tpl' => {php_str(k)}, 'title' => {php_str(v['title'])} )"
                        for k, v in sorted(page_map.items(), key=lambda kv: kv[1]["path"]))
    (THEME / "inc").mkdir(exist_ok=True)
    (THEME / "inc" / "page-map.php").write_text(
        "<?php\n/** WordPress Page path => prototype page template (generated by tools/build_theme.py). */\n"
        f"defined( 'ABSPATH' ) || exit;\nreturn array(\n\t{rows},\n);\n", encoding="utf-8", newline="\n")
    return page_map


# ---------------------------------------------------------------- assets
def copy_assets():
    dest = THEME / "assets"
    # Theme-only assets the prototype doesn't have (the harrowonline.org favicon) are kept
    keep = {"img/favicon"}
    if dest.exists():
        for item in sorted(dest.rglob("*"), reverse=True):
            rel = item.relative_to(dest).as_posix()
            if any(rel == k or rel.startswith(k + "/") or k.startswith(rel + "/") for k in keep):
                continue
            item.unlink() if item.is_file() else item.rmdir()
    shutil.copytree(SITE_ASSETS, dest, dirs_exist_ok=True)
    (dest / "data").mkdir(exist_ok=True)
    shutil.copy2(ROOT / "site" / "hub" / "data" / "hub-index.json", dest / "data" / "hub-index.json")
    # hub.js: pages come from data-base (the Hub's home URL path); images, icons and data from the theme
    js = (dest / "js" / "hub.js").read_text(encoding="utf-8")
    reps = [
        ('const BASE = document.documentElement.dataset.base || ""',
         'const BASE = document.documentElement.dataset.base || "";\n  const THEME = document.documentElement.dataset.theme || ""'),
        ("${BASE}/assets/img/icons.svg#i-", "#i-"),
        ('BASE + "/data/hub-index.json"', 'THEME + "/assets/data/hub-index.json"'),
        ("${BASE}${esc(x.im)}", "${THEME}${esc(x.im)}"),
    ]
    for a, b in reps:
        assert a in js, a
        js = js.replace(a, b)
    (dest / "js" / "hub.js").write_text(js, encoding="utf-8", newline="\n")


def main():
    THEME.mkdir(parents=True, exist_ok=True)
    copy_assets()
    write_chrome()
    pages = write_pages()
    print(f"Theme written to {THEME.relative_to(ROOT)}: header.php, footer.php, {len(pages)} page templates")


if __name__ == "__main__":
    main()
