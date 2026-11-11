"""Check every internal link, script, stylesheet and icon reference in site/ resolves to a file. Run after build.py."""
import re
import sys
from pathlib import Path
from urllib.parse import urlsplit

WEB = Path(__file__).parent / "site"
REF = re.compile(r'(?:href|src|action)="([^"]+)"')
broken = {}
pages = list(WEB.rglob("*.html"))
for page in pages:
    html = page.read_text(encoding="utf-8")
    for url in set(REF.findall(html)):
        if url.startswith(("http:", "https:", "mailto:", "tel:", "#", "data:", "//")):
            continue
        path = urlsplit(url).path
        if not path or "/wp-json/" in path:  # WordPress REST endpoints don't exist in the static preview
            continue
        target = (WEB / path.lstrip("/")) if path.startswith("/") else (page.parent / path)
        if path.endswith("/"):
            target = target / "index.html"
        if not target.exists():
            broken.setdefault(url, []).append(str(page.relative_to(WEB)))

print(f"Checked {len(pages)} pages")
for url, where in sorted(broken.items()):
    print(f"BROKEN {url}  ({len(where)} pages, e.g. {where[0]})")
sys.exit(1 if broken else 0)
