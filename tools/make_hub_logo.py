"""
Hub logo files for the website, from the client's artwork ("HUB powered by Harrow Online Newsletter", Nov 2026).

    python tools/make_hub_logo.py [<dir with "HUB SVG.svg" and "HUB transparent background.png">]

The source SVG is kept in tools/brand/hub-logo-source.svg, so the folder argument is only needed when the
client sends new artwork. Writes to site/hub/assets/img/:
    hub-logo.svg          header and drawer (light backgrounds), original colours
    hub-logo-white.svg    footer (dark background): the black parts turn white, red and grey stay
    hub-logo.png          600px-wide PNG of the colour logo (make_icons.py builds the share image from it)
    hub-logo-white.png    600px-wide PNG of the white version (for emails or anywhere SVG can't be used)
The phone screen in the artwork is transparent, so the white version works on any dark background.
"""
import re
import shutil
import sys
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
BRAND = ROOT / "tools/brand"
OUT = ROOT / "site/hub/assets/img"
SRC_SVG = BRAND / "hub-logo-source.svg"
SRC_PNG = BRAND / "hub-logo-source.png"
PNG_WIDTH = 600

# Colour SVG: classes cls-1/cls-2 are red, cls-4 grey, cls-5 none, cls-6 near-black; paths with no fill class are black.
WHITE_CSS = ".cls-6{fill:#fff;}path:not(.cls-1):not(.cls-2):not(.cls-4):not(.cls-5){fill:#fff;}"


def import_artwork(folder):
    folder = Path(folder)
    BRAND.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(folder / "HUB SVG.svg", SRC_SVG)
    shutil.copyfile(folder / "HUB transparent background.png", SRC_PNG)


def svgs():
    svg = SRC_SVG.read_text(encoding="utf-8")
    svg = re.sub(r"^<\?xml[^>]*>\s*", "", svg)
    svg = svg.replace("<svg ", '<svg role="img" aria-label="Harrow Online Hub" ', 1)
    (OUT / "hub-logo.svg").write_text(svg, encoding="utf-8", newline="\n")
    white = svg.replace("</style>", WHITE_CSS + "</style>", 1)
    (OUT / "hub-logo-white.svg").write_text(white, encoding="utf-8", newline="\n")


def is_dark(r, g, b):
    return max(r, g, b) < 120 and max(r, g, b) - min(r, g, b) < 30


def pngs():
    im = Image.open(SRC_PNG).convert("RGBA")
    im = im.crop(im.getbbox())
    h = round(im.height * PNG_WIDTH / im.width)
    colour = im.resize((PNG_WIDTH, h), Image.LANCZOS)
    colour.save(OUT / "hub-logo.png", optimize=True)
    px = [(255, 255, 255, a) if a and is_dark(r, g, b) else (r, g, b, a) for r, g, b, a in colour.getdata()]
    white = Image.new("RGBA", colour.size)
    white.putdata(px)
    white.save(OUT / "hub-logo-white.png", optimize=True)
    return colour.size


if __name__ == "__main__":
    if len(sys.argv) > 1:
        import_artwork(sys.argv[1])
    svgs()
    print("SVG logos written; PNG size", pngs())
