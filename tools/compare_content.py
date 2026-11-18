"""
Compare the WordPress content pages (businesses, categories, areas, offers, events, classes) with the static
prototype: status, <title> and <main>. Every category, area, offer, event, class and claimed business, plus a
spread of imported businesses.

    python tools/compare_content.py            (server on http://localhost:8020)
    python tools/compare_content.py business   (only paths containing "business")
"""
import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT / "tools"))
from compare_theme import fetch, main_of, norm, title_of  # noqa: E402

WP = "http://localhost:8020/hub"
data = json.loads((ROOT / "data/wp-hub-data.json").read_text(encoding="utf-8"))

paths = [f"/category/{c['slug']}/" for c in data["categories"]] + [f"/area/{a['slug']}/" for a in data["areas"]]
claimed = [b for b in data["businesses"] if b.get("claimed")]
imported = [b for b in data["businesses"] if not b.get("claimed")]
paths += [f"/business/{b['slug']}/" for b in claimed + imported[::65]]
paths += [f"/offers/{o['slug']}/" for o in data["offers"]]
paths += [f"/events/{e['slug']}/" for e in data["events"]] + [f"/events/{c['slug']}/" for c in data["classes"]]
only = sys.argv[1] if len(sys.argv) > 1 else ""
paths = [p for p in paths if only in p]

bad = 0
for p in paths:
    static_file = ROOT / "site/hub" / p.strip("/") / "index.html"
    status, html = fetch(WP + p)
    static = static_file.read_text(encoding="utf-8")
    a, b = norm(main_of(static)), norm(main_of(html))
    t_ok = title_of(static) == title_of(html)
    if a == b and t_ok and status == 200:
        print(f"ok   {p}")
        continue
    bad += 1
    i = next((k for k in range(min(len(a), len(b))) if a[k] != b[k]), min(len(a), len(b)))
    print(f"DIFF {status} {p} main_same={a == b} title_same={t_ok}")
    if not t_ok:
        print(f"      title static: {title_of(static)!r}\n      title wp:     {title_of(html)!r}")
    if a != b:
        print(f"      static: ...{a[max(0, i - 100):i + 140]}\n      wp:     ...{b[max(0, i - 100):i + 140]}")
print(f"\n{len(paths) - bad}/{len(paths)} match")
