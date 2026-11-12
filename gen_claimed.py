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
        return f'<span class="badge badge--premium">{icon("star")}Premium</span>'
    return ""


def verified():
    return f'<span class="badge badge--verified">{icon("verified")}Verified</span>'


def is_sample(x):
    """Every business in data/claimed.json is made up for the preview (hub_data gives them ids "sample-…")."""
    return str(x.get("id", "")).startswith("sample-")


def sample_badge():
    return f'<span class="badge badge--sample" title="Made-up business for the preview. Removed before launch.">{icon("alert")}Sample</span>'


def status_badges(x):
    """Client rule (12 Nov 2026): samples never show Verified, Featured, Premium, Local Partner or Business of the Week.
    Real claimed listings keep the verified + plan badges."""
    if is_sample(x):
        return sample_badge()
    return verified() + tier_badge(x["tier"])


def today_status(c):
    h = c["hours"][TODAY]
    return ('<span class="open-now open-now--closed">Closed today</span>' if h.lower() == "closed"
            else f'<span class="open-now">Open today, {h}</span>')


# ------------------------------------------------------------------ rows and cards
def row(x):
    """Directory row for a claimed listing (search, category, area)."""
    c, tier = x["claimed"], x["tier"]
    promoted = tier in TOP and not is_sample(x)
    cls = "biz-row" + (" biz-row--featured" if promoted else "")
    badges = (f'<span class="badge badge--featured">{icon("star")}Featured</span>' if promoted else "") + status_badges(x)
    return f'''<article class="{cls}">
        <div class="ph">{icon(c.get("icon", "store"))}</div>
        <div class="biz-row__main">
          <span class="biz-card__cat">{e(cat_name(x["cat"]))} · <span class="muted">{e(x["sub"])}</span></span>
          <h3 class="biz-row__title"><a href="/business/{x["slug"]}.html">{e(x["name"])}</a></h3>
          <p class="card__meta"><span>{icon("pin")}{c["address"]}, {e(x["postcode"])}</span><span>{e(area_name(x["area"]))}</span></p>
          <p class="card__text">{c["tagline"]}</p>
          <p class="card__meta">{today_status(c)}{f'<span>{icon("tag")}{len(c["offers"])} offer{"s" if len(c["offers"]) != 1 else ""}</span>' if c["offers"] and tier in PAID else ""}{f'<span>{icon("calendar")}{len(c["events"])} upcoming</span>' if c["events"] else ""}</p>
        </div>
        <div class="biz-row__side"><div class="cluster">{badges}</div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:{x["phone_raw"][0]}">{icon("phone")}Call</a></div>
      </article>'''


def offer_card(x, o, show_biz=True, heading="h3"):
    """Offer card; same markup as the Offers page. Subscriber-only offers render locked with the unlock form."""
    _uid[0] += 1
    c = x["claimed"]
    featured = o.get("featured") and not is_sample(x)
    cls = "offer" + (" offer--featured" if featured else "") + (" offer--locked" if o.get("sub") else "")
    badges = sample_badge() if is_sample(x) else ""
    if featured:
        badges += f'<span class="badge badge--featured">{icon("star")}Featured</span>'
    if o.get("sub"):
        badges += f'<span class="badge badge--sub">{icon("lock")}Subscriber-only</span>'
    badges += f'<span class="biz-card__cat">{e(cat_name(x["cat"]))}</span>'
    if o.get("sub"):
        # Never put a subscriber-only code in the page (it was only blurred). WordPress returns it after email confirmation.
        code = '<span class="offer__code offer__code--pending">Code shown after you confirm your email</span>' if o.get("code") else "<span>No code needed</span>"
    else:
        code = f'<span class="offer__code">{o["code"]}</span>' if o.get("code") else "<span>No code needed</span>"
    lock = ""
    if o.get("sub"):
        uid = f"unlock-{x['slug'][:20]}-{_uid[0]}"
        lock = f'''
          <div class="offer__lock">
            <p>{icon("lock", "ho-icon ho-icon--sm")}Subscriber-only. Enter your email to unlock.</p>
            <form data-unlock action="/wp-json/ho/v1/unlock" method="post">
              <input type="hidden" name="signup_source" value="subscriber-offer">
              <label class="ho-sr" for="{uid}">Email address</label>
              <input id="{uid}" type="email" name="email" placeholder="Your email" autocomplete="email" required>
              <button class="ho-btn ho-btn--sm ho-btn--primary" type="submit">Unlock</button>
            </form>
            <span class="offer__lock-note">We&rsquo;ll email you a link to confirm. Already a subscriber? Use the email you signed up with.</span>
          </div>'''
    biz = f'<a class="offer__biz" href="/business/{x["slug"]}.html">{e(x["name"])} &middot; {e(area_name(x["area"]))}</a>' if show_biz else ""
    return f'''<article class="{cls}">
        <div class="ph">{icon(o.get("icon", c.get("icon", "tag")))}</div>
        <div class="offer__body">
          <div class="cluster">{badges}</div>
          <{heading} class="offer__title">{o["title"]}</{heading}>
          {biz}
          <p class="offer__text">{o["text"]}</p>
          <div class="offer__foot"><span>{icon("calendar", "ho-icon ho-icon--sm")} Ends {o["ends"]}</span>{code}</div>{lock}
        </div>
      </article>'''


def all_offers(listings):
    """Every offer from paid claimed listings: featured first, then in data order."""
    items = [(x, o) for x in listings if x.get("tier") in PAID for o in x["claimed"]["offers"]]
    return sorted(items, key=lambda p: (not p[1].get("featured"), H.TIER_RANK[p[0]["tier"]]))


def event_row(ev, sample=False):
    rep = f'<span class="badge">{icon("repeat")}Weekly class</span>' if ev.get("class") else ""
    feat = sample_badge() if sample else (f'<span class="badge badge--featured">Featured</span>' if ev.get("featured") else "")
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
    maps = f'https://www.google.com/maps/search/?api=1&query={quote(name + " " + x["postcode"])}'
    sample = is_sample(x)
    if sample:
        # One clear label, plus which plan layout this sample demonstrates (no Verified / plan / BotW claims).
        badges = sample_badge() + f'<span class="badge badge--outline">{H.TIER_LABEL[tier]} plan layout</span>'
    else:
        badges = verified() + tier_badge(tier)
        if c.get("botw"):
            badges = f'<span class="botw">{icon("star")}Business of the Week</span>' + badges

    cover = f'<div class="ho-container profile-cover"><div class="ph ph--banner ph--dark">{icon(c.get("icon", "store"))}</div></div>' if top else ""

    # Photos: Basic shows 1; paid plans a gallery (max 5 tiles, "+N more" on the last)
    n = c.get("photos", 1) if tier in PAID else 1
    if n >= 3:
        tiles = [f'<div class="ph">{icon(c.get("icon", "store"))}</div>' for _ in range(min(n, 5))]
        if n > 5:
            tiles[-1] = f'<div class="gallery__more"><div class="ph">{icon(c.get("icon", "store"))}</div><span>+{n - 4} photos</span></div>'
        gallery = f'<div class="gallery mb-6" aria-label="Photos">{"".join(tiles)}</div>'
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

    info = [
        f'<li>{icon("pin")}<div><address>{c["address"]}, {e(area_name(area))}, {e(x["postcode"])}</address><a class="small" href="{maps}" target="_blank" rel="noopener">Get directions</a></div></li>',
        f'<li>{icon("phone")}<a href="tel:{x["phone_raw"][0]}">{e(c["phone"])}</a></li>',
    ]
    sections.append(f'''<section class="profile-section"><h2>Contact and location</h2>
      <div class="grid grid-2"><ul class="info-list">{"".join(info)}</ul>
      <a class="ph ph--map" href="{maps}" target="_blank" rel="noopener" aria-label="Open {e(name)} in Google Maps">{icon("pin")}</a></div></section>''')

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
    hours = "".join(f'<tr{today_cls if i == TODAY else ""}><th scope="row">{d}</th><td>{h}</td></tr>' for i, (d, h) in enumerate(zip(DAYS, c["hours"])))
    social = ""
    if tier in PAID and c.get("social"):
        links = "".join(f'<a href="#" aria-label="{SOCIAL_LABEL[s]}">{icon(s)}</a>' for s in c["social"])
        social = f'<div class="mt-5"><h3 class="panel__title">Follow</h3><div class="social-row">{links}</div></div>'

    aside = f'''<aside class="sticky">
    <div class="panel">
      <h2 class="panel__title">Opening hours</h2>
      <p class="mb-4">{today_status(c)}</p>
      <table class="hours"><caption class="ho-sr">Opening hours</caption><tbody>{hours}</tbody></table>
      <a class="ho-btn ho-btn--primary ho-btn--block mt-5" href="tel:{x["phone_raw"][0]}">{icon("phone")}Call {e(c["phone"])}</a>
      {social}
    </div>
    {f"""<div class="notice notice--sample">{icon("alert")}<p><strong>Sample listing.</strong> A made-up business showing the {H.TIER_LABEL[tier]} plan layout. It is not verified and will be removed before launch.</p></div>""" if sample else f"""<div class="panel panel--soft">
      <p class="small mb-2">{verified()}</p>
      <p class="small muted mb-0">Claimed and checked by the owner since {c["claimed"]}. Details are kept up to date by the business.</p>
    </div>"""}
    <div class="panel mt-4">
      <h3 class="panel__title">Get Harrow&rsquo;s best offers</h3>
      <p class="small muted">Local Offers and what&rsquo;s on, every morning in the Harrow Online Newsletter.</p>
      <a class="link-arrow" href="https://harrowonline.org/newsletter/">Sign up free {icon("arrow")}</a>
    </div>
  </aside>'''

    return f'''{crumbs_html}
{cover}
<section class="profile-head ho-container{" profile-head--premium" if top else ""}">
  <div class="profile-head__row">
    <span class="mono mono--lg{" mono--red" if top else ""}" aria-hidden="true">{e(initials(name))}</span>
    <div class="profile-head__main">
      <div class="cluster">{badges}</div>
      <h1 class="profile-head__title">{e(name)}</h1>
      <p class="profile-head__meta">
        <a href="/category/{cat}.html">{icon("tag")}{e(cat_name(cat))}</a>
        <span>{e(x["sub"])}</span>
        <a href="/area/{area}.html">{icon("pin")}{e(area_name(area))}</a>
        {today_status(c)}
      </p>
      <div class="profile-head__actions">
        <a class="ho-btn ho-btn--primary" href="tel:{x["phone_raw"][0]}">{icon("phone")}Call</a>
        <a class="ho-btn ho-btn--outline" href="{maps}" target="_blank" rel="noopener">{icon("map")}Directions</a>
        <button class="ho-btn ho-btn--outline" type="button" data-copy>{icon("external")}Share</button>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside">
  <div>
    {gallery}
    {"".join(sections)}
    <p class="mt-4"><a class="report-link" href="/report.html?listing={x["slug"]}">{icon("flag")}Report a mistake on this listing</a></p>
  </div>
  {aside}
</div>'''
