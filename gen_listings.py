"""
Generates the data-driven parts of the Hub from data/listings.json (via hub_data.py):

  site/business/{slug}.html     one profile per listing (all imported listings are Unclaimed: noindex, follow)
  site/category/{slug}.html     category archives
  site/area/{slug}.html         area archives
  site/data/hub-index.json      compact index used by the directory search (assets/js/hub.js)
  src/partials/gen-*.html       category/area tiles and <option> lists that hand-written pages include

Called by build.py before the hand-written pages are built.
"""
import json
from collections import Counter, defaultdict
from html import escape as e
from urllib.parse import quote

import hub_data as H

PARTIALS = H.ROOT / "src/partials"

CAT_INTRO = {
    "restaurants": "From family-run curry houses on Station Road to Pinner's high-street bistros, here are Harrow's restaurants.",
    "hair-and-beauty": "Barbers, hair salons, nail bars and beauty clinics across the borough.",
    "doctors-dentists-and-clinics": "GP surgeries, dentists, opticians, physios and private clinics in Harrow.",
    "trades-and-home-services": "Plumbers, electricians, builders, decorators and other trades that serve Harrow homes.",
}


def initials(name):
    words = [w for w in "".join(c if c.isalnum() or c == " " else " " for c in name).split() if w]
    return ("".join(w[0] for w in words[:2]) or "?").upper()


def cat_name(slug):
    return H.CAT_BY_SLUG[slug][1]


def area_name(slug):
    return H.AREA_BY_SLUG[slug][1]


def icon(name, cls="ho-icon"):
    return f'<svg class="{cls}"><use href="#i-{name}"/></svg>'


def crumbs(items):
    lis = []
    for i, (label, href) in enumerate(items):
        if i == len(items) - 1:
            lis.append(f'<li><span aria-current="page">{e(label)}</span></li>')
        else:
            lis.append(f'<li><a href="{href}">{e(label)}</a></li>')
    return f'<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol>{"".join(lis)}</ol></nav>'


def biz_row(x):
    phone = x["phone_raw"][0] if x["phone_raw"] else ""
    sub = f' · <span class="muted">{e(x["sub"])}</span>' if x["sub"] else ""
    call = f'<a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:{e(phone)}">{icon("phone")}Call</a>' if phone else ""
    addr = f'{e(x["address"])}, ' if x["address"] else ""
    return f'''<article class="biz-row biz-row--mono biz-row--unclaimed">
        <span class="mono mono--soft" aria-hidden="true">{e(initials(x["name"]))}</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">{e(cat_name(x["cat"]))}{sub}</span>
          <h3 class="biz-row__title"><a href="/business/{x["slug"]}.html">{e(x["name"])}</a></h3>
          <p class="card__meta"><span>{icon("pin")}{addr}{e(x["postcode"])}</span><span>{e(area_name(x["area"]))}</span></p>
        </div>
        <div class="biz-row__side"><span class="badge badge--unclaimed">Unclaimed</span>{call}</div>
      </article>'''


# ---------------------------------------------------------------- profile
def profile(x, related):
    from build import render
    cat, area = x["cat"], x["area"]
    maps = f'https://www.google.com/maps/search/?api=1&query={quote(x["name"] + " " + x["postcode"])}'
    info = []
    addr = ", ".join(p for p in [x["address"], area_name(area), x["postcode"]] if p)
    info.append(f'<li>{icon("pin")}<div>{e(addr)}<br><a class="small" href="{maps}" target="_blank" rel="noopener">Get directions</a></div></li>')
    for disp, raw in zip(x["phone"], x["phone_raw"]):
        info.append(f'<li>{icon("phone")}<a href="tel:{e(raw)}">{e(disp)}</a></li>')
    for w in x["web"][:1]:
        shown = w.split("//", 1)[-1].rstrip("/")
        info.append(f'<li>{icon("globe")}<a href="{e(w)}" target="_blank" rel="noopener nofollow">{e(shown)}</a></li>')
    for m in x["email"][:1]:
        info.append(f'<li>{icon("mail")}<a href="mailto:{e(m)}">{e(m)}</a></li>')

    rel_html = "".join(biz_row(r) for r in related) or '<p class="muted">No other listings in this area yet.</p>'
    body = f'''{crumbs([("Hub", "/"), (cat_name(cat), f"/category/{cat}.html"), (area_name(area), f"/area/{area}.html"), (x["name"], "")])}

<section class="profile-head ho-container">
  <div class="profile-head__row">
    <span class="mono mono--lg mono--soft" aria-hidden="true">{e(initials(x["name"]))}</span>
    <div class="profile-head__main">
      <div class="cluster"><span class="badge badge--unclaimed">Unclaimed listing</span></div>
      <h1 class="profile-head__title">{e(x["name"])}</h1>
      <p class="profile-head__meta">
        <a href="/category/{cat}.html">{icon("tag")}{e(cat_name(cat))}</a>
        {f'<span>{e(x["sub"])}</span>' if x["sub"] else ""}
        <a href="/area/{area}.html">{icon("pin")}{e(area_name(area))}</a>
      </p>
      <div class="profile-head__actions">
        {f'<a class="ho-btn ho-btn--primary" href="tel:{e(x["phone_raw"][0])}">{icon("phone")}Call</a>' if x["phone_raw"] else ""}
        {f'<a class="ho-btn ho-btn--outline" href="{e(x["web"][0])}" target="_blank" rel="noopener nofollow">{icon("globe")}Website</a>' if x["web"] else ""}
        <a class="ho-btn ho-btn--outline" href="{maps}" target="_blank" rel="noopener">{icon("map")}Directions</a>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside">
  <div>
    <section class="profile-section">
      <h2>About {e(x["name"])}</h2>
      <div class="limited-note">{icon("info")}<p class="mb-0">This business hasn&rsquo;t added a description, opening hours or photos yet. The details below come from open map data and may be out of date. <a href="/claim.html?listing={x["slug"]}">Is this your business? Claim it free</a> to update it.</p></div>
    </section>

    <section class="profile-section">
      <h2>Contact and location</h2>
      <div class="grid grid-2">
        <ul class="info-list">{"".join(info)}</ul>
        <a class="ph ph--map" href="{maps}" target="_blank" rel="noopener" aria-label="Open {e(x["name"])} in Google Maps">{icon("pin")}</a>
      </div>
    </section>

    <section class="profile-section">
      <h2>Opening hours</h2>
      <p class="muted mb-0">Not added yet. Owners can add opening hours after claiming this listing.</p>
    </section>

    <section class="profile-section">
      <div class="sec-head"><div><h2 class="mb-0">More {e(cat_name(cat).lower())} in {e(area_name(area))}</h2></div><a class="link-arrow" href="/category/{cat}.html">See all {icon("arrow")}</a></div>
      {rel_html}
    </section>

    <p class="mt-4"><a class="report-link" href="/report.html?listing={x["slug"]}">{icon("flag")}Report a mistake or ask for this listing to be removed</a></p>
  </div>

  <aside class="sticky">
    <div class="claim-box">
      <h2>Is this your business?</h2>
      <p>Claim this listing for free to add photos, opening hours and a description, and to get a verified badge.</p>
      <ul class="ticks"><li>Free forever on the Basic plan</li><li>Ranked above unclaimed listings</li><li>Add Local Offers and events on paid plans</li></ul>
      <a class="ho-btn ho-btn--primary ho-btn--block" href="/claim.html?listing={x["slug"]}">Claim this business</a>
      <p class="xsmall mt-4 mb-0" style="color:rgba(255,255,255,.6)">Takes about 2 minutes. We check every claim before it goes live.</p>
    </div>
    <div class="panel mt-4">
      <h3 class="panel__title">Get Harrow&rsquo;s best offers</h3>
      <p class="small muted">Local Offers and what&rsquo;s on, every morning in the Harrow Online Newsletter.</p>
      <a class="link-arrow" href="https://harrowonline.org/newsletter/">Sign up free {icon("arrow")}</a>
    </div>
  </aside>
</div>'''
    meta = {
        "title": f'{x["name"]} in {area_name(area)}',
        "description": f'{x["name"]}, {cat_name(cat).lower()} in {area_name(area)}, Harrow. Address, phone and directions on Harrow Online Hub.',
        "robots": "noindex, follow",
        "hubnav": "directory",
    }
    return render(meta, body)


# ---------------------------------------------------------------- archives
def archive(kind, slug, items, counts_other):
    from build import render
    if kind == "cat":
        name = cat_name(slug)
        title = f"{name} in Harrow"
        lead = CAT_INTRO.get(slug, f"{len(items):,} {name.lower()} listed across Harrow. Filter by area, or claim your listing free.")
        crumb = crumbs([("Hub", "/"), (name, "")])
        chips_title = "Areas"
        chips = "".join(f'<a class="chip" href="/search.html?cat={slug}&amp;area={a}">{e(area_name(a))} <span class="count">{n}</span></a>' for a, n in counts_other)
        data_attr = f'data-cat="{slug}"'
        other_filter = f'''<div class="field"><label for="f-area">Area</label><select class="select" id="f-area" name="area"><option value="">All areas</option>{gen_area_options()}</select></div>'''
        hidden = f'<input type="hidden" name="cat" value="{slug}">'
    else:
        name = area_name(slug)
        title = f"Businesses in {name}"
        lead = f"{len(items):,} local businesses in {name}: shops, places to eat, trades and services. Browse by category below."
        crumb = crumbs([("Hub", "/"), ("Areas", "/#areas"), (name, "")])
        chips_title = "Categories"
        chips = "".join(f'<a class="chip" href="/search.html?area={slug}&amp;cat={c}">{e(cat_name(c))} <span class="count">{n}</span></a>' for c, n in counts_other)
        data_attr = f'data-area="{slug}"'
        other_filter = f'''<div class="field"><label for="f-cat">Category</label><select class="select" id="f-cat" name="cat"><option value="">All categories</option>{gen_cat_options()}</select></div>'''
        hidden = f'<input type="hidden" name="area" value="{slug}">'

    first = "".join(biz_row(x) for x in items[:20])
    body = f'''{crumb}
<header class="page-head ho-container">
  <h1 class="page-head__title">{e(title)}</h1>
  <p class="page-head__lead">{e(lead)}</p>
</header>

<div class="ho-container">
  <p class="eyebrow">{chips_title}</p>
  <div class="chips mb-6">{chips}</div>

  <div class="layout-filters">
    <form class="filters" id="filters" data-directory-form action="" method="get">
      {hidden}
      <div class="filters__head"><h2>Filter</h2><button class="ho-header__icon-btn filters__close" type="button" data-filters-close aria-label="Close filters">{icon("close")}</button></div>
      <div class="filters__group"><div class="field"><label for="f-q">Keyword</label><input class="input" id="f-q" type="search" name="q" placeholder="Name or service"></div></div>
      <div class="filters__group">{other_filter}</div>
      <fieldset class="filters__group"><legend>Contact details</legend>
        <ul class="check-list"><li><label><input type="checkbox" name="web" value="1"> Has a website</label></li><li><label><input type="checkbox" name="phone" value="1"> Has a phone number</label></li></ul>
      </fieldset>
      <div class="filters__foot"><a class="ho-btn ho-btn--ghost" href="?">Clear</a><button class="ho-btn ho-btn--primary" type="submit">Show results</button></div>
    </form>

    <div data-directory {data_attr} data-page-size="20">
      <div class="toolbar">
        <p class="toolbar__count" data-count><strong>{len(items):,}</strong> businesses</p>
        <div class="toolbar__right"><button class="ho-btn ho-btn--sm ho-btn--outline filters-toggle" type="button" data-filters-open aria-controls="filters" aria-expanded="false">{icon("filter")}Filters</button></div>
      </div>
      <div class="active-filters" data-active hidden></div>
      <div data-results>{first}</div>
      <nav class="pager" data-pager aria-label="Pages"></nav>
    </div>
  </div>

  <div class="cta-band mt-6">
    <div><h2>Run a business in Harrow?</h2><p>Claim your free listing or add a missing business. It takes two minutes.</p></div>
    <div class="cta-band__actions"><a class="ho-btn ho-btn--primary" href="/add-business.html">List your business</a><a class="ho-btn ho-btn--outline" href="/pricing.html">See plans</a></div>
  </div>
</div>'''
    # SEO brief 03: archives index only with 5+ indexable listings AND intro text. All imports are unclaimed → noindex for now.
    meta = {"title": title, "description": lead[:155], "robots": "noindex, follow", "hubnav": "directory"}
    return render(meta, body)


# ---------------------------------------------------------------- partials for hand-written pages
_LISTINGS = None


def listings():
    global _LISTINGS
    if _LISTINGS is None:
        _LISTINGS = H.load_listings()
    return _LISTINGS


def gen_cat_options():
    return "".join(f'<option value="{s}">{e(n)}</option>' for s, n, _i, _r in sorted(H.CATEGORIES, key=lambda c: c[1]))


def gen_area_options():
    return "".join(f'<option value="{s}">{e(n)}</option>' for s, n, _a, _b in sorted(H.AREAS, key=lambda a: a[1]))


def write_partials(cat_counts, area_counts):
    (PARTIALS / "gen-cat-options.html").write_text(gen_cat_options(), encoding="utf-8")
    (PARTIALS / "gen-area-options.html").write_text(gen_area_options(), encoding="utf-8")
    tiles = "".join(
        f'<a class="tile" href="/category/{s}.html"><span class="tile__icon">{icon(i)}</span><span class="tile__name">{e(n)}</span><span class="tile__count">{cat_counts[s]:,} businesses</span></a>'
        for s, n, i, _r in sorted(H.CATEGORIES, key=lambda c: -cat_counts[c[0]]))
    (PARTIALS / "gen-cat-tiles.html").write_text(f'<div class="grid grid-6">{tiles}</div>', encoding="utf-8")
    atiles = "".join(
        f'<a class="tile tile--area" href="/area/{s}.html"><span><span class="tile__name">{e(n)}</span><br><span class="tile__count">{area_counts[s]:,} businesses</span></span>{icon("chevron")}</a>'
        for s, n, _a, _b in sorted(H.AREAS, key=lambda a: a[1]))
    (PARTIALS / "gen-area-tiles.html").write_text(f'<div class="grid grid-4">{atiles}</div>', encoding="utf-8")
    (PARTIALS / "gen-count.html").write_text(f"{len(listings()):,}", encoding="utf-8")
    # A few real listings for "recently added" style blocks (alphabetical sample with websites)
    sample = [x for x in listings() if x["web"] and x["phone"]][::97][:6]
    (PARTIALS / "gen-recent-rows.html").write_text("".join(biz_row(x) for x in sample), encoding="utf-8")


def write_partials_only():
    L = listings()
    write_partials(Counter(x["cat"] for x in L), Counter(x["area"] for x in L))


def build_all():
    from build import OUT as SITE
    L = listings()
    cat_counts = Counter(x["cat"] for x in L)
    area_counts = Counter(x["area"] for x in L)
    write_partials(cat_counts, area_counts)

    by_cat_area = defaultdict(list)
    by_cat, by_area = defaultdict(list), defaultdict(list)
    for x in L:
        by_cat_area[(x["cat"], x["area"])].append(x)
        by_cat[x["cat"]].append(x)
        by_area[x["area"]].append(x)

    for d in ("business", "category", "area", "data"):
        (SITE / d).mkdir(parents=True, exist_ok=True)

    for x in L:
        rel = [r for r in by_cat_area[(x["cat"], x["area"])] if r is not x][:4]
        (SITE / "business" / f'{x["slug"]}.html').write_text(profile(x, rel), encoding="utf-8", newline="\n")

    for s, *_ in H.CATEGORIES:
        other = Counter(x["area"] for x in by_cat[s]).most_common()
        (SITE / "category" / f"{s}.html").write_text(archive("cat", s, by_cat[s], other), encoding="utf-8", newline="\n")
    for s, *_ in H.AREAS:
        other = Counter(x["cat"] for x in by_area[s]).most_common()
        (SITE / "area" / f"{s}.html").write_text(archive("area", s, by_area[s], other), encoding="utf-8", newline="\n")

    index = {
        "cats": {s: {"n": n, "i": i} for s, n, i, _r in H.CATEGORIES},
        "areas": {s: n for s, n, _a, _b in H.AREAS},
        "items": [
            {k: v for k, v in {
                "k": x["slug"], "n": x["name"], "c": x["cat"], "s": x["sub"], "a": x["area"],
                "ad": x["address"], "pc": x["postcode"], "p": x["phone_raw"][0] if x["phone_raw"] else "",
                "w": 1 if x["web"] else 0,
            }.items() if v not in ("", 0)}
            for x in L
        ],
    }
    (SITE / "data" / "hub-index.json").write_text(json.dumps(index, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
    print(f"Generated {len(L)} profiles, {len(H.CATEGORIES)} categories, {len(H.AREAS)} areas")
