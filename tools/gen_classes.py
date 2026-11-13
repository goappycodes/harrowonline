from pathlib import Path
OUT = Path(__file__).resolve().parent.parent / "src/pages/classes.html"
def I(n, c="ho-icon"): return f'<svg class="{c}"><use href="#i-{n}"/></svg>'
import html as _html, re as _re, unicodedata as _ud
def _slug(t):
    t = _ud.normalize("NFKD", _html.unescape(t)).encode("ascii", "ignore").decode()
    return _re.sub(r"-{2,}", "-", _re.sub(r"[^a-zA-Z0-9]+", "-", t.lower()).strip("-"))
# title, type, when, area, age, price, icon, featured, provider, text
# Samples, except the titles in REAL (classes run by real Hub businesses). Keep titles in step with tools/gen_events.py CLASSES.
C = [
 ("Saturday Soccer School: Reception to Year 2", "Children&rsquo;s activities", "Every Saturday, 10am to 11am", "Hatch End", "Ages 4 to 7 (Reception to Year 2)", "&pound;6", "family", True, "Parkfield Youth FC", "Football coaching from DBS-checked, FA-qualified coaches. No booking needed: just turn up."),
 ("Saturday Soccer School: Years 3 to 9", "Children&rsquo;s activities", "Every Saturday, 11am to 12pm", "Hatch End", "Ages 7 to 14 (Years 3 to 9)", "&pound;6", "family", True, "Parkfield Youth FC", "Football coaching from DBS-checked, FA-qualified coaches. No booking needed: just turn up."),
 ("Girls&rsquo; Saturday Soccer School", "Children&rsquo;s activities", "Every Saturday, time to be confirmed", "Hatch End", "Girls (ages to be confirmed)", "Ask the club", "family", True, "Parkfield Youth FC", "A girls-only Soccer School session. Call or email the club for the time before you go."),
 ("Weekend Bootcamp", "Fitness", "Every Saturday, 9am", "Wealdstone", "Adults", "&pound;10", "dumbbell", True, "Wealdstone Fitness Studio", "Circuits for every fitness level. First session free."),
 ("Evening Yoga Flow", "Fitness", "Every Friday, 6.30pm", "Wealdstone", "Adults", "&pound;10", "dumbbell", True, "Wealdstone Fitness Studio", "An hour of vinyasa yoga to end the week. Mats provided."),
 ("Saturday Kids&rsquo; Coding Club", "Children&rsquo;s activities", "Every Saturday, 10am", "Kenton", "Ages 8 to 12", "&pound;12", "family", False, "Bright Sparks Coding", "Games and animations in Scratch and Python. Laptops provided."),
 ("Story Time", "Children&rsquo;s activities", "Every Saturday, 10.30am", "Hatch End", "Ages 3 to 6", "Free", "cup", False, "Hatch End Bookshop &amp; Caf&eacute;", "Picture books, songs and a craft, with a babyccino for grown-ups."),
 ("Junior Ballet", "Dance schools", "Every Saturday, 9.30am", "Pinner", "Ages 4 to 7", "&pound;9", "music", False, "Pinner Village Hall", "Playful ballet for little dancers, working towards a summer show."),
 ("Street Dance for Teens", "Dance schools", "Every Friday, 6pm", "Rayners Lane", "Ages 12 to 16", "&pound;7", "music", False, "Rayners Lane Youth Hall", "Hip hop and commercial routines. No experience needed."),
 ("Beginners&rsquo; Pottery", "Workshops", "Tuesdays, 7pm (6 weeks)", "Hatch End", "Adults", "&pound;150 course", "edit", False, "Harrow Arts Centre", "Learn to throw and glaze on the wheel. Clay and firing included."),
 ("Toddler Rhyme Time", "Children&rsquo;s activities", "Every Thursday, 10.30am", "Stanmore", "Under 4s", "Free", "family", False, "Stanmore Library", "Songs, rhymes and stories for little ones. Just turn up."),
 ("Lunchtime Spin", "Fitness", "Every Wednesday, 12.30pm", "Wealdstone", "Adults", "&pound;8", "dumbbell", False, "Wealdstone Fitness Studio", "45 minutes of high-energy spin, back at your desk by 1.30pm."),
 ("Chair Yoga for Over-60s", "Fitness", "Every Monday, 11am", "Headstone", "Ages 60+", "&pound;3", "heart", False, "St George&rsquo;s Hall", "Gentle seated yoga to help with balance and movement."),
 ("Conversational Spanish", "Other regular classes", "Every Thursday, 7pm", "North Harrow", "Adults", "&pound;90 for 10 weeks", "people", False, "North Harrow Community Library", "A relaxed evening class for improvers. Join any week."),
]
REAL = {t: ("/assets/img/business/parkfield-youth-fc-team.jpg", "Parkfield Youth FC players in a team huddle", "/business/parkfield-youth-fc-hatch-end.html")
        for t in ("Saturday Soccer School: Reception to Year 2", "Saturday Soccer School: Years 3 to 9", "Girls&rsquo; Saturday Soccer School")}
TYPE_SLUG = {"Children&rsquo;s activities": "childrens-activities", "Fitness": "fitness", "Dance schools": "dance-schools",
             "Workshops": "workshops", "Other regular classes": "other"}
DAY_SLUG = {d.lower(): d[:3].lower() for d in ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]}


def _day(when):
    w = when.lower()
    return next((v for k, v in DAY_SLUG.items() if k in w), "")


def _ages(age):
    """Age buckets a class suits (matches the Age filter values)."""
    a = age.lower()
    if "adult" in a:
        return "adults"
    if "60" in a:
        return "60-plus adults"
    nums = [int(n) for n in __import__("re").findall(r"\d+", a)]
    if "under" in a and nums:
        lo, hi = 0, nums[0] - 1
    elif "+" in a and nums:
        lo, hi = nums[0], 120
    elif len(nums) >= 2:
        lo, hi = nums[0], nums[1]
    elif nums:
        lo, hi = nums[0], nums[0]
    elif "girl" in a or "boy" in a:
        return "under-5 5-11 12-17"   # children's session, exact ages not published yet
    else:
        return "under-5 5-11 12-17 adults 60-plus"
    out = []
    for name, a0, a1 in (("under-5", 0, 4), ("5-11", 5, 11), ("12-17", 12, 17), ("adults", 18, 120)):
        if lo <= a1 and hi >= a0:
            out.append(name)
    return " ".join(out)


cards = []
for t, ty, when, area, age, price, icon, feat, prov, text in C:
    # All classes are made-up samples: Sample badge, plus Featured where set
    feat_b = f'<span class="badge badge--featured">{I("star")}Featured</span>' if feat else ""
    real = REAL.get(t)
    sample_b = "" if feat or real else f'<span class="badge badge--sample" title="Made-up class for the preview. Removed before launch.">{I("alert")}Sample</span>'
    badges = f'<div class="card__badges">{sample_b}{feat_b}</div>\n        '
    ph = "ph ph--dark" if feat else "ph"
    media = (f'<div class="ph ph--photo"><img src="{real[0]}" alt="{real[1]}" width="1200" height="800" loading="lazy" decoding="async"></div>'
             if real else f'<div class="{ph}">{I(icon)}</div>')
    prov_html = f'<a href="{real[2]}">{prov}</a>' if real else prov
    cards.append(f'''      <article class="card event-card{' card--featured' if feat else ''}" data-type="{TYPE_SLUG.get(ty, "other")}" data-area="{_slug(area)}" data-day="{_day(when)}" data-ages="{_ages(age)}">
        {badges}{media}
        <div class="card__body">
          <span class="biz-card__cat">{ty}</span>
          <h3 class="card__title"><a href="/events/{_slug(t)}/">{t}</a></h3>
          <p class="card__meta"><span>{I('repeat')}{when}</span><span>{I('pin')}{area}</span></p>
          <p class="card__meta"><span>{I('people')}{age}</span><span>{I('ticket')}{price}</span></p>
          <p class="card__text">{text}</p>
          <p class="card__meta mt-2"><span>{I('store')}{prov_html}</span></p>
        </div>
      </article>''')
_n = lambda ty: sum(1 for x in C if TYPE_SLUG.get(x[1], "other") == ty)
types = [("", "All classes", len(C)), ("childrens-activities", "Children&rsquo;s activities", _n("childrens-activities")), ("fitness", "Fitness", _n("fitness")),
         ("dance-schools", "Dance schools", _n("dance-schools")), ("workshops", "Workshops", _n("workshops")), ("other", "Other regular classes", _n("other"))]
chips = "\n".join(f'    <a class="chip" data-type="{s}" href="/classes.html{"?type=" + s if s else ""}"{" aria-current=\"page\"" if not s else ""}>{n} <span class="count">{c}</span></a>' for s, n, c in types)
body = f'''<!--
title: Classes and activities in Harrow
description: Weekly classes and activities across Harrow: children's clubs, fitness classes, dance schools, workshops and evening courses. Filter by age, area and day.
nav: classes
hubnav: events
body: page-classes
-->
<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol><li><a href="/">Hub</a></li><li><a href="/events.html">What&rsquo;s On</a></li><li><span aria-current="page">Classes and activities</span></li></ol>
</nav>

<header class="page-head ho-container">
  <h1 class="page-head__title">Classes and activities</h1>
  <p class="page-head__lead">Regular classes that run every week across Harrow: kids&rsquo; clubs, fitness, dance schools, workshops and evening courses. Each class has one page that the provider keeps up to date.</p>
</header>

<div class="ho-container">
  <div class="chips chips--scroll mb-4" aria-label="Class type">
{chips}
  </div>

  <form class="filter-bar" action="/classes.html" method="get" aria-label="Filter classes">
    <div class="field">
      <label for="c-age">Age</label>
      <select class="select select--sm" id="c-age" name="age">
        <option value="">Any age</option>
        <option value="under-5">Under 5s</option>
        <option value="5-11">5 to 11</option>
        <option value="12-17">12 to 17</option>
        <option value="adults">Adults</option>
        <option value="60-plus">Over 60s</option>
      </select>
    </div>
    <div class="field">
      <label for="c-area">Area</label>
      <select class="select select--sm" id="c-area" name="area"><option value="">All of Harrow</option>{{{{> gen-area-options}}}}</select>
    </div>
    <div class="field">
      <label for="c-day">Day of the week</label>
      <select class="select select--sm" id="c-day" name="day">
        <option value="">Any day</option>
        <option value="mon">Monday</option>
        <option value="tue">Tuesday</option>
        <option value="wed">Wednesday</option>
        <option value="thu">Thursday</option>
        <option value="fri">Friday</option>
        <option value="sat">Saturday</option>
        <option value="sun">Sunday</option>
      </select>
    </div>
    <button class="ho-btn ho-btn--dark ho-btn--sm" type="submit">Show classes</button>
  </form>

  <div class="toolbar mt-6">
    <p class="toolbar__count" data-classes-count><strong>{len(C)}</strong> classes and activities</p>
    <a class="link-quiet" href="/events.html">See one-off events in What&rsquo;s On</a>
  </div>

  <h2 class="ho-sr" id="classes-list">All classes and activities</h2>
  <div class="notice mb-5" data-classes-empty hidden>{I('calendar')}<p>No classes match that yet. <a href="/classes.html">See all classes</a>.</p></div>
  <!-- hub.js filters these by ?type=, ?age=, ?area= and ?day= (WordPress filters server-side with the same parameters) -->
  <div class="grid grid-3" data-classes-list>
{chr(10).join(cards)}
  </div>

  <div class="cta-band mt-7">
    <div>
      <h2>Run a class in Harrow?</h2>
      <p>Schools, clubs, charities and community groups list classes free with an organiser account. Businesses add classes on the Featured plan. You keep one page up to date all year, and we check it before it goes live.</p>
    </div>
    <div class="cta-band__actions">
      <a class="ho-btn ho-btn--primary" href="/register.html">{I('plus')}List your class</a>
      <a class="ho-btn ho-btn--outline" href="/pricing.html">Business plans</a>
    </div>
  </div>
</div>
'''
OUT.write_text(body, encoding="utf-8", newline="\n")
print("ok")
