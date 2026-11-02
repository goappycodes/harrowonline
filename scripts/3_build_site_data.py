"""Convert data/harrow-listings.csv into the compact JSON the site loads."""
import csv
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "data" / "harrow-listings.csv"
OUT = ROOT / "data" / "listings.json"


def split(v):
    return [x.strip() for x in (v or "").split("|") if x.strip()]


rows = []
with SRC.open(encoding="utf-8-sig", newline="") as f:
    for r in csv.DictReader(f):
        pc = (r["postcode"] or "").strip().upper()
        rows.append({
            "id": r["overture_id"],
            "n": r["name"].strip(),
            "g": r["category_group"],
            "s": r["subcategory"] or "",
            "c": r["category"],
            "a": (r["address"] or "").strip(),
            "pc": pc,
            "d": pc.split(" ")[0] if " " in pc else pc[:-3],
            "ph": split(r["phone"]),
            "w": split(r["website"]),
            "e": split(r["email"]),
            "so": split(r["socials"]),
            "cf": float(r["confidence"]),
            "src": split(r["sources"]),
            "lat": float(r["latitude"]),
            "lng": float(r["longitude"]),
        })

OUT.write_text(json.dumps(rows, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
print(f"wrote {len(rows)} listings to {OUT} ({OUT.stat().st_size // 1024} KB)")
