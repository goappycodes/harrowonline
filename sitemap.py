"""
Hub XML sitemaps (brief 03, S3): indexable, canonical URLs only, split by type, with an index file.

    site/hub/sitemap_index.xml   → lists the files below
    site/hub/page-sitemap.xml    hand-written pages (home, offers, pricing, legal …)
    site/hub/business-sitemap.xml  indexable business profiles (claimed and filled in)
    site/hub/event-sitemap.xml   upcoming events and classes
    site/hub/category-sitemap.xml / area-sitemap.xml  only once an archive passes the index rule

Built by reading the finished pages, so a page is listed only if its own <meta name="robots"> allows indexing
and its canonical points at itself. The main robots.txt (news site) needs:
    Sitemap: https://harrowonline.org/hub/sitemap_index.xml
"""
import datetime as dt
import re
from collections import defaultdict
from xml.sax.saxutils import escape

import seo
from build import BASE, OUT

ROBOTS = re.compile(r'<meta name="robots" content="([^"]*)"')
CANON = re.compile(r'<link rel="canonical" href="([^"]*)"')


def kind(path):
    first = path.strip("/").split("/")[0]
    return {"business": "business", "category": "category", "area": "area", "events": "event"}.get(first, "page")


def from_disk():
    for f in sorted(OUT.rglob("index.html")):
        rel = f.relative_to(OUT)
        yield "/" + "/".join(rel.parts[:-1]) + ("/" if rel.parts[:-1] else ""), f.read_text(encoding="utf-8")


def collect(pages=None):
    """pages: [(path, head_html)] from build.write; None = read the built files."""
    groups = defaultdict(list)
    for path, html in sorted(pages if pages is not None else from_disk()):
        if path.startswith("/news-site/") or path.endswith(".html"):
            continue
        robots = (ROBOTS.search(html) or [None, ""])[1]
        canon = (CANON.search(html) or [None, ""])[1]
        if "noindex" in robots or canon != seo.absolute(BASE, path):
            continue
        groups[kind(path)].append(canon)
    return groups


def urlset(urls, lastmod):
    rows = "".join(f"<url><loc>{escape(u)}</loc><lastmod>{lastmod}</lastmod></url>\n" for u in urls)
    return ('<?xml version="1.0" encoding="UTF-8"?>\n'
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + rows + "</urlset>\n")


def write_all(pages=None, today=None):
    lastmod = (today or dt.date.today()).isoformat()
    groups = collect(pages)
    for old in OUT.glob("*sitemap*.xml"):
        old.unlink()
    index_rows = []
    for name in ("page", "business", "event", "category", "area"):
        if not groups.get(name):
            continue
        (OUT / f"{name}-sitemap.xml").write_text(urlset(groups[name], lastmod), encoding="utf-8", newline="\n")
        index_rows.append(f"<sitemap><loc>{seo.absolute(BASE, f'/{name}-sitemap.xml')}</loc><lastmod>{lastmod}</lastmod></sitemap>\n")
    (OUT / "sitemap_index.xml").write_text(
        '<?xml version="1.0" encoding="UTF-8"?>\n<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
        + "".join(index_rows) + "</sitemapindex>\n", encoding="utf-8", newline="\n")
    print("Sitemaps: " + ", ".join(f"{k} {len(v)}" for k, v in groups.items()))
