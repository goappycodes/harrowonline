"""
SEO layer for the Hub build: clean URLs, canonical + social meta, JSON-LD and the indexing rules (brief 03, S3).

Everything a WordPress theme needs to reproduce is in this one file:
  * PAGE_PATHS   page template → production URL (brief 01, section 5)
  * head()       the <head> SEO block (canonical, robots, Open Graph, Twitter, icons, theme colour)
  * graph()      one JSON-LD @graph per page: Organization (same @id as the news site), Hub WebSite,
                 WebPage, BreadcrumbList (built from the visible breadcrumb, so there is one source),
                 plus page nodes (LocalBusiness, Event, ItemList ...)
  * listing_indexable() / archive_indexable()  the index/noindex rules
"""
import html as htmllib
import json
import re

ORIGIN = "https://harrowonline.org"          # production origin; canonicals and schema always point here
SITE_NAME = "Harrow Online Hub"
TITLE_SUFFIX = " | Harrow Online Hub"
STRAPLINE = "powered by Harrow Online Newsletter"
THEME_COLOR = "#DF2028"                      # Harrow Online red (--ho-red): browser toolbar / Android status bar
OG_IMAGE = "/assets/img/og-default.png"      # 1200x630 default share image
OG_IMAGE_ALT = "Harrow Online Hub: local businesses, offers and what's on in Harrow"
TWITTER = "@harrowonline"

# Indexing thresholds (brief 03, S3). Editable in WordPress admin in the real build.
MIN_DESC_WORDS = 50          # claimed listing: description words
MIN_ARCHIVE_LISTINGS = 5     # category/area archive: indexable listings needed

# Page template (src/pages/{name}.html) → URL path under the Hub base. Brief 01, section 5.
PAGE_PATHS = {
    "index": "/",
    "search": "/search/",
    "categories": "/categories/",
    "offers": "/offers/",
    "events": "/events/",
    "events-month": "/events/month/",
    "event": "/events/harrow-christmas-lights-switch-on/",
    "classes": "/events/classes/",
    "class": "/events/saturday-kids-coding-club/",
    "pricing": "/pricing/",
    "add-business": "/add-business/",
    "claim": "/claim/",
    "claim-submitted": "/claim/submitted/",
    "report": "/report/",
    "advertise-newsletter": "/advertise/newsletter/",
    "account": "/account/",
    "account-listings": "/account/listings/",
    "account-listing-edit": "/account/listings/edit/",
    "account-offers": "/account/offers/",
    "account-offer-edit": "/account/offers/edit/",
    "account-events": "/account/events/",
    "account-event-edit": "/account/events/edit/",
    "account-bookings": "/account/newsletter/",
    "account-billing": "/account/billing/",
    "account-settings": "/account/settings/",
    "sign-in": "/sign-in/",
    "register": "/register/",
    "forgot-password": "/forgot-password/",
    "privacy": "/privacy/",
    "terms": "/terms/",
    "data-sources": "/data-sources/",
    "pages": "/pages/",
    "404": "/404.html",
    # Mock-ups of harrowonline.org pages (Yoast owns their SEO on the news site). Preview copies only.
    "news-advertise": "/news-site/advertise/",
    "news-support": "/news-site/support/",
    "news-article": "/news-site/article/",
}
PAGE_SCHEMA = {
    "index": "CollectionPage", "search": "SearchResultsPage", "categories": "CollectionPage", "offers": "CollectionPage", "events": "CollectionPage",
    "events-month": "CollectionPage", "classes": "CollectionPage", "pricing": "WebPage", "data-sources": "AboutPage",
    "report": "ContactPage", "event": "ItemPage", "class": "ItemPage",
}
NEWS_CANONICAL = {
    "news-advertise": ORIGIN + "/advertise/",
    "news-support": ORIGIN + "/support/",
    "news-article": ORIGIN + "/2026/11/10/independent-shops-back-late-night-christmas-shopping-on-harrow-on-the-hill/",
}

WRITTEN = []   # (path, <head> html) of every page build.write() saved this run

LINK_RE = re.compile(r'((?:href|action)=")/([\w./-]*?)\.html(?=[?#"])')


def clean_path(stem):
    """'/business/foo' → '/business/foo/'; 'events' → PAGE_PATHS['events']."""
    stem = stem.strip("/")
    if stem in PAGE_PATHS:
        return PAGE_PATHS[stem]
    return "/" + stem + "/"


def rewrite_links(html):
    """Turn source links ('/events.html?x', '/business/foo.html') into production paths ('/events/?x')."""
    return LINK_RE.sub(lambda m: m.group(1) + clean_path(m.group(2)), html)


def absolute(base, path):
    return ORIGIN + base + path


# ------------------------------------------------------------------ indexing rules
def words(paragraphs):
    text = re.sub(r"<[^>]+>|&\w+;", " ", " ".join(paragraphs))
    return len(text.split())


def listing_indexable(x):
    """Brief 03: unclaimed → noindex. Claimed → index only when filled in (50+ words AND hours or a photo)."""
    c = x.get("claimed")
    if not c:
        return False
    has_hours = any(h and h.lower() != "closed" for h in c.get("hours", []))
    return words(c.get("description", [])) >= MIN_DESC_WORDS and (has_hours or c.get("photos", 0) > 0)


def archive_indexable(items, has_intro):
    """Brief 03: category/area archives index only with 5+ indexable listings AND intro text."""
    return has_intro and sum(1 for x in items if listing_indexable(x)) >= MIN_ARCHIVE_LISTINGS


# ------------------------------------------------------------------ <head>
def trim(s, n=158):
    """Meta description: at most n characters, cut at a word boundary."""
    s = " ".join(htmllib.unescape(s).split())
    return s if len(s) <= n else s[:n - 1].rsplit(" ", 1)[0].rstrip(",;:.") + "…"


def esc(s):
    return htmllib.escape(htmllib.unescape(s), quote=True)


def head(meta, base, title):
    """SEO <head> block. meta: description, robots, path, canonical (absolute override), ogtype, image."""
    url = meta.get("canonical") or absolute(base, meta["path"])
    desc = esc(trim(meta.get("description", "")))
    image = meta.get("image") or absolute(base, OG_IMAGE)
    og_title = esc(meta.get("og_title") or title)
    robots = meta.get("robots") or "index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1"
    tags = [
        f'<meta name="robots" content="{robots}">',
        f'<link rel="canonical" href="{url}">',
        '<meta property="og:locale" content="en_GB">',
        f'<meta property="og:type" content="{meta.get("ogtype", "website")}">',
        f'<meta property="og:site_name" content="{SITE_NAME}">',
        f'<meta property="og:title" content="{og_title}">',
        f'<meta property="og:description" content="{desc}">',
        f'<meta property="og:url" content="{url}">',
        f'<meta property="og:image" content="{image}">',
        '<meta property="og:image:width" content="1200">',
        '<meta property="og:image:height" content="630">',
        f'<meta property="og:image:alt" content="{esc(OG_IMAGE_ALT)}">',
        '<meta name="twitter:card" content="summary_large_image">',
        f'<meta name="twitter:site" content="{TWITTER}">',
        f'<meta name="theme-color" content="{THEME_COLOR}">',
        '<meta name="color-scheme" content="light">',
        '<meta name="format-detection" content="telephone=no">',
        '<meta name="mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-title" content="Harrow Hub">',
        '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
        '<link rel="icon" href="/favicon.ico" sizes="48x48">',
        '<link rel="icon" href="/assets/img/icon-192.png" type="image/png" sizes="192x192">',
        '<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">',
        '<link rel="manifest" href="/site.webmanifest">',
    ]
    return "\n".join(tags)


# ------------------------------------------------------------------ JSON-LD
ORG_ID = ORIGIN + "/#organization"
NEWS_SITE_ID = ORIGIN + "/#website"

ORGANIZATION = {
    # Same @id and details as the Yoast graph on harrowonline.org, so Google sees one publisher.
    "@type": "NewsMediaOrganization",
    "@id": ORG_ID,
    "name": "Harrow Online",
    "url": ORIGIN + "/",
    "logo": {
        "@type": "ImageObject", "inLanguage": "en-GB", "@id": ORIGIN + "/#/schema/logo/image/",
        "url": ORIGIN + "/wp-content/uploads/2020/08/Website-Logo-Small-1.png",
        "contentUrl": ORIGIN + "/wp-content/uploads/2020/08/Website-Logo-Small-1.png",
        "width": 233, "height": 90, "caption": "Harrow Online",
    },
    "image": {"@id": ORIGIN + "/#/schema/logo/image/"},
    "sameAs": ["https://www.facebook.com/harrowonline", "https://x.com/harrowonline", "https://www.instagram.com/harrowonline1/"],
    "areaServed": {"@type": "AdministrativeArea", "name": "London Borough of Harrow"},
}


def hub_website(base):
    hub = absolute(base, "/")
    return {
        "@type": "WebSite",
        "@id": hub + "#website",
        "url": hub,
        "name": SITE_NAME,
        "alternateName": "Harrow Online business directory",
        "description": f"Local businesses, offers, events and classes in Harrow, {STRAPLINE}.",
        "publisher": {"@id": ORG_ID},
        "isPartOf": {"@id": NEWS_SITE_ID},
        "inLanguage": "en-GB",
        "potentialAction": [{
            "@type": "SearchAction",
            "target": {"@type": "EntryPoint", "urlTemplate": hub + "search/?q={search_term_string}"},
            "query-input": "required name=search_term_string",
        }],
    }


CRUMB_NAV = re.compile(r'<nav class="crumbs[^"]*"[^>]*>(.*?)</nav>', re.S)
CRUMB_LI = re.compile(r'<li>\s*(?:<a href="([^"]*)">(.*?)</a>|<span[^>]*>(.*?)</span>)\s*</li>', re.S)


def text(s):
    return " ".join(htmllib.unescape(re.sub(r"<[^>]+>", " ", s)).split())


def breadcrumbs(body, base, url):
    """BreadcrumbList from the visible breadcrumb (one source). Filter/anchor links (?, #) are left out of the schema."""
    m = CRUMB_NAV.search(body)
    if not m:
        return None
    items = []
    for href, a_text, span_text in CRUMB_LI.findall(m.group(1)):
        if href and ("?" in href or "#" in href):
            continue
        node = {"@type": "ListItem", "position": len(items) + 1, "name": text(a_text or span_text)}
        if href:
            node["item"] = absolute(base, href[len(base):] if href.startswith(base) else href)
        else:
            node["item"] = url
        items.append(node)
    if len(items) < 2:
        return None
    return {"@type": "BreadcrumbList", "@id": url + "#breadcrumb", "itemListElement": items}


LD_RE = re.compile(r'\s*<script type="application/ld\+json">(.*?)</script>', re.S)


def graph(meta, body, base, title):
    """Return (body_without_inline_ld, '<script type="application/ld+json">…</script>')."""
    url = meta.get("canonical") or absolute(base, meta["path"])
    extra = list(meta.get("ld", []))

    def take(m):
        data = json.loads(m.group(1))
        nodes = data.get("@graph", [data])
        for n in nodes:
            n.pop("@context", None)
            if n.get("@type") != "BreadcrumbList":   # breadcrumbs come from the visible trail only
                extra.append(n)
        return ""
    body = LD_RE.sub(take, body)

    crumbs = breadcrumbs(body, base, url)
    page = {
        "@type": meta.get("schema", "WebPage"),
        "@id": url + "#webpage",
        "url": url,
        "name": title,
        "description": trim(meta.get("description", "")),
        "isPartOf": {"@id": absolute(base, "/") + "#website"},
        "publisher": {"@id": ORG_ID},
        "inLanguage": "en-GB",
    }
    if crumbs:
        page["breadcrumb"] = {"@id": crumbs["@id"]}
    main = next((n for n in extra if n.get("@id") and n.get("@type") not in ("ItemList",)), None)
    if main:
        page["mainEntity"] = {"@id": main["@id"]}
    nodes = [ORGANIZATION, hub_website(base), page] + ([crumbs] if crumbs else []) + extra
    ld = json.dumps({"@context": "https://schema.org", "@graph": nodes}, ensure_ascii=False, separators=(",", ":"))
    return body, f'<script type="application/ld+json">{ld}</script>'


# ------------------------------------------------------------------ page nodes
# Most specific schema.org type per category; a sub-category keyword can narrow it further.
CAT_TYPE = {
    "restaurants": "Restaurant", "cafes-and-takeaways": "CafeOrCoffeeShop", "pubs-and-bars": "BarOrPub",
    "food-shops-and-groceries": "GroceryStore", "fashion-and-clothing": "ClothingStore", "shops": "Store",
    "hair-and-beauty": "BeautySalon", "health-and-wellbeing": "HealthAndBeautyBusiness",
    "doctors-dentists-and-clinics": "MedicalBusiness", "pets": "LocalBusiness",
    "trades-and-home-services": "HomeAndConstructionBusiness", "property-and-estate-agents": "RealEstateAgent",
    "finance-and-accountants": "FinancialService", "legal-services": "LegalService",
    "professional-services": "ProfessionalService", "printing-and-signs": "LocalBusiness",
    "events-and-parties": "LocalBusiness", "family-and-childcare": "ChildCare",
    "cleaning-laundry-and-hire": "DryCleaningOrLaundry", "motoring": "AutomotiveBusiness",
    "travel": "TravelAgency", "sport-and-fitness": "SportsActivityLocation",
    "arts-and-entertainment": "EntertainmentBusiness", "hotels-and-places-to-stay": "LodgingBusiness",
}
SUB_TYPE = [
    (r"dent", "Dentist"), (r"pharmac", "Pharmacy"), (r"barber|hair", "HairSalon"), (r"plumb|heating", "Plumber"),
    (r"football|sports club", "SportsClub"), (r"bike|cycle", "BikeStore"), (r"book", "BookStore"), (r"florist", "Florist"), (r"gym|fitness", "ExerciseGym"),
    (r"indian|chinese|thai|italian|british", "Restaurant"), (r"vet", "VeterinaryCare"), (r"electric", "Electrician"),
    (r"estate agent|letting", "RealEstateAgent"), (r"account", "AccountingService"), (r"solicitor", "Attorney"),
    (r"coding|tutor|school|class", "EducationalOrganization"), (r"association", "Organization"),
]
DAY_URI = "https://schema.org/{}"
TIME_RE = re.compile(r"(\d{1,2})(?:[.:](\d{2}))?\s*(am|pm)", re.I)


def to_24h(t):
    m = TIME_RE.fullmatch(t.strip())
    if not m:
        return None
    h, mins, ap = int(m.group(1)), int(m.group(2) or 0), m.group(3).lower()
    h = h % 12 + (12 if ap == "pm" else 0)
    return f"{h:02d}:{mins:02d}"


def opening_hours(days, hours):
    """['Monday', …] + ['9am to 5.30pm', 'Closed', '9am to 1pm, 2pm to 5pm'] → OpeningHoursSpecification list."""
    out = []
    for day, h in zip(days, hours):
        for part in re.split(r",|&| and ", h):
            ends = [to_24h(p) for p in part.split(" to ")]
            if len(ends) == 2 and all(ends):
                out.append({"@type": "OpeningHoursSpecification", "dayOfWeek": DAY_URI.format(day), "opens": ends[0], "closes": ends[1]})
    return out


def business_type(x):
    for pat, t in SUB_TYPE:
        if re.search(pat, x.get("sub", ""), re.I):
            return t
    return CAT_TYPE.get(x["cat"], "LocalBusiness")


def local_business(x, base, area_name, cat_name, days, area_centre):
    url = absolute(base, f"/business/{x['slug']}/")
    c = x.get("claimed") or {}
    node = {
        "@type": business_type(x),
        "@id": url + "#business",
        "name": htmllib.unescape(x["name"]),
        "url": url,
        "address": {"@type": "PostalAddress", "streetAddress": htmllib.unescape(c.get("address") or x.get("address", "")),
                    "addressLocality": area_name, "addressRegion": "Harrow", "postalCode": x.get("postcode", ""), "addressCountry": "GB"},
        "areaServed": {"@type": "Place", "name": f"{area_name}, Harrow"},
        "image": absolute(base, OG_IMAGE),   # replaced by the business's own photo in WordPress
    }
    if not str(x.get("id", "")).startswith("sample-"):   # real business: its own logo and photos
        own = ([c["logo"]] if c.get("logo") else []) + list(c.get("images", []))
        if own:
            node["image"] = [absolute(base, i) for i in own]
        if c.get("logo"):
            node["logo"] = absolute(base, c["logo"])
        if x.get("email"):
            node["email"] = x["email"][0]
    if c.get("description"):
        node["description"] = text(" ".join(c["description"]))
    elif c.get("tagline"):
        node["description"] = text(c["tagline"])
    phone = c.get("phone") or (x["phone"][0] if x.get("phone") else "")
    if phone:
        node["telephone"] = phone
    lat, lng = x.get("lat"), x.get("lng")
    if lat is None and area_centre:
        lat, lng = area_centre
    if lat is not None:
        node["geo"] = {"@type": "GeoCoordinates", "latitude": round(lat, 5), "longitude": round(lng, 5)}
    if c.get("hours"):
        node["openingHoursSpecification"] = opening_hours(days, c["hours"])
    same = [w for w in x.get("web", []) if w.startswith("http")] + list((c.get("social_urls") or {}).values())
    if same:
        node["sameAs"] = same
    if c.get("offers"):
        node["makesOffer"] = [{"@type": "Offer", "name": text(o["title"]), "description": text(o.get("text", "")),
                               "url": absolute(base, o.get("href", "/offers/")),
                               **({"validThrough": o["end"].isoformat()} if o.get("end") else {})} for o in c["offers"] if o.get("title")]
    return node


def item_list(base, url, items, name):
    return {
        "@type": "ItemList", "@id": url + "#list", "name": name, "numberOfItems": len(items),
        "itemListElement": [{"@type": "ListItem", "position": i + 1, "url": absolute(base, f"/business/{x['slug']}/"),
                             "name": htmllib.unescape(x["name"])} for i, x in enumerate(items[:20])],
    }
