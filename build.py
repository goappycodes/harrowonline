"""
Harrow Online Hub: static front-end build.

Assembles src/pages/*.html with the shared partials (head, header, footer, newsletter strip, icon sprite)
into plain HTML files in site/hub/, one folder per production URL (site/hub/events/index.html = /hub/events/).
SEO (canonical, social tags, JSON-LD, sitemaps, index rules) lives in seo.py and sitemap.py. The output is what you open in a browser or hand over for the WordPress theme.

    python build.py            # build once
    python build.py --watch    # rebuild when anything in src/ changes

Page front matter (first HTML comment in each page file), one "key: value" per line:
    title        <title> text (" | Harrow Online Hub" is appended unless it already contains "Harrow Online")
    description  meta description
    nav          which main-menu item is current: news | events | classes | offers | hub | newsletter
    hubnav       which Hub sub-menu item is current: directory | offers | events | classes | pricing | account
    robots       e.g. "noindex, follow" (mirrors the SEO brief 03 rules); empty = indexable
    schema       schema.org WebPage type (default from seo.PAGE_SCHEMA, else WebPage)
    body         extra <body> classes
    css          extra stylesheet name in site/assets/css/ (e.g. "hub-a"); merged into hub.css before hand-off
    layout       default | bare (bare = no Hub bar, no newsletter strip; used by auth pages)
    site         hub | news (news = page belongs to harrowonline.org; hides the Hub bar and uses the news header CTA)
    nl           off = hide the newsletter strip

Inside page bodies:
    {{> name}}   include src/partials/name.html
"""
import re
import sys
import time
from pathlib import Path

import seo

ROOT = Path(__file__).parent
SRC = ROOT / "src"
PARTIALS = SRC / "partials"
PAGES = SRC / "pages"
# Base URL path of the Hub. Change it here only (brief 01: never hard-code "hub" in templates or links).
# Page sources use root-absolute links ("/search.html"); the build prefixes them with BASE.
BASE = "/hub"
WEB = ROOT / "site"                                # web root (what Vercel serves)
OUT = WEB / BASE.strip("/") if BASE else WEB       # where the Hub lives inside it

FM_RE = re.compile(r"\A\s*<!--(.*?)-->", re.S)
INC_RE = re.compile(r"\{\{>\s*([\w-]+)\s*\}\}")


def partial(name, depth=0):
    if depth > 5:
        raise RuntimeError(f"Include loop at {name}")
    text = (PARTIALS / f"{name}.html").read_text(encoding="utf-8")
    return INC_RE.sub(lambda m: partial(m.group(1), depth + 1), text)


def parse(page_text):
    meta = {}
    m = FM_RE.match(page_text)
    if m:
        for line in m.group(1).strip().splitlines():
            if ":" in line:
                k, v = line.split(":", 1)
                meta[k.strip()] = v.strip()
        page_text = page_text[m.end():]
    return meta, page_text.strip("\n")


def render(meta, body):
    """meta needs "path" (the page's URL under BASE, e.g. "/events/"); see seo.PAGE_PATHS."""
    title = meta.get("title", "Harrow Online Hub")
    if "Harrow Online" not in title:
        title += seo.TITLE_SUFFIX
    site = meta.get("site", "hub")
    layout = meta.get("layout", "default")

    body = INC_RE.sub(lambda m: partial(m.group(1)), body)
    if meta.get("hubnav") == "account":
        # Preview only: the signed-in account, its bookings, statuses, invoices and figures are made up.
        body = ('<div class="ho-container mt-4"><div class="notice notice--sample"><svg class="ho-icon"><use href="#i-alert"/></svg>'
                '<p><strong>Sample account.</strong> The business, bookings, statuses, invoices and figures on this page are made up '
                'to show how the dashboard works. None of it is real, and it will be removed before launch.</p></div></div>\n') + body

    parts = [partial("head"), partial("header")]
    if site == "hub" and layout != "bare":
        parts.append(partial("hubbar"))
    parts.append('<main id="main">\n' + body + "\n</main>")
    if layout != "bare" and meta.get("nl") != "off":
        parts.append(partial("nl-strip"))
    parts.append(partial("footer"))
    if site == "hub" and layout != "bare":
        parts.insert(-1, partial("tabbar"))   # phone-only bottom tab bar (CSS hides it from 768px)
    html = "\n".join(parts)

    css = meta.get("css", "")
    html = html.replace("</head>", (f'<link rel="stylesheet" href="/assets/css/{css}.css">\n' if css else "") + "</head>", 1)
    html = html.replace("{{title}}", seo.esc(title))
    html = html.replace("{{description}}", seo.esc(seo.trim(meta.get("description", f"Harrow Online Hub: local businesses, offers, events and classes in Harrow, {seo.STRAPLINE}."))))
    html = html.replace("{{seo}}", seo.head(meta, BASE, title))
    html = html.replace("{{body}}", " ".join(filter(None, ["site-" + site, meta.get("body", "")])))

    # Current menu items
    nav = meta.get("nav", "hub" if site == "hub" else "news")
    hubnav = meta.get("hubnav", "")
    html = re.sub(r"\{\{nav:([\w-]+)\}\}", lambda m: ' class="current-menu-item"' if m.group(1) == nav else "", html)
    html = re.sub(r"\{\{navcls:([\w-]+)\}\}", lambda m: " current-menu-item" if m.group(1) == nav else "", html)
    html = re.sub(r"\{\{navaria:([\w-]+)\}\}", lambda m: ' aria-current="page"' if m.group(1) == nav else "", html)
    html = re.sub(r"\{\{hubnav:([\w-]+)\}\}", lambda m: ' aria-current="page"' if m.group(1) == hubnav else "", html)
    tab = "home" if meta.get("path") == "/" else {"directory": "search", "offers": "offers", "events": "events", "account": "account"}.get(hubnav, "")
    html = re.sub(r"\{\{tab:([\w-]+)\}\}", lambda m: ' aria-current="page"' if m.group(1) == tab else "", html)
    html = html.replace('href="#i-', 'href="/assets/img/icons.svg#i-')
    html = re.sub(r"\{\{site:(\w+)\}\}(.*?)\{\{/site\}\}", lambda m: m.group(2) if m.group(1) == site else "", html, flags=re.S)

    # Production URLs for internal links, then one JSON-LD graph in <head> (news-site mock-ups: Yoast does this there)
    html = seo.rewrite_links(html)
    if site == "hub":
        html, ld = seo.graph(meta, html, BASE, title)
    else:
        ld = ""
    html = html.replace("{{jsonld}}", ld)
    return with_base(html)


def out_file(path):
    """URL path under BASE → file in OUT ("/events/" → events/index.html)."""
    rel = path.strip("/")
    return OUT / rel if rel.endswith(".html") else (OUT / rel / "index.html" if rel else OUT / "index.html")


def write(path, html):
    seo.WRITTEN.append((path, html[:html.find("</head>")]))   # sitemap.py reads this instead of 2,700 files
    f = out_file(path)
    f.parent.mkdir(parents=True, exist_ok=True)
    f.write_text(html, encoding="utf-8", newline="\n")


def with_base(html):
    """Prefix root-absolute URLs with BASE and expose it to hub.js as <html data-base>."""
    if not BASE:
        return html
    html = re.sub(r'((?:href|src|action)=")/(?!/)', r'\g<1>' + BASE + '/', html)
    return html.replace('<html lang="en-GB">', f'<html lang="en-GB" data-base="{BASE}">', 1)


def build(listings=True):
    """listings=False skips the 2,600 generated profile/category/area pages (fast rebuild while editing)."""
    import gen_listings
    sys.path.insert(0, str(ROOT / "tools"))
    import gen_events   # data/events.json → What's On list, month, homepage weekend cards, one page per event
    gen_events.write_sources()
    OUT.mkdir(parents=True, exist_ok=True)
    if listings:
        clean_html()
        gen_listings.build_all()
    else:
        gen_listings.write_partials_only()
    count = 0
    for page in sorted(PAGES.glob("*.html")):
        meta, body = parse(page.read_text(encoding="utf-8"))
        stem = page.stem
        meta["path"] = seo.clean_path(stem)
        meta.setdefault("schema", seo.PAGE_SCHEMA.get(stem, "WebPage"))
        if stem in seo.NEWS_CANONICAL:
            meta["canonical"] = seo.NEWS_CANONICAL[stem]
        write(meta["path"], render(meta, body))
        count += 1
    for meta, body in gen_events.event_pages():
        write(meta["path"], render(meta, body))
        count += 1
    import sitemap
    sitemap.write_all(seo.WRITTEN if listings else None)
    write_vercel()
    print(f"Built {count} pages into {OUT}")


def write_vercel():
    """Keep vercel.json in step with seo.PAGE_PATHS: old *.html preview links 301 to the clean URLs."""
    import json
    cfg_file = ROOT / "vercel.json"
    cfg = json.loads(cfg_file.read_text(encoding="utf-8"))
    cfg["trailingSlash"] = True
    redirects = [{"source": "/", "destination": f"{BASE}/", "permanent": False}]
    for stem, path in seo.PAGE_PATHS.items():
        if stem not in ("404", "index"):
            redirects.append({"source": f"{BASE}/{stem}.html", "destination": BASE + path, "permanent": True})
    redirects.append({"source": f"{BASE}/:kind(business|category|area)/:slug.html", "destination": f"{BASE}/:kind/:slug/", "permanent": True})
    cfg["redirects"] = redirects
    cfg_file.write_text(json.dumps(cfg, indent=2) + "\n", encoding="utf-8", newline="\n")


def clean_html():
    """Remove previously built pages (everything .html outside assets/) so renamed URLs leave no stale copies."""
    for f in OUT.rglob("*.html"):
        if "assets" not in f.relative_to(OUT).parts:
            f.unlink()
    for d in sorted((p for p in OUT.rglob("*") if p.is_dir()), key=lambda p: -len(p.parts)):
        if "assets" not in d.relative_to(OUT).parts and not any(d.iterdir()):
            d.rmdir()


def snapshot():
    return {p: p.stat().st_mtime for p in SRC.rglob("*.html") if not p.name.startswith("gen-")}


if __name__ == "__main__":
    build(listings="--pages-only" not in sys.argv and "--watch" not in sys.argv)
    if "--watch" in sys.argv:
        seen = snapshot()
        while True:
            time.sleep(1)
            now = snapshot()
            if now != seen:
                seen = now
                try:
                    build(listings=False)
                except Exception as exc:  # keep watching after a typo
                    print("Build failed:", exc)
