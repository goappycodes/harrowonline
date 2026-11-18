"""
Compare the WordPress theme's pages (http://localhost:8020/hub/...) with the static prototype (site/hub/...):
status code, <title>, and the <main> content after normalising asset and link URLs.

    python tools/compare_theme.py
"""
import re
import sys
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))
import seo  # noqa: E402

WP = "http://localhost:8020/hub"
THEME_ASSETS = "/hub/wp-content/themes/harrow-hub/assets/"


def main_of(html):
    m = re.search(r"<main id=\"main\">(.*?)</main>", html, re.S)
    return m.group(1) if m else ""


def norm(html):
    html = html.replace("http://localhost:8020", "")
    html = html.replace(THEME_ASSETS, "/hub/assets/")
    html = html.replace("/hub/assets/img/icons.svg#i-", "#i-")
    html = re.sub(r"\?ver=[\w.]+", "", html)
    html = html.replace("&#038;", "&amp;")   # WordPress's esc_url() writes & as &#038; (same to a browser)
    # The prototype numbers unlock forms across the whole static build (unlock-…-6); WordPress per page (…-1)
    html = re.sub(r"(unlock-[\w-]+?-)\d+\b", r"\g<1>N", html)
    html = re.sub(r"\s+", " ", html)
    return html.strip()


def title_of(html):
    import html as h
    m = re.search(r"<title>(.*?)</title>", html, re.S)
    return h.unescape(re.sub(r"\s+", " ", m.group(1)).strip()).replace("’", "'") if m else ""


def fetch(url):
    try:
        with urllib.request.urlopen(url, timeout=60) as r:
            return r.status, r.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", "replace")


def main():
    bad = 0
    for stem, path in sorted(seo.PAGE_PATHS.items(), key=lambda kv: kv[1]):
        if stem.startswith("news-") or stem in ("event", "class"):
            continue
        static_file = ROOT / "site" / "hub" / (path.strip("/") + ("/index.html" if path.endswith("/") and path != "/" else "")) if path != "/" else ROOT / "site/hub/index.html"
        if path == "/404.html":
            static_file = ROOT / "site/hub/404.html"
            url = WP + "/this-page-does-not-exist/"
        else:
            url = WP + path
        status, html = fetch(url)
        static = static_file.read_text(encoding="utf-8")
        a, b = norm(main_of(static)), norm(main_of(html))
        same = a == b
        t_ok = title_of(static) == title_of(html)
        flag = "ok " if (same and status in (200, 404)) else "DIFF"
        if not same or not t_ok:
            bad += 1
            # first difference, for a quick look
            i = next((k for k in range(min(len(a), len(b))) if a[k] != b[k]), min(len(a), len(b)))
            print(f"{flag} {status} {path:32} main_same={same} title_same={t_ok}\n      static: ...{a[max(0, i-80):i+120]}\n      wp:     ...{b[max(0, i-80):i+120]}")
        else:
            print(f"{flag} {status} {path}")
    print(f"\n{bad} page(s) differ")


if __name__ == "__main__":
    main()
