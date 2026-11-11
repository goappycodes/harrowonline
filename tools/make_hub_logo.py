"""
Make the Harrow Online Hub logos from the existing Harrow Online logos:
a red "HUB" block beside "HARROW", the same height as its letters. The original artwork is untouched;
the canvas is only widened to fit the block.

    python tools/make_hub_logo.py <dir with Website-Logo-Small-1.png and bottomlogo.png>

Writes site/hub/assets/img/hub-logo.png (header, 2x) and hub-logo-white.png (footer, 2x).
"""
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

SRC = Path(sys.argv[1])
OUT = Path(__file__).resolve().parent.parent / "site/hub/assets/img"
RED = (223, 32, 40, 255)  # --ho-red, sampled from the logo
SCALE = 4                  # draw at 4x, save at 2x for crisp edges
FONTS = ["C:/Windows/Fonts/impact.ttf", "/usr/share/fonts/truetype/msttcorefonts/Impact.ttf", "C:/Windows/Fonts/arialbd.ttf"]


def font(size):
    for f in FONTS:
        if Path(f).exists():
            return ImageFont.truetype(f, size)
    return ImageFont.load_default()


def make(src_name, out_name):
    logo = Image.open(SRC / src_name).convert("RGBA")
    w, h = logo.size                      # 233 x 90 (header), 231 x 90 (footer)
    # Block geometry in logo pixels: cap height of "HARROW" is about y 10..44
    bx0, by0, bh = 203, 10, 35
    bw = 64
    cw = bx0 + bw + 2                     # widened canvas

    big = Image.new("RGBA", (cw * SCALE, h * SCALE), (0, 0, 0, 0))
    big.alpha_composite(logo.resize((w * SCALE, h * SCALE), Image.LANCZOS), (0, 0))
    d = ImageDraw.Draw(big)
    box = [bx0 * SCALE, by0 * SCALE, (bx0 + bw) * SCALE, (by0 + bh) * SCALE]
    d.rounded_rectangle(box, radius=5 * SCALE, fill=RED)
    f = font(31 * SCALE)
    tb = d.textbbox((0, 0), "HUB", font=f)
    tw, th = tb[2] - tb[0], tb[3] - tb[1]
    tx = box[0] + (box[2] - box[0] - tw) / 2 - tb[0]
    ty = box[1] + (box[3] - box[1] - th) / 2 - tb[1]
    d.text((tx, ty), "HUB", font=f, fill=(255, 255, 255, 255))

    out = big.resize((cw * 2, h * 2), Image.LANCZOS)
    OUT.mkdir(parents=True, exist_ok=True)
    out.save(OUT / out_name, optimize=True)
    print(out_name, out.size, "display at", cw, "x", h)


make("Website-Logo-Small-1.png", "hub-logo.png")
make("bottomlogo.png", "hub-logo-white.png")
