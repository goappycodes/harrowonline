"""
Favicons, app icons, web app manifest and the default social share image for the Hub.

    python tools/make_icons.py

Source: tools/brand/ho-symbol-512.jpg (the Harrow Online symbol used as the news site's favicon) and
site/hub/assets/img/hub-logo.png (made by make_hub_logo.py). Outputs:
    site/hub/favicon.ico                      16, 32, 48 px
    site/hub/assets/img/icon-192.png, icon-512.png, icon-maskable-512.png, apple-touch-icon.png (180)
    site/hub/assets/img/og-default.png        1200x630 Open Graph / X card
    site/hub/site.webmanifest                 Add to Home Screen (standalone, theme colour)
In WordPress: favicon/app icons go in Appearance → Customise → Site Identity on the Hub install, or the theme head.
"""
import json
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))
import seo  # noqa: E402

HUB = ROOT / "site" / "hub"
IMG = HUB / "assets" / "img"
SYMBOL = ROOT / "tools" / "brand" / "ho-symbol-512.jpg"
RED = (223, 32, 40)
INK = (20, 20, 20)
FONT_BOLD = "C:/Windows/Fonts/arialbd.ttf"


def font(size):
    try:
        return ImageFont.truetype(FONT_BOLD, size)
    except OSError:
        return ImageFont.load_default()


def icons():
    sym = Image.open(SYMBOL).convert("RGB")
    sym.resize((48, 48), Image.LANCZOS).save(HUB / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)])
    for size, name in ((192, "icon-192.png"), (512, "icon-512.png"), (180, "apple-touch-icon.png")):
        sym.resize((size, size), Image.LANCZOS).save(IMG / name, optimize=True)
    # Maskable: symbol at 80% on white so Android's circle/squircle mask never clips it
    m = Image.new("RGB", (512, 512), "white")
    s = sym.resize((410, 410), Image.LANCZOS)
    m.paste(s, ((512 - 410) // 2, (512 - 410) // 2))
    m.save(IMG / "icon-maskable-512.png", optimize=True)


def og_image():
    W, H = 1200, 630
    im = Image.new("RGB", (W, H), "white")
    d = ImageDraw.Draw(im)
    logo = Image.open(IMG / "hub-logo.png").convert("RGBA")
    lw = 760
    logo = logo.resize((lw, round(logo.height * lw / logo.width)), Image.LANCZOS)
    im.paste(logo, ((W - lw) // 2, 110), logo)
    d.rectangle([0, H - 170, W, H], fill=RED)
    line1 = "Local businesses, offers and what’s on in Harrow"
    f1 = font(44)
    w1 = d.textlength(line1, font=f1)
    d.text(((W - w1) / 2, H - 140), line1, font=f1, fill="white")
    line2 = "powered by Harrow Online Newsletter"
    f2 = font(30)
    w2 = d.textlength(line2, font=f2)
    d.text(((W - w2) / 2, H - 78), line2, font=f2, fill=(255, 225, 226))
    im.save(IMG / "og-default.png", optimize=True)


def manifest():
    data = {
        "name": seo.SITE_NAME,
        "short_name": "Harrow Hub",
        "description": f"Local businesses, offers, events and classes in Harrow, {seo.STRAPLINE}.",
        "lang": "en-GB",
        "start_url": "./?source=pwa",
        "scope": "./",
        "display": "standalone",
        "background_color": "#FFFFFF",
        "theme_color": seo.THEME_COLOR,
        "icons": [
            {"src": "assets/img/icon-192.png", "sizes": "192x192", "type": "image/png"},
            {"src": "assets/img/icon-512.png", "sizes": "512x512", "type": "image/png"},
            {"src": "assets/img/icon-maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable"},
        ],
        "shortcuts": [
            {"name": "Search businesses", "url": "search/"},
            {"name": "Local Offers", "url": "offers/"},
            {"name": "What's On", "url": "events/"},
        ],
    }
    (HUB / "site.webmanifest").write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8", newline="\n")


if __name__ == "__main__":
    icons()
    og_image()
    manifest()
    print("Icons, share image and manifest written to", HUB)
