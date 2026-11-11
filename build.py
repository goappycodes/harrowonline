"""
Harrow Online Hub: static front-end build.

Assembles src/pages/*.html with the shared partials (head, header, footer, newsletter strip, icon sprite)
into plain HTML files in site/. The output is what you open in a browser or hand over for the WordPress theme.

    python build.py            # build once
    python build.py --watch    # rebuild when anything in src/ changes

Page front matter (first HTML comment in each page file), one "key: value" per line:
    title        <title> text (" | Harrow Online Hub" is appended unless it already contains "Harrow Online")
    description  meta description
    nav          which main-menu item is current: news | events | classes | offers | hub | newsletter
    hubnav       which Hub sub-menu item is current: directory | offers | events | classes | pricing | account
    robots       e.g. "noindex, follow" (mirrors the SEO brief 03 rules)
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
    title = meta.get("title", "Harrow Online Hub")
    if "Harrow Online" not in title:
        title += " | Harrow Online Hub"
    site = meta.get("site", "hub")
    layout = meta.get("layout", "default")

    body = INC_RE.sub(lambda m: partial(m.group(1)), body)

    parts = [partial("head"), partial("header")]
    if site == "hub" and layout != "bare":
        parts.append(partial("hubbar"))
    parts.append('<main id="main">\n' + body + "\n</main>")
    if layout != "bare" and meta.get("nl") != "off":
        parts.append(partial("nl-strip"))
    parts.append(partial("footer"))
    html = "\n".join(parts)

    robots = meta.get("robots", "")
    css = meta.get("css", "")
    html = html.replace("</head>", (f'<link rel="stylesheet" href="/assets/css/{css}.css">\n' if css else "") + "</head>", 1)
    html = html.replace("{{title}}", title)
    html = html.replace("{{description}}", meta.get("description", "Harrow Online Hub: local businesses, offers, events and classes in Harrow, powered by Harrow Online Newsletter."))
    html = html.replace("{{robots}}", f'<meta name="robots" content="{robots}">' if robots else "")
    html = html.replace("{{body}}", " ".join(filter(None, ["site-" + site, meta.get("body", "")])))

    # Current menu items
    nav = meta.get("nav", "hub" if site == "hub" else "news")
    hubnav = meta.get("hubnav", "")
    html = re.sub(r"\{\{nav:([\w-]+)\}\}", lambda m: ' class="current-menu-item"' if m.group(1) == nav else "", html)
    html = re.sub(r"\{\{navaria:([\w-]+)\}\}", lambda m: ' aria-current="page"' if m.group(1) == nav else "", html)
    html = re.sub(r"\{\{hubnav:([\w-]+)\}\}", lambda m: ' aria-current="page"' if m.group(1) == hubnav else "", html)
    html = html.replace('href="#i-', 'href="/assets/img/icons.svg#i-')
    html = re.sub(r"\{\{site:(\w+)\}\}(.*?)\{\{/site\}\}", lambda m: m.group(2) if m.group(1) == site else "", html, flags=re.S)
    return with_base(html)


def with_base(html):
    """Prefix root-absolute URLs with BASE and expose it to hub.js as <html data-base>."""
    if not BASE:
        return html
    html = re.sub(r'((?:href|src|action)=")/(?!/)', r'\g<1>' + BASE + '/', html)
    return html.replace('<html lang="en-GB">', f'<html lang="en-GB" data-base="{BASE}">', 1)


def build(listings=True):
    """listings=False skips the 2,600 generated profile/category/area pages (fast rebuild while editing)."""
    import gen_listings
    OUT.mkdir(parents=True, exist_ok=True)
    if listings:
        gen_listings.build_all()
    else:
        gen_listings.write_partials_only()
    count = 0
    for page in sorted(PAGES.glob("*.html")):
        meta, body = parse(page.read_text(encoding="utf-8"))
        (OUT / page.name).write_text(render(meta, body), encoding="utf-8", newline="\n")
        count += 1
    print(f"Built {count} pages into {OUT}")


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
