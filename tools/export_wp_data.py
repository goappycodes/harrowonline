"""
Export the Hub's data for the WordPress import (wp-local/hub/import-local.php), using the same loaders as the
prototype (hub_data.py, gen_events.py), so WordPress gets exactly the same businesses, offers, events and classes.

    python tools/export_wp_data.py      ->  data/wp-hub-data.json
"""
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))
sys.path.insert(0, str(ROOT / "tools"))
import gen_listings as L   # noqa: E402  (listing order, CAT_INTRO)
import hub_data as H       # noqa: E402
import gen_events as G     # noqa: E402
import classes_data as CD  # noqa: E402  (the Classes and activities page's cards)


def business(x, order):
    out = {k: v for k, v in x.items() if k not in ("claimed",)}
    out["order"] = order
    if x.get("claimed"):
        c = {k: v for k, v in x["claimed"].items() if k not in ("offers", "ended_offers", "events")}
        # weekly classes typed into claimed.json (one-off events come from the event posts by organiser name)
        c["class_events"] = [ev for ev in x["claimed"].get("events", []) if ev.get("class")]
        out["claimed"] = c
    return out


def offer(x, o, i):
    keep = {k: v for k, v in o.items() if k not in ("end", "days_left", "live", "ends_short", "href")}
    keep["end"] = o["end"].isoformat() if o["end"] else ""
    keep["business"] = x["slug"]
    keep["index"] = i
    return keep


def main():
    listings = L.listings()   # sorted: Verified, Checked, imported; then plan; then name
    businesses, offers = [], []
    for n, x in enumerate(listings):
        businesses.append(business(x, n))
        if x.get("claimed"):
            all_o = x["claimed"]["offers"] + x["claimed"].get("ended_offers", [])
            for i, o in enumerate(x["claimed"].get("offers_all", all_o)):
                offers.append(offer(x, o, i))
    events = []
    for ev in G.EVENTS:
        e = {k: v for k, v in ev.items() if k not in ("day", "start", "end", "href")}
        e.update(day=ev["day"].isoformat(), start=ev["start"].isoformat(), end=ev["end"].isoformat())
        events.append(e)
    # Weekly classes. "card" is how the class shows on the Classes and activities page (tools/classes_data.py): its
    # place on the page, a short text, and the "when", price, age or provider wording where the card words it differently.
    # Entries on that page with no weekly class of their own (Kodak FC's teams) are classes with "day": None and
    # "page": False: they appear only on that page, and their card links to the business.
    cards = {c[0]: (n, c) for n, c in enumerate(CD.C)}

    def card(title, when, price, age, provider):
        n, c = cards[title]
        out = {"order": n, "text": c[9]}
        for key, mine, theirs in (("when", when, c[2]), ("price", price, c[5]), ("age", age, c[4]), ("provider", provider, c[8])):
            if mine != theirs:
                out[key] = theirs
        return out

    classes = []
    for i, c in enumerate(G.CLASSES):
        day, tm, sort, title, venue, area, price, age, ic, feat, ctype = c
        classes.append({"title": title, "slug": G.class_slug(title), "day": day, "time": tm, "sort": sort, "venue": venue,
                        "area": area, "price": price, "age": age, "icon": ic, "featured": feat, "type": ctype,
                        "desc": G.CLASS_DESC.get(title, ""), "real": G.CLASS_REAL.get(title) or None,
                        "provider": G.CLASS_PROVIDER.get(title, ""), "order": i,
                        "page": True, "card": card(title, f"Every {G.DAYNAMES[day]}, {tm}", price, age, G.CLASS_PROVIDER.get(title, ""))})
    known = {c[3] for c in G.CLASSES}
    for t, ty, when, area, age, price, icon, feat, prov, text in CD.C:
        if t in known:
            continue
        img, alt, _prov_page, _link, fit = CD.REAL[t]
        classes.append({"title": t, "slug": G.class_slug(t), "day": None, "time": "", "sort": 0, "venue": "", "area": area,
                        "price": price, "age": age, "icon": icon, "featured": feat, "type": ty, "desc": "",
                        "real": {"img": img, "alt": alt, **({"fit": "logo"} if fit == "logo" else {})},
                        "provider": prov, "order": len(classes), "page": False, "card": card(t, None, price, age, prov)})
    data = {
        "today": H.PREVIEW_TODAY.isoformat(),
        "categories": [{"slug": s, "name": n, "icon": i, "intro": L.CAT_INTRO.get(s, "")} for s, n, i, _r in H.CATEGORIES],
        "areas": [{"slug": s, "name": n, "lat": a, "lng": b} for s, n, a, b in H.AREAS],
        "businesses": businesses,
        "offers": offers,
        "events": events,
        "classes": classes,
    }
    out = ROOT / "data" / "wp-hub-data.json"
    out.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8", newline="\n")
    print(f"{out.relative_to(ROOT)}: {len(businesses)} businesses, {len(offers)} offers, {len(events)} events, {len(classes)} classes")


if __name__ == "__main__":
    main()
