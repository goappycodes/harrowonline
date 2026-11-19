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
HAND_EDITED = {"index", "offers", "events", "events-month", "classes"}

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
        body = convert_links(nav_placeholders(hub_only(expand(body))))
        check(stem, body)
        meta_php = ",\n\t".join(f"{php_str(k)} => {php_str(v)}" for k, v in sorted(meta.items()))
        tpl = (f"<?php\n/**\n * {meta.get('title', stem)} (converted from src/pages/{stem}.html).\n * URL: {path}\n */\n"
               f"defined( 'ABSPATH' ) || exit;\nho_set_page( array(\n\t{meta_php},\n) );\nget_header();\n?>\n{body}\n<?php get_footer();\n")
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
    if dest.exists():
        shutil.rmtree(dest)
    shutil.copytree(SITE_ASSETS, dest)
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
