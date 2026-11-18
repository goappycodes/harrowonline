"""
Templates for claimed listings (data/claimed.json via hub_data): directory rows, full profiles by plan,
offer cards and the Offers page list. Text fields in claimed.json are trusted HTML (entities allowed).

Plan rules shown here (brief 01, 6.2 and 6.5):
  basic     verified badge, 1 photo, description, hours, contact; related listings shown
  enhanced  + more photos, social links, offers and events; related listings shown
  premium   + cover photo, gallery, featured ranking, Business of the Week; NO competitor listings
  partner   Premium benefits + Local Partner badge
"""
from html import escape as e
from urllib.parse import quote

import hub_data as H

DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
TODAY = 2  # Wednesday 11 November 2026 (preview date)
PAID = ("enhanced", "premium", "partner")
TOP = ("premium", "partner")
SOON_DAYS = 7   # offers ending within a week get an "Ends soon" badge
SOCIAL_LABEL = {"facebook": "Facebook", "instagram": "Instagram", "tiktok": "TikTok", "linkedin": "LinkedIn", "x": "X"}

_uid = [0]


def icon(name, cls="ho-icon"):
    return f'<svg class="{cls}"><use href="#i-{name}"/></svg>'


def initials(name):
    words = [w for w in "".join(c if c.isalnum() or c == " " else " " for c in name).split() if w]
    return ("".join(w[0] for w in words[:2]) or "?").upper()


def cat_name(s):
    return H.CAT_BY_SLUG[s][1]


def area_name(s):
    return H.AREA_BY_SLUG[s][1]


def tier_badge(tier):
    if tier == "partner":
        return f'<span class="badge badge--partner">{icon("star")}Local Partner</span>'
    if tier == "premium":
        return f'<span class="badge badge--premium">{icon("star")}Featured</span>'   # Premium plan is sold as "Featured"
    return ""


def verified():
    return f'<span class="badge badge--verified">{icon("verified")}Verified by the business</span>'


def name_tick(x, focusable=True):
    """Status icon after a business name (cards, rows, profile h1); hover or focus shows the status name.
    Verified by the business (green badge: the real businesses Harrow Online supplied), Checked by Harrow Online
    (red tick: claimed.json "listing_status": "checked"), imported from public data (grey globe)."""
    if badge_sample(x):
        return ""
    if x.get("checked"):
        kind, s, ic = "checked", "Checked by Harrow Online", "check"
    elif x.get("claimed"):
        kind, s, ic = "verified", "Verified by the business", "verified"
    else:
        kind, s, ic = "imported", "Information imported from public data", "globe"
    tab = ' tabindex="0"' if focusable else ""   # inside a link the link takes focus (and shows the name)
    return f'<span class="card__tick card__tick--{kind}"{tab} role="img" aria-label="{s}" data-tip="{s}"><svg class="ho-icon" aria-hidden="true"><use href="#i-{ic}"/></svg></span>'


def name_with_tick(x, name=None, linked=True):
    """Business name with its status icon. The last word and the icon sit in one no-wrap span, so the icon never
    lands on a line of its own (18 Nov 2026). linked=True: the result goes inside the name link."""
    name = name or x["name"]
    # cards and rows (linked): no icon for imported listings, so Verified and Checked stand out (18 Nov 2026)
    tick = "" if linked and not (x.get("checked") or x.get("claimed")) else name_tick(x, focusable=not linked)
    if not tick:
        return e(name)
    head, _, last = name.rpartition(" ")
    return (e(head) + " " if head else "") + f'<span class="name-end">{e(last)}{tick}</span>'


def checked_badge():
    return f'<span class="badge badge--checked">{icon("check")}Checked by Harrow Online</span>'


def imported_badge():
    return '<span class="badge badge--unclaimed">Information imported from public data</span>'


def listing_status(x):
    """The one status badge for a listing that isn't claimed: checked by the team, or imported from public data."""
    return checked_badge() if x.get("checked") else imported_badge()


def is_sample(x):
    """Made-up preview business (hub_data gives them ids "sample-…"). Real ones ("real": true in claimed.json) get "claimed-…"."""
    return str(x.get("id", "")).startswith("sample-")


def has_address(c):
    """Mobile, service-based businesses (claimed.json "service_area") publish no street address."""
    return bool(c.get("address")) and not c.get("service_area")


def address_line(x, with_area=False):
    """'14 High Street, Wealdstone, HA3 7AB', or 'Covers Harrow and nearby areas' for a service-area business."""
    c = x["claimed"]
    if not has_address(c):
        return f'Covers {c.get("service_area") or "Harrow"}'
    parts = [c["address"]] + ([e(area_name(x["area"]))] if with_area else []) + ([e(x["postcode"])] if x.get("postcode") else [])
    return ", ".join(parts)


SHOW_SAMPLE_TAG = False   # 18 Nov 2026: no "Sample" tag anywhere; samples show the same badges as a real listing


def badge_sample(x):
    """True when a listing should carry the Sample tag instead of Verified/Featured (off: never)."""
    return SHOW_SAMPLE_TAG and is_sample(x)


def sample_badge():
    if not SHOW_SAMPLE_TAG:
        return ""
    return f'<span class="badge badge--sample" title="Made-up business for the preview. Removed before launch.">{icon("alert")}Sample</span>'


def status_badges(x):
    """A made-up sample shows only its Sample badge; a real listing shows Checked by Harrow Online (team-entered,
    claimed.json "listing_status": "checked") or Verified by the business (18 Nov 2026: a sample is never shown as
    Verified, Featured or Business of the Week)."""
    if badge_sample(x):
        return sample_badge()
    return checked_badge() if x.get("checked") else verified()


def photo_inner(x, i=0, fallback_icon="store", alt="", eager=False):
    """Inside an image slot: the business's sample photo (data/claimed.json "images") or the placeholder icon."""
    c = x.get("claimed") or {}
    imgs = c.get("images") or []
    if not imgs:
        return icon(fallback_icon)
    src = imgs[i % len(imgs)]
    alts = c.get("image_alts") or []
    if not alt and i % len(imgs) < len(alts):
        alt = alts[i % len(imgs)]
    load = 'fetchpriority="high"' if eager else 'loading="lazy" decoding="async"'
    return f'<img src="{src}" alt="{e(alt)}" width="1200" height="800" {load}>'


def offer_photo_index(x, o):
    """Each offer of a business gets its own photo (images 2, 3, 1, ...)."""
    offers = (x.get("claimed") or {}).get("offers") or []
    return 1 + (offers.index(o) if o in offers else 0)


def ph_cls(x, base="ph"):
    return base + (" ph--photo" if (x.get("claimed") or {}).get("images") else "")


def logo_slot(c, cls="ph ph--logo", alt=""):
    """The business's own logo or badge (claimed.json "logo"), shown whole on white instead of a cropped photo."""
    return f'<div class="{cls}"><img src="{c["logo"]}" alt="{e(alt)}" width="480" height="480" loading="lazy" decoding="async"></div>'


def today_status(c):
    if c.get("status") or not c.get("hours"):
        # Clubs and groups with a weekly schedule instead of opening hours (claimed.json "status" + "schedule")
        return f'<span class="open-now open-now--info">{c.get("status", "See times")}</span>'
    h = c["hours"][TODAY]
    if h.lower() == "closed":
        return '<span class="open-now open-now--closed">Closed today</span>'
    if h.lower().startswith("open"):          # "Open 24 hours"
        return '<span class="open-now">Open today</span>'
    if not any(ch.isdigit() for ch in h):     # "Not listed"
        return f'<span class="open-now open-now--info">Hours {h.lower()} today</span>'
    return '<span class="open-now">Open today</span>'   # just open or closed; the times are in the hours table


# ------------------------------------------------------------------ rows and cards
def row(x):
    """Directory row for a claimed listing (search, category, area)."""
    c, tier = x["claimed"], x["tier"]
    promoted = tier in TOP
    # biz-row--media: has a photo or logo, so on phones the row becomes a card with the picture on top
    media = bool(c.get("logo") or c.get("images"))
    cls = "biz-row" + (" biz-row--featured" if promoted else "") + (" biz-row--media" if media else "")
    badges = (f'<span class="badge badge--featured">{icon("star")}Featured</span>' if promoted and not badge_sample(x) else "") + (sample_badge() if badge_sample(x) else "")
    return f'''<article class="{cls}">
        {logo_slot(c) if c.get("logo") else f'<div class="{ph_cls(x)}">{photo_inner(x, 0, c.get("icon", "store"))}</div>'}
        <div class="biz-row__main">
          <span class="biz-card__cat">{e(cat_name(x["cat"]))} · <span class="muted">{e(x["sub"])}</span></span>
          <h3 class="biz-row__title"><a href="/business/{x["slug"]}.html">{name_with_tick(x)}</a></h3>
          <p class="card__meta"><span>{icon("pin")}{address_line(x)}</span><span>{e(area_name(x["area"]))}</span></p>
          <p class="card__text">{c["tagline"]}</p>
          <p class="card__meta">{today_status(c)}{f'<span>{icon("tag")}{len(c["offers"])} offer{"s" if len(c["offers"]) != 1 else ""}</span>' if c["offers"] and tier in PAID else ""}{f'<span>{icon("calendar")}{len(c["events"])} upcoming</span>' if c["events"] else ""}</p>
        </div>
        <div class="biz-row__side"><div class="cluster">{badges}</div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:{x["phone_raw"][0]}">{icon("phone")}Call</a></div>
      </article>'''


def ends_label(o):
    """'Ends 30 Nov', 'Ends in 6 days', 'Ends today' or 'Ended 8 Nov' (from hub_data.offer_dates)."""
    if o["end"] is None:
        return "No end date"
    d = o["days_left"]
    if d < 0:
        return f'Ended {o["ends_short"]}'
    if d == 0:
        return "Ends today"
    if d == 1:
        return "Ends tomorrow"
    return f"Ends in {d} days" if d <= SOON_DAYS else f'Ends {o["ends_short"]}'


def unlock_box(x, o, note=True):
    """Subscriber-only unlock form. The code is never in the page: WordPress returns it after the reader confirms."""
    _uid[0] += 1
    uid = f"unlock-{x['slug'][:20]}-{_uid[0]}"
    note_html = '<span class="offer__lock-note">We&rsquo;ll email you a link to confirm. Already a subscriber? Use the email you signed up with.</span>' if note else ""
    return f'''
          <div class="offer__lock">
            <p>{icon("lock", "ho-icon ho-icon--sm")}Subscriber-only. Enter your email to unlock.</p>
            <form data-unlock action="/wp-json/ho/v1/unlock" method="post">
              <input type="hidden" name="signup_source" value="subscriber-offer">
              <input type="hidden" name="offer" value="{o["slug"]}">
              <label class="ho-sr" for="{uid}">Email address</label>
              <input id="{uid}" type="email" name="email" placeholder="Your email" autocomplete="email" required>
              <button class="ho-btn ho-btn--sm ho-btn--primary" type="submit">Unlock</button>
            </form>{note_html}
          </div>'''


def offer_card(x, o, show_biz=True, heading="h3"):
    """Offer card; same markup on the Offers page, homepage and profiles. The title links to the offer's own page.
    Subscriber-only offers render locked with the unlock form."""
    c = x["claimed"]
    featured = o.get("featured")
    soon = 0 <= o["days_left"] <= SOON_DAYS
    cls = "offer" + (" offer--featured" if featured else "") + (" offer--locked" if o.get("sub") else "")
    badges = sample_badge() if badge_sample(x) else ""
    if featured and not badge_sample(x):
        badges += f'<span class="badge badge--featured">{icon("star")}Featured</span>'
    if o.get("sub"):
        badges += f'<span class="badge badge--sub">{icon("lock")}Subscriber-only</span>'
    if soon:
        badges += f'<span class="badge badge--soon">{icon("clock")}Ends soon</span>'
    badges += f'<span class="biz-card__cat">{e(cat_name(x["cat"]))}</span>'
    if o.get("sub"):
        # Never put a subscriber-only code in the page (it was only blurred). WordPress returns it after email confirmation.
        code = f'<span class="offer__code offer__code--pending" data-sub-code data-offer="{o["slug"]}">Code shown after you confirm your email</span>' if o.get("code") else "<span>No code needed</span>"
    else:
        code = f'<span class="offer__code">{o["code"]}</span>' if o.get("code") else "<span>No code needed</span>"
    lock = unlock_box(x, o) if o.get("sub") else ""
    biz = f'<a class="offer__biz" href="/business/{x["slug"]}.html">{e(x["name"])} &middot; {e(area_name(x["area"]))}</a>' if show_biz else ""
    return f'''<article class="{cls}" data-cat="{x["cat"]}" data-area="{x["area"]}" data-sub="{1 if o.get("sub") else 0}">
        <div class="offer__body">
          <div class="cluster">{badges}</div>
          <{heading} class="offer__title"><a href="{o["href"]}">{o["title"]}</a></{heading}>
          {biz}
          <p class="offer__text">{o["text"]}</p>
          <div class="offer__foot"><span class="offer__ends{" offer__ends--soon" if soon else ""}">{icon("calendar", "ho-icon ho-icon--sm")} {ends_label(o)}</span>{code}</div>
          <a class="offer__more" href="{o["href"]}">Terms and how to redeem {icon("arrow", "ho-icon ho-icon--sm")}</a>{lock}
        </div>
      </article>'''


def offer_value(o):
    """Short headline value for value-first tiles: '20% OFF', '£25 OFF', '2-FOR-1', 'FREE'. Optional "value" in claimed.json wins."""
    import re
    if o.get("value"):
        return o["value"]
    t = o["title"].replace("&pound;", "£")
    for pat, fmt in ((r"(\d+)%\s*off", "{}% OFF"), (r"£(\d+)\s*off", "£{} OFF"), (r"(\d)-for-(\d)", None),
                     (r"£(\d+)\s*cuts", "£{} CUTS"), (r"for\s*£(\d+)", "£{}")):
        m = re.search(pat, t, re.I)
        if m:
            return f"{m.group(1)}-FOR-{m.group(2)}" if fmt is None else fmt.format(m.group(1))
    if re.search(r"no joining fee", t, re.I):
        return "NO JOINING FEE"
    m = re.match(r"(?:a\s+)?free\s+(\w+)", t, re.I)          # "Free coffee …", "A free week …" → FREE COFFEE / FREE WEEK
    if m:
        return f"FREE {m.group(1).upper()}"
    m = re.match(r"(.{0,24}?\bfree)\b", t, re.I)              # "Kids eat free …", "First session free" → KIDS EAT FREE
    if m:
        return m.group(1).upper()
    return t.split(",")[0].upper()


def offer_tile(x, o):
    """Value-first offer tile: big value and the business (no photo: photos are for businesses and events)."""
    c = x["claimed"]
    lock = f'<span class="offer-tile__lock" title="Subscribers only">{icon("lock", "ho-icon ho-icon--sm")}<span class="offer-tile__lock-text">Subscribers</span></span>' if o.get("sub") else ""
    return f'''<a class="offer-tile" href="{o["href"]}">
        {lock}
        <span class="offer-tile__biz">{e(x["name"])}, {e(area_name(x["area"]))}</span>
        <strong class="offer-tile__value">{offer_value(o)}</strong>
        <span class="offer-tile__title">{o["title"]}</span>
      </a>'''


def offer_list_card(x, o):
    """Compact card for the Offers page: value, offer line, business, end date. The code, unlock form and terms
    are on the offer's own page (simplified, Nov 2026). Data attributes drive the hub.js filters."""
    soon = 0 <= o["days_left"] <= SOON_DAYS
    feat = bool(o.get("featured"))
    tags = sample_badge() if badge_sample(x) else (f'<span class="badge badge--featured">{icon("star")}Featured</span>' if feat else "")
    if o.get("sub"):
        tags += f'<span class="badge badge--sub">{icon("lock")}Subscriber-only</span>'
    if soon:
        tags += f'<span class="badge badge--soon">{icon("clock")}Ends soon</span>'
    return f'''<a class="offer-card{" offer-card--featured" if feat else ""}" href="{o["href"]}" data-cat="{x["cat"]}" data-area="{x["area"]}" data-sub="{1 if o.get("sub") else 0}">
        <span class="offer-card__top"><span class="biz-card__cat">{e(cat_name(x["cat"]))}</span>{tags}</span>
        <strong class="offer-card__value">{offer_value(o)}</strong>
        <span class="offer-card__title">{o["title"]}</span>
        <span class="offer-card__biz">{e(x["name"])} &middot; {e(area_name(x["area"]))}</span>
        <span class="offer-card__foot"><span>{icon("calendar", "ho-icon ho-icon--sm")}{ends_label(o)}</span><span class="offer-card__go">View offer {icon("arrow", "ho-icon ho-icon--sm")}</span></span>
      </a>'''


def all_offers(listings):
    """Every live offer from paid claimed listings: featured first, then by plan."""
    items = [(x, o) for x in listings if x.get("tier") in PAID for o in x["claimed"]["offers"]]
    return sorted(items, key=lambda p: (not p[1].get("featured"), H.TIER_RANK[p[0]["tier"]]))


# ------------------------------------------------------------------ offer page: /offers/{slug}/
ORIGIN = "https://harrowonline.org/hub"


def plain(s):
    return " ".join(H._unescape(s).split())


def offer_page(x, o, live):
    """(meta, body) for one offer. Shareable URL, clear terms and end date, the code (or unlock form), share links.
    Ended offers keep their page (old newsletter and social links still land) but say so, hide the code and are noindexed."""
    c = x["claimed"]
    url = ORIGIN + o["href"]
    ended = not o["live"]
    title, name, area = o["title"], x["name"], area_name(x["area"])
    end = o["end"]
    end_long = f'{end.strftime("%A")} {end.day} {end.strftime("%B %Y")}' if end else ""
    sample = is_sample(x)
    soon = 0 <= o["days_left"] <= SOON_DAYS

    # Simplified layout (client feedback, Nov 2026): photo + offer side by side, one code box, a small share row;
    # terms folded away, one compact business row, small offer tiles below.
    status = ""
    if ended:
        status = '<span class="badge badge--unclaimed">Ended</span>'
    elif o.get("sub"):
        status = f'<span class="badge badge--sub">{icon("lock")}Subscriber-only</span>'
    elif soon:
        status = f'<span class="badge badge--soon">{icon("clock")}Ends soon</span>'

    if ended:
        left = "This offer has ended"
    elif end is None:
        left = ""
    elif o["days_left"] == 0:
        left = "Last day today"
    else:
        left = f'{o["days_left"]} day{"s" if o["days_left"] != 1 else ""} left'

    # The voucher: code + copy, "no code needed", the unlock form, or "ended"
    if ended:
        voucher_main = f'''<p class="voucher__state">This offer has ended</p>
          <p class="small mb-0">It ran until {end.day} {end.strftime("%B %Y")}. Any code for it no longer works.</p>
          <a class="ho-btn ho-btn--primary mt-4" href="/offers.html">See current offers</a>'''
    elif o.get("sub"):
        what = "Your code appears here once you confirm your email." if o.get("code") else "Full details appear here once you confirm your email."
        # hub.js section 6 swaps this for the code when the reader is a known subscriber (confirm-link cookie or signed in)
        voucher_main = f'''<div data-sub-gate data-offer="{o["slug"]}" data-has-code="{1 if o.get("code") else 0}"><p class="small mb-0">{what}</p>{unlock_box(x, o, note=False)}</div>'''
    elif o.get("code"):
        voucher_main = f'''<div class="voucher__row"><span class="voucher__code">{o["code"]}</span>
          <button class="ho-btn ho-btn--dark" type="button" data-copy="{o["code"]}" data-copied="Code copied">Copy code</button></div>'''
    else:
        voucher_main = '<p class="voucher__state">No code needed</p>'
    redeem = "" if ended else f'<p class="voucher__how">{o.get("redeem", "Show this page when you pay.")}</p>'
    voucher = f'''<div class="voucher voucher--simple{" voucher--ended" if ended else ""}" id="redeem">
          {voucher_main}
          {redeem}
        </div>'''

    terms = "".join(f"<li>{t}</li>" for t in o.get("terms", []))
    terms += (f'<li>{"Ended" if ended else "Ends"} at 11.59pm on {end_long}.</li>' if end
              else "<li>No end date has been set. The offer runs until the business ends it.</li>")
    if o.get("code") and not o.get("sub"):
        terms += f'<li>Use the code <strong>{o["code"]}</strong> where asked.</li>'
    terms += f"<li>Run by {e(name)}. The business can end the offer early if it can no longer honour it.</li>"

    more = [(y, p) for y, p in live if p is not o]
    more.sort(key=lambda yp: (yp[0] is not x, yp[0]["cat"] != x["cat"], not yp[1].get("featured")))
    more_html = ""
    if more:
        tiles = "".join(offer_tile(y, p) for y, p in more[:4])
        more_html = f'''
    <section class="offer-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More Local Offers</h2>
        <a class="link-arrow" href="/offers.html">All offers {icon("arrow")}</a>
      </div>
      <div class="offer-tiles offer-tiles--4">{tiles}</div>
    </section>'''

    share = quote(f'{plain(title)} at {plain(name)}: {url}', safe="")
    share_row = "" if ended else f'''
        <div class="share-row" aria-label="Share this offer">
          <span>Share</span>
          <a href="https://wa.me/?text={share}" rel="noopener" target="_blank" aria-label="Share on WhatsApp">{icon("whatsapp")}</a>
          <a href="https://www.facebook.com/sharer/sharer.php?u={quote(url, safe="")}" rel="noopener" target="_blank" aria-label="Share on Facebook">{icon("facebook")}</a>
          <a href="mailto:?subject={quote(plain(title))}&amp;body={quote(url, safe="")}" aria-label="Share by email">{icon("mail")}</a>
          <button type="button" data-copy="{url}" data-copied="Link copied">Copy link</button>
        </div>'''

    biz_row = f'''
    <a class="offer-biz" href="/business/{x["slug"]}.html">
      <span class="{ph_cls(x, "ph ph--square offer-biz__pic")}">{photo_inner(x, 0, c.get("icon", "store"))}</span>
      <span class="offer-biz__main"><strong>{e(name)}</strong><span>{address_line(x, with_area=True)}</span></span>
      <span class="offer-biz__go">View business {icon("arrow", "ho-icon ho-icon--sm")}</span>
    </a>'''

    ended_note = f'''
<div class="ho-container mt-4">
  <div class="notice notice--warn">{icon("alert")}
    <div><p><strong>This offer has ended.</strong> It ran until {end_long} and has come off Local Offers and the business profile.</p>
    <p><a href="/offers.html">See current offers in Harrow</a></p></div>
  </div>
</div>''' if ended else ""
    pub = "Made-up sample for the preview." if sample else "Checked by Harrow Online."
    still = "" if ended or not left else f" &middot; {left}"
    meta_line = (f'{icon("calendar", "ho-icon ho-icon--sm")}{"Ended" if ended else "Ends"} {end.day} {end.strftime("%b %Y")}{still}' if end
                 else f'{icon("calendar", "ho-icon ho-icon--sm")}No end date')

    body = f'''<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="/">Hub</a></li>
    <li><a href="/offers.html">Local Offers</a></li>
    <li><a href="/offers.html?cat={x["cat"]}">{e(cat_name(x["cat"]))}</a></li>
    <li><span aria-current="page">{title}</span></li>
  </ol>
</nav>
{ended_note}
<article class="offer-page offer-page--simple{" offer-page--ended" if ended else ""}">
  <div class="ho-container offer-simple">
    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags">{status}{sample_badge() if sample else ""}<span class="biz-card__cat">{e(cat_name(x["cat"]))}</span></div>
      <p class="offer-simple__value">{offer_value(o)}</p>
      <h1 class="offer-simple__title">{title}</h1>
      <p class="offer-simple__biz"><a href="/business/{x["slug"]}.html">{e(name)}</a> &middot; {e(area)}</p>
      <p class="offer-simple__text">{o["text"]}</p>
    </div>
    <div class="offer-simple__side">
      {voucher}
      <p class="offer-simple__meta">{meta_line} &middot; <a href="#terms">Terms</a></p>
    </div>{share_row}
  </div>

  <div class="ho-container offer-simple__below">
    <details class="offer-terms" id="terms">
      <summary>Terms and conditions</summary>
      <ul class="terms-list">{terms}</ul>
      <p class="small muted mb-0">{pub} Offers are run by the business, so check the terms with them before you buy. <a class="report-link" href="/report.html">{icon("flag")}Report a problem</a></p>
    </details>
{biz_row}
{more_html}
  </div>
</article>
'''
    import json
    node = {
        "@type": "Offer", "@id": url + "#offer", "name": plain(title), "description": plain(o["text"]), "url": url,
        **({"validThrough": end.isoformat() + "T23:59:00"} if end else {}), "availability": "https://schema.org/" + ("Discontinued" if ended else "InStock"),
        "offeredBy": {"@type": "LocalBusiness", "name": plain(name), "url": f'{ORIGIN}/business/{x["slug"]}/'},
    }
    ld = json.dumps({"@context": "https://schema.org", "@graph": [node]}, indent=2, ensure_ascii=False)
    body += f'\n<script type="application/ld+json">\n{ld}\n</script>\n'
    when = f'{"Ended" if ended else "Ends"} {end.day} {end.strftime("%B %Y")}.' if end else "No end date."
    desc = plain(f'{title} at {name}, {area}. {o["text"]} {when}')
    full = plain(f"{title} at {name}")
    # Keep the page title within 70 characters: drop "at {business}" when the pair is too long
    meta = {"title": full if len(full) <= 50 else plain(title), "description": desc, "nav": "offers", "hubnav": "offers",
            "body": "page-offer", "schema": "ItemPage", "path": o["href"]}
    if ended or sample:
        meta["robots"] = "noindex, follow"   # ended offers leave the index; samples never enter it
    return meta, body


def offer_pages(listings):
    """[(meta, body)] for every offer on a paid plan, live and ended. build.py renders and writes them."""
    live = all_offers(listings)
    out = []
    for x in listings:
        if x.get("tier") in PAID and x.get("claimed"):
            for o in x["claimed"]["offers"] + x["claimed"].get("ended_offers", []):
                out.append(offer_page(x, o, live))
    return out


def event_row(ev, sample=False):
    rep = f'<span class="badge">{icon("repeat")}Weekly class</span>' if ev.get("class") else ""
    feat = sample_badge() if (sample and SHOW_SAMPLE_TAG) else (f'<span class="badge badge--featured">Featured</span>' if ev.get("featured") else "")
    return f'''<article class="event-row{" event-row--featured" if ev.get("featured") else ""}">
          <time class="date-tile" datetime="2026-{ {"Oct": 10, "Nov": 11, "Dec": 12}.get(ev["m"], 11):02d}-{int(ev["d"]):02d}"><span class="date-tile__m">{ev["m"]}</span><span class="date-tile__d">{ev["d"]}</span><span class="date-tile__w">{ev["w"]}</span></time>
          <div class="ph">{icon("repeat" if ev.get("class") else "calendar")}</div>
          <div>
            <div class="cluster mb-2">{feat}{rep}</div>
            <h3 class="event-row__title"><a href="{ev["href"]}">{ev["title"]}</a></h3>
            <p class="card__meta"><span>{icon("clock")}{ev["when"]}</span></p>
          </div>
          <div class="event-row__side"><strong>{ev["price"]}</strong></div>
        </article>'''


# ------------------------------------------------------------------ profile
def profile_body(x, related_html, crumbs_html):
    c, tier = x["claimed"], x["tier"]
    name, area, cat = x["name"], x["area"], x["cat"]
    top = tier in TOP
    maps = f'https://www.google.com/maps/search/?api=1&query={quote(H._unescape(name) + " " + x["postcode"])}' if has_address(c) else ""
    sample = is_sample(x)
    if sample and SHOW_SAMPLE_TAG:
        # One clear label only: no Verified, Featured or Business of the Week on a sample.
        badges = sample_badge()
    else:
        badges = tier_badge(tier)   # status shows as the icon after the name
        if c.get("botw"):
            badges = f'<span class="botw">{icon("star")}Business of the Week</span>' + badges

    if top and c.get("cover"):
        fit = c.get("cover_bg")
        style = f' style="background:{fit}"' if fit else ""
        cover = (f'<div class="ho-container profile-cover"><div class="ph ph--banner ph--photo{" ph--contain" if fit else ""}"{style}>'
                 f'<img src="{c["cover"]}" alt="{e(c.get("cover_alt", name))}" width="1600" height="600" fetchpriority="high"></div></div>')
    elif top and (sample or c.get("images")):
        cover = f'<div class="ho-container profile-cover"><div class="{ph_cls(x, "ph ph--banner ph--dark")}">{photo_inner(x, 0, c.get("icon", "store"), name)}</div></div>'
    else:
        cover = ""

    # Photos: Basic shows 1; paid plans a gallery (max 5 tiles, "+N more" on the last)
    n = c.get("photos", 1) if tier in PAID else 1
    if not sample and c.get("images") and tier in PAID:
        # Real business: its own photos, each shown once (no "+N more" padding)
        imgs = c["images"][:5]
        tiles = "".join(f'<div class="{ph_cls(x)}">{photo_inner(x, k)}</div>' for k in range(len(imgs)))
        gallery = f'<div class="gallery gallery--{len(imgs)} mb-6" aria-label="Photos">{tiles}</div>'
    elif n >= 3:
        tiles = [f'<div class="{ph_cls(x)}">{photo_inner(x, k + 1, c.get("icon", "store"))}</div>' for k in range(min(n, 5))]
        if n > 5:
            tiles[-1] = f'<div class="gallery__more"><div class="{ph_cls(x)}">{photo_inner(x, 2, c.get("icon", "store"))}</div><span>+{n - 4} photos</span></div>'
        gallery = f'<div class="gallery mb-6" aria-label="Photos">{"".join(tiles)}</div>'
    elif not sample:
        gallery = ""   # real business with no photos yet: no placeholder
    else:
        gallery = f'<div class="ph ph--wide mb-6">{icon(c.get("icon", "store"))}</div>'

    desc = "".join(f"<p>{p}</p>" for p in c["description"])
    sections = [f'<section class="profile-section"><h2>About {e(name)}</h2><div class="profile-desc">{desc}</div></section>']

    offers = c["offers"] if tier in PAID else []
    if offers:
        cards = "".join(offer_card(x, o, show_biz=False) for o in offers)
        sections.append(f'<section class="profile-section"><div class="sec-head"><h2 class="mb-0">Local Offers</h2><a class="link-arrow" href="/offers.html">All offers {icon("arrow")}</a></div><div class="stack">{cards}</div></section>')

    if c["events"]:
        rows = "".join(event_row(ev, sample) for ev in c["events"])
        sections.append(f'<section class="profile-section"><div class="sec-head"><h2 class="mb-0">Upcoming events and classes</h2><a class="link-arrow" href="/events.html">What&rsquo;s On {icon("arrow")}</a></div><div>{rows}</div></section>')

    if has_address(c):
        where = f'<li>{icon("pin")}<div><address>{address_line(x, with_area=True)}</address><a class="small" href="{maps}" target="_blank" rel="noopener">Get directions</a></div></li>'
    else:
        where = f'<li>{icon("pin")}<div><span>{address_line(x)}</span><p class="small muted mb-0">Works at customers&rsquo; homes and premises. No shop or office to visit.</p></div></li>'
    info = [where, f'<li>{icon("phone")}<a href="tel:{x["phone_raw"][0]}">{e(c["phone"])}</a></li>']
    for ph in c.get("phones_extra", []):
        info.append(f'<li>{icon("phone")}<a href="tel:{"".join(ch for ch in ph if ch.isdigit() or ch == "+")}">{e(ph)}</a></li>')
    for w in x.get("web", [])[:1]:
        info.append(f'<li>{icon("globe")}<a href="{e(w)}" target="_blank" rel="noopener">{e(w.split("//", 1)[-1].rstrip("/"))}</a></li>')
    for m in x.get("email", [])[:1]:
        info.append(f'<li>{icon("mail")}<a href="mailto:{e(m)}">{e(m)}</a></li>')
    for loc in c.get("other_locations", []):
        lmaps = f'https://www.google.com/maps/search/?api=1&query={quote(loc["name"] + " " + loc["address"])}'
        info.append(f'<li>{icon("pin")}<div><span class="small muted">{loc["label"]}</span><address>{loc["name"]}, {loc["address"]}</address><a class="small" href="{lmaps}" target="_blank" rel="noopener">Get directions</a></div></li>')
    sections.append(f'''<section class="profile-section"><h2>Contact and location</h2>
      <div class="grid grid-2"><ul class="info-list">{"".join(info)}</ul>
      {f'<a class="ph ph--map" href="{maps}" target="_blank" rel="noopener" aria-label="Open {e(name)} in Google Maps">{icon("pin")}</a>' if maps else ""}</div></section>''')

    # In the news: only real Harrow Online articles tagged with this business (each needs its published URL).
    # In WordPress: posts linked to the listing by the editor, newest first. Nothing linked → no section.
    news = [n_ for n_ in c.get("news", []) if str(n_.get("url", "")).startswith("https://harrowonline.org/")]
    if news:
        items = "".join(f'''<article class="news-link"><div class="ph">{icon("news")}</div><div><span class="news-link__cat">{n_["cat"]}</span>
          <h3 class="news-link__title"><a href="{n_["url"]}">{n_["title"]}</a></h3><p class="news-link__date">{n_["date"]}</p></div></article>''' for n_ in news)
        sections.append(f'<section class="profile-section"><h2>In the news</h2>{items}</section>')

    if not top:  # brief 01, 6.2: paid Premium profiles show no competitor or related listings
        sections.append(f'''<section class="profile-section"><div class="sec-head"><h2 class="mb-0">More {e(cat_name(cat).lower())} in {e(area_name(area))}</h2>
          <a class="link-arrow" href="/category/{cat}.html">All {e(cat_name(cat).lower())} in Harrow {icon("arrow")}</a></div>{related_html}</section>''')

    today_cls = ' class="is-today"'
    if c.get("schedule"):
        hours_title = c.get("schedule_title", "When we meet")
        hours = "".join(f'<tr><th scope="row">{d}</th><td>{h}</td></tr>' for d, h in c["schedule"])
        hours_note = f'<p class="small muted mt-3 mb-0">{c["schedule_note"]}</p>' if c.get("schedule_note") else ""
    else:
        hours_title = "Opening hours"
        hours_note = f'<p class="small muted mt-3 mb-0">{c["hours_note"]}</p>' if c.get("hours_note") else ""
        hours = "".join(f'<tr{today_cls if i == TODAY else ""}><th scope="row">{d}</th><td>{h}</td></tr>' for i, (d, h) in enumerate(zip(DAYS, c["hours"])))
    social = ""
    if tier in PAID and c.get("social"):
        urls = c.get("social_urls", {})
        links = "".join(f'<a href="{e(urls[s])}" target="_blank" rel="noopener" aria-label="{SOCIAL_LABEL[s]}">{icon(s)}</a>' if s in urls
                        else f'<a href="#" aria-label="{SOCIAL_LABEL[s]}">{icon(s)}</a>' for s in c["social"])
        social = f'<div class="profile-card__follow"><h3 class="profile-card__h">Follow</h3><div class="social-row">{links}</div></div>'

    trust = (f"""<p class="profile-card__trust">{icon("alert", "ho-icon ho-icon--sm")}<span><strong>Sample listing.</strong> Made up for the preview; removed before launch.</span></p>""" if sample
             else f"""<p class="profile-card__trust">{icon("check" if x.get("checked") else "verified", "ho-icon ho-icon--sm")}<span>{c.get("verified_note") or f"Verified by the business since {c['claimed']}."}</span></p>""")
    aside = f'''<aside class="sticky profile-aside">
    <div class="panel profile-card">
      <h2 class="panel__title">{hours_title}</h2>
      <p class="mb-3">{today_status(c)}</p>
      <table class="hours"><caption class="ho-sr">{hours_title}</caption><tbody>{hours}</tbody></table>{hours_note}
      <a class="ho-btn ho-btn--primary ho-btn--block mt-4" href="tel:{x["phone_raw"][0]}">{icon("phone")}Call {e(c["phone"])}</a>
      {social}
      {trust}
    </div>
  </aside>'''

    return f'''{crumbs_html}
{cover}
<section class="profile-head profile-head--card ho-container{" profile-head--premium" if top else ""}">
  <div class="profile-head__row">
    {f'<span class="mono mono--lg mono--logo"><img src="{c["logo"]}" alt="{e(name)} badge" width="480" height="480"></span>' if c.get("logo") else f'<span class="mono mono--lg{" mono--red" if top else ""}" aria-hidden="true">{e(initials(name))}</span>'}
    <div class="profile-head__main">
      <div class="cluster">{badges}</div>
      <h1 class="profile-head__title">{name_with_tick(x, name, linked=False)}</h1>
      {f'<p class="profile-head__summary">{c["tagline"]}</p>' if c.get("tagline") else ""}
      <p class="profile-head__meta">
        <a href="/category/{cat}.html">{icon("tag")}{e(cat_name(cat))}</a>
        <span>{e(x["sub"])}</span>
        <a href="/area/{area}.html">{icon("pin")}{e(area_name(area))}</a>
        {today_status(c)}
      </p>
      <div class="profile-head__actions">
        <a class="ho-btn ho-btn--primary" href="tel:{x["phone_raw"][0]}">{icon("phone")}Call</a>
        {f'<a class="ho-btn ho-btn--outline" href="{e(x["web"][0])}" target="_blank" rel="noopener">{icon("globe")}Website</a>' if x.get("web") else ""}
        {f'<a class="ho-btn ho-btn--outline" href="{maps}" target="_blank" rel="noopener">{icon("map")}Directions</a>' if maps else ""}
        <button class="ho-btn ho-btn--outline" type="button" data-copy>{icon("external")}Share</button>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside profile-layout">
  <div class="profile-main">
    {gallery}
    {"".join(sections)}
    <p class="mt-4"><a class="report-link" href="/report.html?listing={x["slug"]}">{icon("flag")}Report a mistake on this listing</a></p>
  </div>
  {aside}
</div>'''
