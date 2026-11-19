"""Build src/pages/classes.html (the prototype's Classes and activities page) from tools/classes_data.py."""
from pathlib import Path
from classes_data import C, REAL, TYPE_SLUG, I, _slug, _day, _ages  # noqa: F401
OUT = Path(__file__).resolve().parent.parent / "src/pages/classes.html"
cards = []
for t, ty, when, area, age, price, icon, feat, prov, text in C:
    # All classes are made-up samples: Sample badge, plus Featured where set
    feat_b = f'<span class="badge badge--featured">{I("star")}Featured</span>' if feat else ""
    real = REAL.get(t)
    sample_b = ""   # 18 Nov 2026: no "Sample" tag anywhere
    badges = f'<div class="card__badges">{sample_b}{feat_b}</div>\n        '
    ph = "ph ph--dark" if feat else "ph"
    if real and real[4] == "logo":
        media = f'<div class="ph ph--logo"><img src="{real[0]}" alt="{real[1]}" width="480" height="480" loading="lazy" decoding="async"></div>'
    elif real:
        media = f'<div class="ph ph--photo"><img src="{real[0]}" alt="{real[1]}" width="1200" height="800" loading="lazy" decoding="async"></div>'
    else:
        media = f'<div class="{ph}">{I(icon)}</div>'
    href = (real[3] if real and real[3] else f"/events/{_slug(t)}/")
    prov_html = f'<a href="{real[2]}">{prov}</a>' if real else prov
    cards.append(f'''      <article class="card event-card{' card--featured' if feat else ''}" data-type="{TYPE_SLUG.get(ty, "other")}" data-area="{_slug(area)}" data-day="{_day(when)}" data-ages="{_ages(age)}">
        {badges}{media}
        <div class="card__body">
          <span class="biz-card__cat">{ty}</span>
          <h3 class="card__title"><a href="{href}">{t}</a></h3>
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
  <!-- Filters (clean, Nov 2026): class type chips, then one line with the count and three small filters that apply on change -->
  <div class="chips chips--scroll" aria-label="Class type">
{chips}
  </div>
  <div class="fline">
    <div class="fline__count">
      <p class="toolbar__count" data-classes-count><strong>{len(C)}</strong> classes and activities</p>
    </div>
    <form class="fline__form" action="/classes.html" method="get" aria-label="Filter classes" data-autofilter>
      <input type="hidden" name="type" value="" data-keep-type>
      <label class="fsel"><span class="ho-sr">Age</span><select id="c-age" name="age"><option value="">Any age</option> <option value="under-5">Under 5s</option> <option value="5-11">5 to 11</option> <option value="12-17">12 to 17</option> <option value="adults">Adults</option> <option value="60-plus">Over 60s</option></select></label>
      <label class="fsel"><span class="ho-sr">Area</span><select id="c-area" name="area"><option value="">All of Harrow</option>{{{{> gen-area-options}}}}</select></label>
      <label class="fsel"><span class="ho-sr">Day of the week</span><select id="c-day" name="day"><option value="">Any day</option> <option value="mon">Monday</option> <option value="tue">Tuesday</option> <option value="wed">Wednesday</option> <option value="thu">Thursday</option> <option value="fri">Friday</option> <option value="sat">Saturday</option> <option value="sun">Sunday</option></select></label>
      <button class="ho-btn ho-btn--dark ho-btn--sm fpanel__go" type="submit">Show classes</button>
      <a class="fpanel__clear" href="/classes.html" data-filter-clear hidden>Clear</a>
    </form>
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
      <p>Businesses add classes on the Featured or Local Partner plan. Schools, clubs, charities and community groups add them free with an organiser account. You keep one page up to date all year, and we check it before it goes live.</p>
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
