"""Generates src/pages/events.html and src/pages/events-month.html from one event list (prototype data)."""
import datetime as dt
from pathlib import Path

PAGES = Path(__file__).resolve().parent.parent / "src/pages"
D = dt.date
TODAY = D(2026, 11, 11)

def I(name, cls="ho-icon"):
    return f'<svg class="{cls}"><use href="#i-{name}"/></svg>'

# One-off events: date, time label, sort key, title, category, venue, area, price, age, icon, featured, desc
EVENTS = [
    (D(2026,10,31), "5pm", 17.0, "Halloween Lantern Trail", "Family", "Headstone Manor &amp; Museum", "Headstone", "&pound;4", "All ages", "family", False, ""),
    (D(2026,11,1), "11am", 11.0, "Autumn Apple Day", "Family", "Headstone Manor &amp; Museum", "Headstone", "Free", "All ages", "leaf", False, ""),
    (D(2026,11,3), "7pm", 19.0, "Local History Talk: Harrow Weald", "Community", "Headstone Manor &amp; Museum", "Headstone", "&pound;5", "Adults", "news", False, ""),
    (D(2026,11,5), "6pm", 18.0, "Bonfire Night Lantern Walk", "Family", "Harrow Weald Common", "Harrow Weald", "Free", "All ages", "family", False, ""),
    (D(2026,11,7), "6pm", 18.0, "Canons Park Fireworks", "Family", "Canons Park", "Canons Park", "&pound;6", "All ages", "party", True, ""),
    (D(2026,11,8), "10.45am", 10.75, "Remembrance Sunday Service", "Community", "Harrow Civic Centre", "Harrow Town Centre", "Free", "All ages", "flag", False, ""),
    (D(2026,11,8), "2pm", 14.0, "Diwali Family Celebration", "Family", "Harrow Arts Centre", "Hatch End", "Free", "All ages", "party", False, ""),
    (D(2026,11,11), "10.45am", 10.75, "Armistice Day Silence", "Community", "Harrow Civic Centre, Station Road", "Harrow Town Centre", "Free", "All ages", "flag", False,
     "Join the two-minute silence at 11am, followed by a short wreath-laying."),
    (D(2026,11,11), "7.30pm", 19.5, "Charity Quiz Night", "Community", "St George&rsquo;s Hall", "Headstone", "&pound;5 a head", "Adults", "people", False,
     "Teams of up to six, with a raffle. Money raised goes to the Headstone food bank."),
    (D(2026,11,12), "6pm to 9pm", 18.0, "Museum Lates: A Victorian Christmas", "Arts", "Headstone Manor &amp; Museum", "Headstone", "&pound;6", "Ages 16+", "home", False,
     "Candlelit tours of the manor, short talks and festive crafts after hours."),
    (D(2026,11,12), "7.30pm", 19.5, "Open Mic Night", "Music", "Harrow Arts Centre", "Hatch End", "&pound;4", "Ages 14+", "music", False,
     "Singers, poets and comics welcome. Sign up on the door from 7pm."),
    (D(2026,11,13), "4pm to 8pm", 16.0, "Harrow Christmas Lights Switch-On", "Community", "St Ann&rsquo;s Road", "Harrow Town Centre", "Free", "All ages", "party", True,
     "Live music, a lantern parade, food stalls and the big switch-on at 6pm."),
    (D(2026,11,13), "8pm", 20.0, "Friday Night Comedy", "Nightlife", "Harrow Arts Centre", "Hatch End", "&pound;15", "Ages 18+", "people", False,
     "Four stand-up acts from the London circuit, hosted by a local favourite."),
    (D(2026,11,14), "10am to 1pm", 10.0, "Canons Park Tree Planting", "Community", "Canons Park", "Canons Park", "Free, book a place", "Ages 8+ with an adult", "leaf", False,
     "Help plant 200 native trees. Tools and gloves provided, bring wellies."),
    (D(2026,11,14), "7.30pm", 19.5, "Live Jazz Supper Club", "Music", "Hill Street Kitchen", "Harrow on the Hill", "&pound;35 with dinner", "Adults", "music", False,
     "A three-course supper with the Hill Street Trio. Booking essential."),
    (D(2026,11,15), "10am to 2pm", 10.0, "Pinner Farmers&rsquo; Market", "Food and drink", "Pinner High Street", "Pinner", "Free entry", "All ages", "cart", False,
     "Around 30 stalls of local produce, bread, cheese and hot food."),
    (D(2026,11,15), "10am to 12pm", 10.01, "Harrow Weald Common Guided Walk", "Community", "Harrow Weald Common", "Harrow Weald", "Free", "All ages", "leaf", False,
     "A gentle two-hour walk with a local ranger. Meet at the car park."),
    (D(2026,11,16), "7.30am", 7.5, "Harrow Business Breakfast", "Community", "Hill Street Kitchen", "Harrow on the Hill", "&pound;12", "Adults", "briefcase", False, ""),
    (D(2026,11,17), "2pm", 14.0, "Pinner Repair Caf&eacute;", "Community", "Pinner Methodist Hall", "Pinner", "Free", "All ages", "wrench", False, ""),
    # Events posted by claimed Hub businesses (data/claimed.json)
    (D(2026,11,17), "9am to 1pm", 9.0, "Flu Jab Drop-in", "Community", "Kenton Road Pharmacy", "Kenton", "Free for eligible adults", "Ages 18+", "medical", False,
     "No appointment needed. Bring your NHS number if you have it."),
    (D(2026,11,21), "10am", 10.02, "Bike Maintenance for Beginners", "Community", "Pinner Cycle Workshop", "Pinner", "&pound;15", "Ages 12+", "wrench", False,
     "Learn to fix a puncture, adjust your brakes and keep your chain running smoothly."),
    (D(2026,11,26), "7pm", 19.0, "Curry and Quiz Night", "Food and drink", "Rayners Lane Spice House", "Rayners Lane", "&pound;20 with dinner", "Adults", "food", False,
     "Teams of up to six. Dinner included, with prizes for the top three."),
    (D(2026,11,28), "10am", 10.02, "Children&rsquo;s Brushing Workshop", "Family", "Stanmore Family Dental", "Stanmore", "Free", "Ages 3 to 10", "medical", False,
     "A fun half hour on looking after teeth, with a free toothbrush for every child."),
    (D(2026,11,19), "7pm", 19.0, "Wine Tasting: Wines for Christmas", "Food and drink", "Hill Street Kitchen", "Harrow on the Hill", "&pound;25", "Ages 18+", "food", False, ""),
    (D(2026,11,20), "7pm", 19.0, "Film Night: It&rsquo;s a Wonderful Life", "Arts", "Harrow Arts Centre", "Hatch End", "&pound;7", "All ages", "star", False, ""),
    (D(2026,11,21), "10am", 10.0, "Christmas Craft Fair", "Arts", "Headstone Manor &amp; Museum", "Headstone", "Free entry", "All ages", "gift", True, ""),
    (D(2026,11,21), "7pm", 19.0, "Winter Choir Concert", "Music", "St George&rsquo;s Hall", "Headstone", "&pound;10", "All ages", "music", False, ""),
    (D(2026,11,22), "11am", 11.0, "Christmas Tree Festival", "Community", "St George&rsquo;s Hall", "Headstone", "Free", "All ages", "gift", False, ""),
    (D(2026,11,25), "5pm", 17.0, "Pinner Late-Night Shopping", "Community", "Pinner High Street", "Pinner", "Free", "All ages", "cart", False, ""),
    (D(2026,11,27), "7pm", 19.0, "Panto: Jack and the Beanstalk", "Family", "Harrow Arts Centre", "Hatch End", "From &pound;15", "All ages", "party", False, ""),
    (D(2026,11,28), "10am", 10.0, "Stanmore Christmas Market", "Food and drink", "Stanmore Broadway", "Stanmore", "Free entry", "All ages", "cart", True, ""),
    (D(2026,11,28), "2pm", 14.0, "Panto: Jack and the Beanstalk", "Family", "Harrow Arts Centre", "Hatch End", "From &pound;15", "All ages", "party", False, ""),
    (D(2026,11,29), "6pm", 18.0, "Advent Carols by Candlelight", "Music", "St George&rsquo;s Hall", "Headstone", "Free", "All ages", "music", False, ""),
    (D(2026,11,30), "7.30pm", 19.5, "St Andrew&rsquo;s Day Ceilidh", "Music", "Harrow Arts Centre", "Hatch End", "&pound;12", "All ages", "music", False, ""),
    (D(2026,12,5), "11am", 11.0, "Wealdstone Christmas Fair", "Community", "Wealdstone High Street", "Wealdstone", "Free", "All ages", "gift", False, ""),
]

# Weekly classes: weekday (0 = Mon), time, sort, title, venue, area, price, age, icon, featured, type
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
ORG_LINKS = {"Hill Street Kitchen": "/sample-premium.html", "Wealdstone Fitness Studio": "/sample-premium.html",
             "Hatch End Bookshop &amp; Caf&eacute;": "/sample-premium.html"}

def items_on(day):
    out = []
    for e in EVENTS:
        if e[0] == day:
            out.append(dict(kind="event", time=e[1], sort=e[2], title=e[3], cat=e[4], venue=e[5], area=e[6], price=e[7], age=e[8],
                            icon=e[9], featured=e[10], desc=e[11], href="/event.html"))
    if day.month == 11 or day <= D(2026, 12, 12):
        for c in CLASSES:
            if c[0] == day.weekday():
                out.append(dict(kind="class", time=c[1], sort=c[2], title=c[3], cat="Classes and activities", venue=c[4], area=c[5], price=c[6],
                                age=c[7], icon=c[8], featured=c[9], desc=CLASS_DESC.get(c[3], ""), href="/class.html", ctype=c[10]))
    out.sort(key=lambda x: (not x["featured"], x["sort"]))
    return out

# ---------------------------------------------------------------- list view
def event_row(x, day):
    ph_cls = "ph ph--red" if x["featured"] and x["kind"] == "event" else ("ph ph--dark" if x["featured"] else "ph")
    badges = []
    if x["featured"]:
        badges.append(f'<span class="badge badge--featured">{I("star")}Featured</span>')
    if x["kind"] == "class":
        badges.append(f'<span class="badge badge--outline">{I("repeat")}Weekly class</span>')
    badges.append(f'<span class="biz-card__cat">{x["cat"]}</span>')
    when = f'Every {DAYNAMES[day.weekday()]}, {x["time"]}' if x["kind"] == "class" else x["time"]
    when_icon = "repeat" if x["kind"] == "class" else "clock"
    desc = f'\n      <p class="card__text">{x["desc"]}</p>' if x["desc"] else ""
    return f'''    <article class="event-row{' event-row--featured' if x['featured'] else ''}">
      <span class="date-tile"><span class="date-tile__m">{day.strftime('%b')}</span><span class="date-tile__d">{day.day}</span><span class="date-tile__w">{day.strftime('%a')}</span></span>
      <div class="{ph_cls}">{I(x['icon'])}</div>
      <div>
        <div class="cluster mb-2">{''.join(badges)}</div>
        <h3 class="event-row__title"><a href="{x['href']}">{x['title']}</a></h3>
        <p class="card__meta"><span>{I(when_icon)}{when}</span><span>{I('pin')}{x['venue']}, {x['area']}</span><span>{I('people')}{x['age']}</span></p>{desc}
      </div>
      <div class="event-row__side"><strong>{x['price']}</strong></div>
    </article>'''

def day_label(day):
    if day == TODAY:
        return f"Today <small>Wednesday {day.day} November</small>"
    if day == TODAY + dt.timedelta(days=1):
        return f"Tomorrow <small>Thursday {day.day} November</small>"
    return f"{DAYNAMES[day.weekday()]} {day.day} November"

def build_list():
    groups, total = [], 0
    for n in range(5):  # Wed 11 to Sun 15
        day = TODAY + dt.timedelta(days=n)
        its = items_on(day)
        total += len(its)
        rows = "\n".join(event_row(x, day) for x in its)
        count = f'{len(its)} {"thing" if len(its) == 1 else "things"} on'
        groups.append(f'''  <section class="day-group" aria-labelledby="day-{day.isoformat()}">
    <h2 class="day-group__title" id="day-{day.isoformat()}">{day_label(day)} <small>{count}</small></h2>
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
    body = f'''<!--
title: What's On in Harrow: events this week and weekend
description: Events, things to do and classes across Harrow this week and weekend: family days out, live music, markets, community events and more. Free to list your event.
nav: events
hubnav: events
body: page-events
-->
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="/">Hub</a></li><li><span aria-current="page">What&rsquo;s On</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">What&rsquo;s On in Harrow</h1>
  <p class="page-head__lead">Events, days out and regular classes across the borough, checked by the Harrow Online team. Running something? <a href="/register.html">Add your event free</a>.</p>
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
            <li><label><input type="radio" name="when" value="weekend"> This weekend <span class="count">13 to 15 Nov</span></label></li>
            <li><label><input type="radio" name="when" value="7days"> Next 7 days</label></li>
            <li><label><input type="radio" name="when" value="date" data-reveal="#pick-date"> Choose a date</label></li>
          </ul>
          <div class="field mt-4" id="pick-date" hidden>
            <label for="f-date">Date</label>
            <input class="input input--sm" id="f-date" type="date" name="date" min="2026-11-11" value="2026-11-14">
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

    <div>
      <div class="toolbar">
        <p class="toolbar__count"><strong>{total}</strong> events and classes, Wednesday 11 to Sunday 15 November</p>
        <div class="toolbar__right">
          <button class="ho-btn ho-btn--outline ho-btn--sm filters-toggle" type="button" data-filters-open aria-controls="filters" aria-expanded="false">{I('filter')}Filters</button>
          <nav class="seg" aria-label="View">
            <a href="/events.html" aria-current="page">{I('list')}List</a>
            <a href="/events-month.html">{I('calendar')}Month</a>
          </nav>
        </div>
      </div>

      <div class="chips chips--scroll mb-5" aria-label="Quick dates">
        <a class="chip" href="/events.html?when=today">Today</a>
        <a class="chip" href="/events.html?when=weekend">This weekend</a>
        <a class="chip" href="/events.html?when=7days">Next 7 days</a>
        <a class="chip" href="/classes.html">{I('repeat', 'ho-icon ho-icon--sm')}Classes and activities</a>
        <a class="chip" href="/events.html?cat=family">Family</a>
        <a class="chip" href="/events.html?free=1">Free</a>
      </div>

{groups}

      <div class="split mt-6">
        <p class="muted small mb-0">Showing Wednesday 11 to Sunday 15 November.</p>
        <a class="ho-btn ho-btn--outline" href="/events.html?from=2026-11-16">Next week: from Monday 16 November {I('arrow')}</a>
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
          <a class="link-arrow" href="https://harrowonline.org/newsletter/">Get The Weekend free {I('arrow')}</a>
        </div>
      </div>
    </div>
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Add your event, free</h2>
      <p>Schools, clubs, charities and local businesses can list events and classes for free. We check every listing before it goes live, usually within one working day.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="/register.html">{I('plus')}Add your event</a>
      <a class="ho-btn ho-btn--outline" href="/pricing.html">Feature your event</a>
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
            label = f"{DAYNAMES[day.weekday()]} {day.day} November"
            if day == TODAY:
                label = f"Today <small>Wednesday {day.day} November</small>"
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
      <h2>Add your event, free</h2>
      <p>Schools, clubs, charities and local businesses can list events and classes on the Hub for free. Every listing is checked before it goes live.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="/register.html">{I('plus')}Add your event</a>
      <a class="ho-btn ho-btn--outline" href="/classes.html">Browse classes</a>
    </div>
  </div>
</div>
'''
    (PAGES / "events-month.html").write_text(body, encoding="utf-8", newline="\n")

if __name__ == "__main__":
    print("list items:", write_list())
    write_month()
    print("done")
