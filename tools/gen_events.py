"""
What's On generator. One event source: data/events.json (loaded by hub_data.load_events()).

From that one list it builds:
  src/pages/events.html          list view (7 days from the preview's "today"; hub.js filters by ?when=)
  src/pages/events-month.html    month calendar
  src/partials/gen-weekend-cards.html   homepage "This weekend in Harrow"
  /events/{slug}/                one page per event (build.py calls event_pages() and writes them)
Business profiles pick up the same events by organiser ("org") in hub_data.load_claimed().

Sample content rule: anything with "sample": true carries a Sample badge (alongside Featured where set, to demo
featured placement). Weekly classes below are all samples.

    python tools/gen_events.py   # regenerate the list, month and weekend partial (build.py also runs this)
"""
import datetime as dt
import html
import re
import sys
from pathlib import Path
from urllib.parse import quote, quote_plus

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))
import hub_data as H  # noqa: E402

PAGES = ROOT / "src/pages"
PARTIALS = ROOT / "src/partials"
D = dt.date
TODAY = H.PREVIEW_TODAY
WEEKEND = (D(2026, 11, 13), D(2026, 11, 15))      # Friday to Sunday after TODAY
LIST_DAYS = 7                                       # list view: Wednesday 11 to Tuesday 17 November
ORIGIN = "https://harrowonline.org/hub"


def I(name, cls="ho-icon"):
    return f'<svg class="{cls}"><use href="#i-{name}"/></svg>'


def plain(s):
    """Text for URLs, JSON and calendar files: no tags, no entities."""
    return html.unescape(re.sub(r"<[^>]+>", "", s or ""))


SAMPLE = f'<span class="badge badge--sample" title="Made-up content for the preview. Removed before launch.">{I("alert")}Sample</span>'


def event_badges(x, extra=""):
    b = []
    if x.get("featured"):
        b.append(f'<span class="badge badge--featured">{I("star")}Featured</span>')
    elif x.get("sample"):
        b.append(SAMPLE)   # Sample only where no Featured badge shows
    return "".join(b) + extra


def is_featured(x):
    return bool(x.get("featured"))


# Weekly classes: weekday (0 = Mon), time, sort, title, venue, area, price, age, icon, featured, type. All samples.
CLASSES = [
    (0, "11am", 11.0, "Chair Yoga for Over-60s", "St George&rsquo;s Hall", "Headstone", "&pound;3", "Ages 60+", "heart", False, "Fitness"),
    (1, "7pm", 19.0, "Beginners&rsquo; Pottery", "Harrow Arts Centre", "Hatch End", "&pound;150 for 6 weeks", "Adults", "edit", False, "Workshops"),
    (2, "12.30pm", 12.5, "Lunchtime Spin", "Wealdstone Fitness Studio", "Wealdstone", "&pound;8", "Adults", "dumbbell", False, "Fitness"),
    (3, "10.30am", 10.5, "Toddler Rhyme Time", "Stanmore Library", "Stanmore", "Free", "Under 4s", "family", False, "Children&rsquo;s activities"),
    (3, "7pm", 19.0, "Conversational Spanish", "North Harrow Community Library", "North Harrow", "&pound;90 for 10 weeks", "Adults", "people", False, "Other regular classes"),
    (4, "6pm", 18.0, "Street Dance for Teens", "Rayners Lane Youth Hall", "Rayners Lane", "&pound;7", "Ages 12 to 16", "music", False, "Dance schools"),
    (4, "6.30pm", 18.5, "Evening Yoga Flow", "Wealdstone Fitness Studio", "Wealdstone", "&pound;10", "Adults", "dumbbell", True, "Fitness"),
    (5, "9am", 9.0, "Weekend Bootcamp", "Wealdstone Fitness Studio", "Wealdstone", "&pound;10", "Adults", "dumbbell", True, "Fitness"),
    (5, "9.30am", 9.5, "Junior Ballet", "Pinner Village Hall", "Pinner", "&pound;9", "Ages 4 to 7", "music", False, "Dance schools"),
    (5, "10am", 10.0, "Saturday Kids&rsquo; Coding Club", "Kenton Library", "Kenton", "&pound;12", "Ages 8 to 12", "family", False, "Children&rsquo;s activities"),
    (5, "10.30am", 10.25, "Story Time", "Hatch End Bookshop &amp; Caf&eacute;", "Hatch End", "Free", "Ages 3 to 6", "cup", False, "Children&rsquo;s activities"),
]
CLASS_DESC = {
    "Lunchtime Spin": "45 minutes of high-energy spin. Bikes set up for you, towels provided.",
    "Toddler Rhyme Time": "Songs, rhymes and stories for little ones and their grown-ups. Just turn up.",
    "Conversational Spanish": "Relaxed evening course for improvers. Join at any point this term.",
    "Street Dance for Teens": "Hip hop and commercial routines. No experience needed.",
    "Evening Yoga Flow": "Unwind after work with an hour of vinyasa yoga. Mats provided.",
    "Weekend Bootcamp": "Outdoor-style circuits indoors, for every fitness level. First session free.",
    "Junior Ballet": "Playful ballet for little dancers, working towards a summer show.",
    "Saturday Kids&rsquo; Coding Club": "Build games and animations in Scratch and Python. Laptops provided.",
    "Story Time": "Picture books, songs and a craft. Free, with a babyccino for the grown-ups.",
}
DAYNAMES = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]

EVENTS = H.load_events()


def items_on(day):
    out = []
    for e in EVENTS:
        if e["day"] == day:
            out.append(dict(kind="event", time=e["time"], sort=e["start"].hour + e["start"].minute / 60, title=e["title"], cat=e["cat"],
                            venue=e["venue"], area=e["area"], price=e["price"], age=e["age"], icon=e["icon"], featured=is_featured(e),
                            sample=e.get("sample", False), desc=e["desc"], href=e["href"]))
    if day.month == 11 or day <= D(2026, 12, 12):
        for c in CLASSES:
            if c[0] == day.weekday():
                out.append(dict(kind="class", time=c[1], sort=c[2], title=c[3], cat="Classes and activities", venue=c[4], area=c[5], price=c[6],
                                age=c[7], icon=c[8], featured=c[9], sample=True, desc=CLASS_DESC.get(c[3], ""), href="/class.html", ctype=c[10]))
    out.sort(key=lambda x: (not x["featured"], x["sort"]))
    return out


def long_date(d):
    return f"{DAYNAMES[d.weekday()]} {d.day} {d.strftime('%B')}"


# ---------------------------------------------------------------- list view
def event_row(x, day):
    ph_cls = "ph ph--red" if x["featured"] and x["kind"] == "event" else ("ph ph--dark" if x["featured"] else "ph")
    extra = f'<span class="badge badge--outline">{I("repeat")}Weekly class</span>' if x["kind"] == "class" else ""
    badges = event_badges(x, extra) + f'<span class="biz-card__cat">{x["cat"]}</span>'
    when = f'Every {DAYNAMES[day.weekday()]}, {x["time"]}' if x["kind"] == "class" else x["time"]
    when_icon = "repeat" if x["kind"] == "class" else "clock"
    desc = f'\n      <p class="card__text">{x["desc"]}</p>' if x["desc"] else ""
    return f'''    <article class="event-row{' event-row--featured' if x['featured'] else ''}" data-kind="{x['kind']}">
      <time class="date-tile" datetime="{day.isoformat()}"><span class="date-tile__m">{day.strftime('%b')}</span><span class="date-tile__d">{day.day}</span><span class="date-tile__w">{day.strftime('%a')}</span></time>
      <div class="{ph_cls}">{I(x['icon'])}</div>
      <div>
        <div class="cluster mb-2">{badges}</div>
        <h3 class="event-row__title"><a href="{x['href']}">{x['title']}</a></h3>
        <p class="card__meta"><span>{I(when_icon)}{when}</span><span>{I('pin')}{x['venue']}, {x['area']}</span><span>{I('people')}{x['age']}</span></p>{desc}
      </div>
      <div class="event-row__side"><strong>{x['price']}</strong></div>
    </article>'''


def day_label(day):
    if day == TODAY:
        return f"Today <small>{long_date(day)}</small>"
    if day == TODAY + dt.timedelta(days=1):
        return f"Tomorrow <small>{long_date(day)}</small>"
    return long_date(day)


def build_list():
    groups, total = [], 0
    for n in range(LIST_DAYS):
        day = TODAY + dt.timedelta(days=n)
        its = items_on(day)
        total += len(its)
        rows = "\n".join(event_row(x, day) for x in its)
        count = f'{len(its)} {"thing" if len(its) == 1 else "things"} on'
        groups.append(f'''  <section class="day-group" data-date="{day.isoformat()}" aria-labelledby="day-{day.isoformat()}">
    <h2 class="day-group__title" id="day-{day.isoformat()}">{day_label(day)} <small data-day-count>{count}</small></h2>
    <div>
{rows}
    </div>
  </section>''')
    return "\n".join(groups), total


def cat_checks():
    cats = [("classes", "Classes and activities", 11), ("family", "Family", 9), ("music", "Music", 8), ("food-and-drink", "Food and drink", 4),
            ("nightlife", "Nightlife", 2), ("community", "Community", 14), ("arts", "Arts", 3)]
    return "\n".join(f'              <li><label><input type="checkbox" name="cat[]" value="{s}"> {n} <span class="count">{c}</span></label></li>' for s, n, c in cats)


def write_list():
    groups, total = build_list()
    last = TODAY + dt.timedelta(days=LIST_DAYS - 1)
    span = f"{long_date(TODAY)} to {long_date(last)}"
    w0, w1 = WEEKEND
    body = f'''<!--
title: What's On in Harrow: events this week and weekend
description: Events, things to do and classes across Harrow this week and weekend: family days out, live music, markets, community events and more. Schools, clubs and charities list events free.
nav: events
hubnav: events
body: page-events
-->
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="/">Hub</a></li><li><span aria-current="page">What&rsquo;s On</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">What&rsquo;s On in Harrow</h1>
  <p class="page-head__lead">Events, days out and regular classes across the borough, checked by the Harrow Online team. Running something? <a href="/register.html">Add your event</a>: free for schools, clubs and charities.</p>
</header>

<div class="ho-container">
  <div class="layout-filters">
    <aside class="filters" id="filters" aria-label="Filter events">
      <form action="/events.html" method="get">
        <div class="filters__head">
          <h2>Filter events</h2>
          <button class="ho-btn ho-btn--ghost ho-btn--sm filters__close" type="button" data-filters-close aria-label="Close filters">{I('close')}</button>
        </div>
        <fieldset class="filters__group">
          <legend>Date</legend>
          <ul class="check-list">
            <li><label><input type="radio" name="when" value="" checked> Any date</label></li>
            <li><label><input type="radio" name="when" value="today"> Today</label></li>
            <li><label><input type="radio" name="when" value="weekend"> This weekend <span class="count">{w0.day} to {w1.day} Nov</span></label></li>
            <li><label><input type="radio" name="when" value="7days"> Next 7 days</label></li>
            <li><label><input type="radio" name="when" value="date" data-reveal="#pick-date"> Choose a date</label></li>
          </ul>
          <div class="field mt-4" id="pick-date" hidden>
            <label for="f-date">Date</label>
            <input class="input input--sm" id="f-date" type="date" name="date" min="{TODAY.isoformat()}" max="{last.isoformat()}" value="{w0.isoformat()}">
          </div>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Area</legend>
          <label class="ho-sr" for="f-area">Area</label>
          <select class="select select--sm" id="f-area" name="area"><option value="">All of Harrow</option>{{{{> gen-area-options}}}}</select>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Category</legend>
          <ul class="check-list">
{cat_checks()}
          </ul>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Age</legend>
          <ul class="check-list">
            <li><label><input type="checkbox" name="age[]" value="under-5"> Under 5s</label></li>
            <li><label><input type="checkbox" name="age[]" value="5-11"> 5 to 11</label></li>
            <li><label><input type="checkbox" name="age[]" value="12-17"> 12 to 17</label></li>
            <li><label><input type="checkbox" name="age[]" value="adults"> Adults</label></li>
            <li><label><input type="checkbox" name="age[]" value="60-plus"> Over 60s</label></li>
          </ul>
        </fieldset>
        <fieldset class="filters__group">
          <legend>Price</legend>
          <ul class="check-list">
            <li><label><input type="checkbox" name="free" value="1"> Free events only</label></li>
            <li><label><input type="checkbox" name="classes" value="0"> Hide weekly classes</label></li>
          </ul>
        </fieldset>
        <div class="filters__foot">
          <a class="ho-btn ho-btn--ghost" href="/events.html">Clear</a>
          <button class="ho-btn ho-btn--primary" type="submit">Show results</button>
        </div>
      </form>
    </aside>

    <div data-events-list data-today="{TODAY.isoformat()}" data-weekend="{w0.isoformat()},{w1.isoformat()}">
      <div class="toolbar">
        <p class="toolbar__count" data-events-count><strong>{total}</strong> events and classes, {span}</p>
        <div class="toolbar__right">
          <button class="ho-btn ho-btn--outline ho-btn--sm filters-toggle" type="button" data-filters-open aria-controls="filters" aria-expanded="false">{I('filter')}Filters</button>
          <nav class="seg" aria-label="View">
            <a href="/events.html" aria-current="page">{I('list')}List</a>
            <a href="/events-month.html">{I('calendar')}Month</a>
          </nav>
        </div>
      </div>

      <div class="chips chips--scroll mb-5" aria-label="Quick dates">
        <a class="chip" href="/events.html?when=today" data-when="today">Today</a>
        <a class="chip" href="/events.html?when=weekend" data-when="weekend">This weekend</a>
        <a class="chip" href="/events.html?when=7days" data-when="7days">Next 7 days</a>
        <a class="chip" href="/classes.html">{I('repeat', 'ho-icon ho-icon--sm')}Classes and activities</a>
        <a class="chip" href="/events.html?cat=family">Family</a>
        <a class="chip" href="/events.html?free=1">Free</a>
      </div>

      <div class="notice mb-5" data-events-empty hidden>{I('calendar')}<p>Nothing listed for that date yet. <a href="/events-month.html">See the month calendar</a>.</p></div>

{groups}

      <div class="split mt-6">
        <p class="muted small mb-0" data-events-range>Showing {span}.</p>
        <a class="ho-btn ho-btn--outline" href="/events-month.html">See the whole month {I('arrow')}</a>
      </div>

      <div class="grid grid-2 mt-6">
        <div class="panel panel--soft">
          <h2 class="panel__title">{I('repeat')} Looking for a regular class?</h2>
          <p class="small">Fitness, dance, kids&rsquo; clubs and workshops that run every week, all in one place.</p>
          <a class="link-arrow" href="/classes.html">Browse classes and activities {I('arrow')}</a>
        </div>
        <div class="panel panel--soft">
          <h2 class="panel__title">{I('mail')} The Weekend, every Thursday</h2>
          <p class="small">Our Thursday newsletter picks the best things to do in Harrow that weekend.</p>
          <form class="inline-signup" action="https://harrowonline.org/wp-json/ho/v1/subscribe" method="post" data-nl-form data-nl-done="Thanks! Check your inbox to confirm. The Weekend arrives every Thursday.">
            <input type="hidden" name="signup_source" value="events-weekend">
            <input type="hidden" name="editions[]" value="weekend">
            <div class="inline-signup__row">
              <label class="ho-sr" for="wk-email">Email address</label>
              <input class="input" id="wk-email" type="email" name="email" placeholder="Your email" autocomplete="email" required>
              <button class="ho-btn ho-btn--primary ho-btn--sm" type="submit">Get The Weekend</button>
            </div>
            <p class="help mt-2 mb-0" role="status" aria-live="polite">Free. The Weekend only: it won&rsquo;t sign you up to the Daily or Harrow Business.</p>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Running an event in Harrow?</h2>
      <p>Schools, clubs, charities and community groups list events and classes free. Businesses add events and classes on the Featured plan. We check every listing before it goes live, usually within one working day.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="/register.html">{I('plus')}Add your event</a>
      <a class="ho-btn ho-btn--outline" href="/pricing.html">Business plans</a>
    </div>
  </div>
</div>
'''
    (PAGES / "events.html").write_text(body, encoding="utf-8", newline="\n")
    return total


# ---------------------------------------------------------------- month view
def cal_ev(x):
    cls = "cal__ev"
    if x["featured"]:
        cls += " cal__ev--featured"
    elif x["kind"] == "class":
        cls += " cal__ev--class"
    label = f'{x["time"].split(" to ")[0]} {x["title"]}'
    return f'<a class="{cls}" href="{x["href"]}" title="{x["time"]}: {x["title"]}">{label}</a>'


def build_month():
    start = D(2026, 10, 26)
    cells = []
    for n in range(42):
        day = start + dt.timedelta(days=n)
        its = items_on(day)
        cls = ["cal__day"]
        if day.month != 11:
            cls.append("is-out")
        if day == TODAY:
            cls.append("is-today")
        shown = its[:3]
        more = len(its) - len(shown)
        inner = "".join(cal_ev(x) for x in shown)
        if more:
            inner += f'<a class="cal__more" href="/events.html?when=date&amp;date={day.isoformat()}">+{more} more</a>'
        aria = ' aria-current="date"' if day == TODAY else ""
        cells.append(f'      <div class="{" ".join(cls)}"><time class="cal__num" datetime="{day.isoformat()}"{aria}>{day.day}</time>{inner}</div>')
    return "\n".join(cells)


def build_agenda():
    out = []
    day = D(2026, 11, 1)
    while day.month == 11:
        evs = [x for x in items_on(day) if x["kind"] == "event"]
        if evs:
            label = long_date(day)
            if day == TODAY:
                label = f"Today <small>{long_date(day)}</small>"
            past = day < TODAY
            items = "".join(cal_ev(x) for x in evs)
            out.append(f'''      <section class="day-group{' is-past' if past else ''}">
        <h3 class="day-group__title">{label}{' <small>Ended</small>' if past else ''}</h3>
        {items}
      </section>''')
        day += dt.timedelta(days=1)
    return "\n".join(out)


def write_month():
    dows = "".join(f'<div class="cal__dow"><abbr title="{d}">{d[:3]}</abbr></div>' for d in DAYNAMES)
    body = f'''<!--
title: What's On in Harrow: November 2026 calendar
description: Month calendar of events and classes in Harrow for November 2026: Christmas lights, markets, fireworks, live music and weekly classes.
nav: events
hubnav: events
robots: noindex, follow
body: page-events
-->
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="/">Hub</a></li><li><a href="/events.html">What&rsquo;s On</a></li><li><span aria-current="page">November 2026</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">What&rsquo;s On in November</h1>
  <p class="page-head__lead">Every event and weekly class in Harrow this month. Pick a day to see everything that&rsquo;s on.</p>
</header>

<div class="ho-container">
  <div class="toolbar">
    <form class="cluster" action="/events-month.html" method="get">
      <label class="ho-sr" for="m-area">Area</label>
      <select class="select select--sm" id="m-area" name="area" style="width:auto"><option value="">All of Harrow</option>{{{{> gen-area-options}}}}</select>
      <label class="ho-sr" for="m-cat">Category</label>
      <select class="select select--sm" id="m-cat" name="cat" style="width:auto">
        <option value="">All categories</option>
        <option value="classes">Classes and activities</option>
        <option value="family">Family</option>
        <option value="music">Music</option>
        <option value="food-and-drink">Food and drink</option>
        <option value="nightlife">Nightlife</option>
        <option value="community">Community</option>
        <option value="arts">Arts</option>
      </select>
      <button class="ho-btn ho-btn--dark ho-btn--sm" type="submit">Apply</button>
    </form>
    <nav class="seg" aria-label="View">
      <a href="/events.html">{I('list')}List</a>
      <a href="/events-month.html" aria-current="page">{I('calendar')}Month</a>
    </nav>
  </div>

  <div class="cal">
    <div class="cal__head">
      <h2>November 2026</h2>
      <div class="cal__nav">
        <a href="/events-month.html?month=2026-10" aria-label="Previous month: October 2026">{I('chevron-left')}</a>
        <a href="/events-month.html?month=2026-12" aria-label="Next month: December 2026">{I('chevron')}</a>
      </div>
    </div>
    <div class="cal__grid cal__grid--month">
      {dows}
{build_month()}
    </div>
    <div class="cal__agenda">
      <p class="small muted mt-4">One-off events only. Weekly classes run every week: <a href="/classes.html">see all classes</a>.</p>
{build_agenda()}
      <p class="mt-5 mb-5"><a class="link-arrow" href="/events-month.html?month=2026-12">December 2026 {I('arrow')}</a></p>
    </div>
  </div>

  <div class="split mt-2">
    <div class="cal-legend" aria-label="Key">
      <span><i class="f"></i>Featured</span>
      <span><i></i>Event</span>
      <span><i class="c"></i>Weekly class</span>
    </div>
    <a class="link-quiet" href="/events.html">Prefer a list? See this week&rsquo;s events</a>
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Running an event in Harrow?</h2>
      <p>Schools, clubs, charities and community groups list events and classes free. Businesses add events and classes on the Featured plan. Every listing is checked before it goes live.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="/register.html">{I('plus')}Add your event</a>
      <a class="ho-btn ho-btn--outline" href="/classes.html">Browse classes</a>
    </div>
  </div>
</div>
'''
    (PAGES / "events-month.html").write_text(body, encoding="utf-8", newline="\n")


# ---------------------------------------------------------------- cards (homepage, event pages)
def event_card(ev):
    feat = is_featured(ev)
    badges = event_badges(ev)
    top = f'<div class="card__badges">{badges}</div>\n        ' if badges else ""
    d = ev["day"]
    return f'''<article class="card event-card{' card--featured' if feat else ''}">
        {top}<time class="date-tile" datetime="{d.isoformat()}"><span class="date-tile__m">{d.strftime('%b')}</span><span class="date-tile__d">{d.day}</span><span class="date-tile__w">{d.strftime('%a')}</span></time>
        <div class="ph{' ph--red' if feat else ''}">{I(ev['icon'])}</div>
        <div class="card__body">
          <span class="biz-card__cat">{ev['cat']}</span>
          <h3 class="card__title"><a href="{ev['href']}">{ev['title']}</a></h3>
          <p class="card__meta"><span>{I('clock')}{ev['time']}</span><span>{I('pin')}{ev['area']}</span></p>
          <p class="card__meta"><span>{I('ticket')}{ev['price']}</span></p>
        </div>
      </article>'''


def weekend_events():
    w0, w1 = WEEKEND
    evs = [e for e in EVENTS if w0 <= e["day"] <= w1]
    return sorted(evs, key=lambda e: (not is_featured(e), e["start"]))


def write_weekend_partial():
    cards = "\n      ".join(event_card(e) for e in weekend_events()[:4])
    (PARTIALS / "gen-weekend-cards.html").write_text(cards + "\n", encoding="utf-8", newline="\n")


# ---------------------------------------------------------------- one page per event
def _utc(t):
    """UK local → UTC stamp for calendar links. GMT from 25 Oct 2026 to 28 Mar 2027 (all preview dates)."""
    bst = dt.datetime(2026, 3, 29) <= t < dt.datetime(2026, 10, 25, 1)
    return (t - dt.timedelta(hours=1 if bst else 0)).strftime("%Y%m%dT%H%M%SZ")


def _offset(t):
    bst = dt.datetime(2026, 3, 29) <= t < dt.datetime(2026, 10, 25, 1)
    return "+01:00" if bst else "+00:00"


def organiser(ev, claimed):
    """Hub business that posted the event (sample), or None."""
    c = claimed.get(ev.get("org"))
    if not c:
        return None
    slug = H.slugify(f'{c["name"]} {H.AREA_BY_SLUG[c["area"]][1]}')
    return {"name": c["name"], "slug": slug, "area": H.AREA_BY_SLUG[c["area"]][1], "tagline": c.get("tagline", "")}


def calendar_links(ev, url):
    title, place = plain(ev["title"]), plain(f'{ev["venue"]}, {ev["area"]}, Harrow')
    s, e = _utc(ev["start"]), _utc(ev["end"])
    google = ("https://calendar.google.com/calendar/render?action=TEMPLATE&amp;text=" + quote_plus(title) + f"&amp;dates={s}/{e}"
              + "&amp;location=" + quote_plus(place) + "&amp;details=" + quote_plus(url))
    iso = lambda t: (t - (dt.timedelta(hours=1) if _offset(t) == "+01:00" else dt.timedelta())).strftime("%Y-%m-%dT%H:%M:%SZ")
    outlook = ("https://outlook.live.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&amp;rru=addevent&amp;subject="
               + quote_plus(title) + "&amp;startdt=" + quote(iso(ev["start"]), safe="") + "&amp;enddt=" + quote(iso(ev["end"]), safe="")
               + "&amp;location=" + quote_plus(place))
    ics = "\r\n".join([
        "BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//Harrow Online Hub//EN", "BEGIN:VEVENT",
        f'UID:{ev["slug"]}-{ev["day"].year}@harrowonline.org', "DTSTAMP:20261111T090000Z", f"DTSTART:{s}", f"DTEND:{e}",
        "SUMMARY:" + title.replace(",", "\\,"), "LOCATION:" + place.replace(",", "\\,"), f"URL:{url}", "END:VEVENT", "END:VCALENDAR"])
    return google, outlook, "data:text/calendar;charset=utf-8," + quote(ics, safe=":/@.-_ ")


def more_events(ev, n=3):
    after = max(ev["start"], dt.datetime.combine(TODAY, dt.time()))
    return [e for e in EVENTS if e is not ev and e["start"] >= after][:n]


def event_page(ev, claimed):
    """(meta, body) for /events/{slug}/. Body mirrors the original hand-written event.html layout."""
    url = f'{ORIGIN}/events/{ev["slug"]}/'
    title, d = ev["title"], ev["day"]
    past = d < TODAY
    org = organiser(ev, claimed)
    google, outlook, ics = calendar_links(ev, url)
    maps = "https://www.google.com/maps/search/?api=1&amp;query=" + quote_plus(plain(f'{ev["venue"]} {ev["area"]} Harrow'))
    when_sub = ev["time"] + (f'. {ev["note"]}.' if ev.get("note") else "")
    cat_slug = H.slugify(plain(ev["cat"]))
    free = plain(ev["price"]).lower().startswith("free")

    ended = f'''
<div class="ho-container mt-4">
  <div class="notice notice--warn">{I('alert')}
    <div><p><strong>This event has ended.</strong> It took place on {long_date(d)} {d.year}.</p>
    <p><a href="/events.html">See what&rsquo;s on in Harrow this week</a></p></div>
  </div>
</div>''' if past else ""
    sample_note = f'''
<div class="ho-container mt-4">
  <div class="notice notice--sample">{I('alert')}
    <p><strong>Sample event.</strong> Made up to show how event pages work. It will be removed before launch.</p>
  </div>
</div>''' if ev.get("sample") else ""

    facts = [
        f'<li>{I("calendar")}<div><strong>{long_date(d)} {d.year}</strong><span>{when_sub}</span></div></li>',
        f'<li>{I("pin")}<div><strong>{ev["venue"]}, {ev["area"]}</strong><span><a href="#map">View on map</a></span></div></li>',
        f'<li>{I("ticket")}<div><strong>{ev["price"]}</strong><span>{"No tickets or booking needed" if free else "Check with the organiser before you go"}</span></div></li>',
        f'<li>{I("people")}<div><strong>{ev["age"]}</strong></div></li>',
    ]
    if org:
        facts.append(f'<li>{I("store")}<div><strong>Organised by <a href="/business/{org["slug"]}.html">{org["name"]}</a></strong><span>Listed on Harrow Online Hub</span></div></li>')

    about = "".join(f"<p>{p}</p>" for p in [ev["desc"]] if p) or "<p>Full details from the organiser coming soon.</p>"
    more = more_events(ev)
    more_html = ""
    if more:
        more_html = f'''
        <section class="profile-section" aria-labelledby="more-title">
          <div class="sec-head">
            <div><h2 class="sec-title" id="more-title">More coming up</h2></div>
            <a class="link-arrow hide-mobile" href="/events.html">All events in Harrow {I('arrow')}</a>
          </div>
          <div class="grid grid-3">
            {"".join(event_card(e) for e in more)}
          </div>
        </section>'''
    org_panel = ""
    if org:
        org_panel = f'''
        <div class="panel">
          <p class="eyebrow mb-2">Organiser</p>
          <div class="biz-card__head">
            <span class="mono" aria-hidden="true">{"".join(w[0] for w in plain(org["name"]).split()[:2]).upper()}</span>
            <div>
              <h2 class="panel__title mb-0"><a href="/business/{org["slug"]}.html">{org["name"]}</a></h2>
              <p class="card__meta mt-2"><span>{I('pin')}{org["area"]}</span>{SAMPLE}</p>
            </div>
          </div>
          <p class="small mt-4">{org["tagline"]}</p>
          <a class="ho-btn ho-btn--dark ho-btn--sm" href="/business/{org["slug"]}.html">View {org["name"]} profile</a>
        </div>'''
    share_text = quote(f'{plain(title)}, {d.strftime("%a")} {d.day} {d.strftime("%b")} {url}', safe="")
    pub = "Made-up sample for the preview." if ev.get("sample") else "Checked by Harrow Online."

    body = f'''<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="/">Hub</a></li>
    <li><a href="/events.html">What&rsquo;s On</a></li>
    <li><a href="/events.html?cat={cat_slug}">{ev["cat"]}</a></li>
    <li><span aria-current="page">{title}</span></li>
  </ol>
</nav>
{ended}{sample_note}
<article>
  <div class="ho-container event-hero">
    <div class="ph{' ph--red' if is_featured(ev) else ''}">{I(ev["icon"])}</div>
    <div>
      <div class="cluster">{event_badges(ev)}<a class="badge badge--outline" href="/events.html?cat={cat_slug}">{ev["cat"]}</a></div>
      <h1 class="event-hero__title">{title}</h1>
      <ul class="facts">
        {"".join(facts)}
      </ul>
      <div class="cluster mt-4">
        <a class="ho-btn ho-btn--outline" href="#add-to-calendar">{I('calendar')}Add to calendar</a>
        <a class="ho-btn ho-btn--outline" href="{maps}" rel="noopener" target="_blank">{I('map')}Get directions</a>
      </div>
    </div>
  </div>

  <div class="ho-container">
    <div class="layout-aside">
      <div>
        <section class="profile-section" aria-labelledby="about-title">
          <h2 id="about-title">About this event</h2>
          <div class="profile-desc">{about}</div>
        </section>

        <section class="profile-section" id="map" aria-labelledby="map-title">
          <h2 id="map-title">Where it is</h2>
          <div class="ph ph--map ph--wide">{I('pin')}</div>
          <div class="split mt-4">
            <p class="mb-0"><strong>{ev["venue"]}</strong>, {ev["area"]}, Harrow</p>
            <a class="ho-btn ho-btn--outline ho-btn--sm" href="{maps}" rel="noopener" target="_blank">{I('map')}Get directions</a>
          </div>
        </section>
{more_html}
      </div>

      <aside class="sticky stack">
        <div class="panel" id="add-to-calendar">
          <h2 class="panel__title">Add to your calendar</h2>
          <div class="stack">
            <a class="ho-btn ho-btn--outline ho-btn--block" href="{google}" rel="noopener" target="_blank">{I('calendar')}Google Calendar</a>
            <a class="ho-btn ho-btn--outline ho-btn--block" href="{outlook}" rel="noopener" target="_blank">{I('calendar')}Outlook</a>
            <!-- WordPress serves this as /hub/events/{{slug}}/ical/ ; the prototype uses a data URI -->
            <a class="ho-btn ho-btn--outline ho-btn--block" download="{ev["slug"]}.ics" href="{ics}">{I('download')}Apple or other (.ics)</a>
          </div>
        </div>

        <div class="panel">
          <h2 class="panel__title">Share this event</h2>
          <div class="cluster">
            <a class="ho-btn ho-btn--outline ho-btn--sm" href="https://wa.me/?text={share_text}" rel="noopener" target="_blank">{I('whatsapp')}WhatsApp</a>
            <a class="ho-btn ho-btn--outline ho-btn--sm" href="https://www.facebook.com/sharer/sharer.php?u={quote(url, safe='')}" rel="noopener" target="_blank">{I('facebook')}Facebook</a>
            <a class="ho-btn ho-btn--outline ho-btn--sm" href="mailto:?subject={quote(plain(title))}&amp;body={quote(url, safe='')}">{I('mail')}Email</a>
            <button class="ho-btn ho-btn--ghost ho-btn--sm" type="button" data-copy="{url}">Copy link</button>
          </div>
        </div>
{org_panel}
        <p class="small muted">{pub} Details can change, so check with the organiser before you travel. <a class="report-link" href="/report.html">{I('flag')}Report a problem</a></p>
      </aside>
    </div>
  </div>
</article>
'''
    import json
    node = {
        "@type": "Event", "@id": url + "#event", "name": plain(title), "description": plain(ev["desc"]) or plain(title), "url": url,
        "image": [f"{ORIGIN}/assets/img/og-default.png"],
        "startDate": ev["start"].strftime("%Y-%m-%dT%H:%M:%S") + _offset(ev["start"]),
        "endDate": ev["end"].strftime("%Y-%m-%dT%H:%M:%S") + _offset(ev["end"]),
        "eventStatus": "https://schema.org/EventScheduled", "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
        "isAccessibleForFree": free,
        "location": {"@type": "Place", "name": plain(ev["venue"]),
                     "address": {"@type": "PostalAddress", "streetAddress": plain(ev["venue"]), "addressLocality": "Harrow",
                                 "addressRegion": "London", "addressCountry": "GB"}},
        "publisher": {"@id": "https://harrowonline.org/#organization"},
    }
    if org:
        node["organizer"] = {"@type": "Organization", "name": plain(org["name"]), "url": f'{ORIGIN}/business/{org["slug"]}/'}
    body += f'\n<script type="application/ld+json">\n{json.dumps({"@context": "https://schema.org", "@graph": [node]}, indent=2, ensure_ascii=False)}\n</script>\n'

    desc = plain(f'{title} at {ev["venue"]}, {ev["area"]}, {long_date(d)} {d.year}, {ev["time"]}. {ev["price"]}, {ev["age"]}. {ev["desc"]}')
    meta = {"title": plain(f'{title}, {d.day} {d.strftime("%b")} {d.year}'), "description": desc, "nav": "events", "hubnav": "events",
            "body": "page-event", "schema": "ItemPage", "path": f'/events/{ev["slug"]}/'}
    if past or ev.get("sample"):
        meta["robots"] = "noindex, follow"   # SEO brief 03: ended events leave the index; samples never enter it
    return meta, body


def event_pages():
    """[(meta, body)] for every event. build.py renders and writes them."""
    claimed = {c["name"]: c for c in H.load_claimed()}
    return [event_page(ev, claimed) for ev in EVENTS]


def write_sources():
    """Hand-off files that live in src/ (list, month, homepage partial)."""
    n = write_list()
    write_month()
    write_weekend_partial()
    return n


if __name__ == "__main__":
    print("list items:", write_sources())
    print("event pages:", len(event_pages()))
