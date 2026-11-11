"""
Harrow Online Hub: listing data, local categories and areas.

Reads data/listings.json (Overture Maps Places, filtered to ~2,600 Harrow businesses by scripts/) and adds:
  - a local category (23 top-level categories mapped from Overture's group + subcategory; draft for client approval)
  - an Area (nearest Harrow area centre to the map pin; same list the news site's area hubs will use)
  - a URL slug: {name}-{area}, unique

Brief 01, section 6.1. Categories and areas are drafts here; in WordPress they become editable taxonomies.
"""
import json
import math
import re
import unicodedata
from pathlib import Path

ROOT = Path(__file__).parent

# slug, name, icon, [(overture group, overture subcategory or "*")]
CATEGORIES = [
    ("restaurants", "Restaurants", "food", [("food_and_drink", "restaurant")]),
    ("cafes-and-takeaways", "Cafés and takeaways", "cup", [("food_and_drink", "casual_eatery"), ("food_and_drink", "non_alcoholic_beverage_venue"), ("food_and_drink", "")]),
    ("pubs-and-bars", "Pubs and bars", "beer", [("food_and_drink", "alcoholic_beverage_venue")]),
    ("food-shops-and-groceries", "Food shops and groceries", "cart", [("shopping", "food_and_beverage_store"), ("shopping", "convenience_store"), ("shopping", "market"), ("shopping", "warehouse_club_store"), ("shopping", "discount_store")]),
    ("fashion-and-clothing", "Fashion and clothing", "shirt", [("shopping", "fashion_and_apparel_store"), ("shopping", "second_hand_store")]),
    ("shops", "Shops and specialists", "gift", [("shopping", "specialty_store"), ("shopping", ""), ("shopping", "shopping_mall"), ("shopping", "department_store")]),
    ("hair-and-beauty", "Hair and beauty", "scissors", [("lifestyle_services", "personal_or_beauty_service")]),
    ("health-and-wellbeing", "Health and wellbeing", "leaf", [("lifestyle_services", "wellness_service")]),
    ("doctors-dentists-and-clinics", "Doctors, dentists and clinics", "medical", [("health_care", "*")]),
    ("pets", "Pets and vets", "paw", [("lifestyle_services", "animal_or_pet_service")]),
    ("trades-and-home-services", "Trades and home services", "wrench", [("services_and_business", "home_service"), ("services_and_business", "building_or_construction_service")]),
    ("property-and-estate-agents", "Property and estate agents", "key", [("services_and_business", "real_estate_service"), ("services_and_business", "housing_or_property_service")]),
    ("finance-and-accountants", "Finance and accountants", "pound", [("services_and_business", "financial_service")]),
    ("legal-services", "Legal services", "scale", [("services_and_business", "legal_service")]),
    ("professional-services", "Professional services", "briefcase", [("services_and_business", "professional_service"), ("services_and_business", "design_service")]),
    ("printing-and-signs", "Printing and signs", "print", [("services_and_business", "printing_service")]),
    ("events-and-parties", "Events, parties and catering", "party", [("services_and_business", "event_or_party_service"), ("lifestyle_services", "food_service")]),
    ("family-and-childcare", "Family and childcare", "family", [("services_and_business", "family_service")]),
    ("cleaning-laundry-and-hire", "Cleaning, laundry and hire", "washer", [("services_and_business", "laundry_service"), ("services_and_business", "rental_service")]),
    ("motoring", "Motoring", "car", [("travel_and_transportation", "vehicle_service"), ("shopping", "vehicle_dealer")]),
    ("travel", "Travel and transport", "plane", [("travel_and_transportation", "travel_service")]),
    ("sport-and-fitness", "Sport and fitness", "dumbbell", [("sports_and_recreation", "*")]),
    ("arts-and-entertainment", "Arts and entertainment", "music", [("arts_and_entertainment", "*")]),
    ("hotels-and-places-to-stay", "Hotels and places to stay", "bed", [("lodging", "*")]),
]

# slug, name, lat, lng (approximate centres; assignment = nearest centre to the map pin)
AREAS = [
    ("harrow-town-centre", "Harrow Town Centre", 51.5800, -0.3355),
    ("harrow-on-the-hill", "Harrow on the Hill", 51.5715, -0.3375),
    ("west-harrow", "West Harrow", 51.5790, -0.3530),
    ("wealdstone", "Wealdstone", 51.5960, -0.3340),
    ("harrow-weald", "Harrow Weald", 51.6085, -0.3390),
    ("headstone", "Headstone", 51.5935, -0.3560),
    ("north-harrow", "North Harrow", 51.5845, -0.3620),
    ("rayners-lane", "Rayners Lane", 51.5745, -0.3715),
    ("south-harrow", "South Harrow", 51.5645, -0.3525),
    ("sudbury-hill", "Sudbury Hill and Roxeth", 51.5560, -0.3350),
    ("pinner", "Pinner", 51.5935, -0.3810),
    ("hatch-end", "Hatch End", 51.6090, -0.3680),
    ("kenton", "Kenton", 51.5830, -0.3030),
    ("belmont", "Belmont", 51.6025, -0.3160),
    ("queensbury", "Queensbury", 51.5945, -0.2860),
    ("stanmore", "Stanmore", 51.6170, -0.3105),
    ("canons-park", "Canons Park", 51.6080, -0.2950),
    ("edgware", "Edgware", 51.6135, -0.2750),
    ("greenhill", "Greenhill", 51.5870, -0.3300),
]

CAT_BY_SLUG = {c[0]: c for c in CATEGORIES}
AREA_BY_SLUG = {a[0]: a for a in AREAS}
_CAT_RULES = {}
for slug, _n, _i, rules in CATEGORIES:
    for g, s in rules:
        _CAT_RULES[(g, s)] = slug


def slugify(text):
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode()
    text = re.sub(r"[^a-zA-Z0-9]+", "-", text.lower()).strip("-")
    return re.sub(r"-{2,}", "-", text)[:70].strip("-") or "business"


def tidy(text):
    """Some sources supply names/addresses in capitals: show them in title case."""
    if text and text == text.upper() and re.search(r"[A-Z]{3}", text):
        return re.sub(r"\b([a-z])", lambda m: m.group(1).upper(), text.lower())
    return text


def label(slug):
    s = (slug or "").replace("_", " ").strip()
    return s[:1].upper() + s[1:]


def category_for(g, s):
    return _CAT_RULES.get((g, s)) or _CAT_RULES.get((g, "*")) or "shops"


def area_for(lat, lng):
    best, best_d = AREAS[0][0], 1e9
    k = math.cos(math.radians(51.59))
    for slug, _n, alat, alng in AREAS:
        d = (lat - alat) ** 2 + ((lng - alng) * k) ** 2
        if d < best_d:
            best, best_d = slug, d
    return best


def phone_display(p):
    p = re.sub(r"[^\d+]", "", p or "")
    if p.startswith("+44"):
        p = "0" + p[3:]
    if len(p) == 11 and p.startswith("020"):
        return f"{p[:3]} {p[3:7]} {p[7:]}"
    if len(p) == 11 and p.startswith("07"):
        return f"{p[:5]} {p[5:]}"
    if len(p) == 11:
        return f"{p[:5]} {p[5:]}"
    return p


def load_listings():
    raw = json.loads((ROOT / "data/listings.json").read_text(encoding="utf-8"))
    used = set()
    out = []
    for r in raw:
        name = tidy(r["n"]).strip()
        area = area_for(r["lat"], r["lng"])
        base = slugify(f"{name} {AREA_BY_SLUG[area][1]}")
        slug, i = base, 2
        while slug in used:
            slug, i = f"{base}-{i}", i + 1
        used.add(slug)
        out.append({
            "id": r["id"],
            "slug": slug,
            "name": name,
            "cat": category_for(r["g"], r["s"]),
            "sub": label(r["c"]) if r.get("c") else "",
            "area": area,
            "address": tidy(r["a"] or ""),
            "postcode": r["pc"],
            "phone": [phone_display(p) for p in r["ph"]],
            "phone_raw": [re.sub(r"[^\d+]", "", p) for p in r["ph"]],
            "web": [w if re.match(r"https?://", w) else "https://" + w.lstrip("/") for w in r["w"]],
            "email": r["e"],
            "social": r["so"],
            "lat": r["lat"],
            "lng": r["lng"],
            "sources": r["src"],
        })
    out.sort(key=lambda x: x["name"].lower())
    return out


if __name__ == "__main__":
    import collections
    L = load_listings()
    print(len(L), "listings")
    print(collections.Counter(x["cat"] for x in L).most_common())
    print(collections.Counter(x["area"] for x in L).most_common())
