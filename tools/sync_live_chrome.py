"""
Copy the live harrowonline.org header and footer into the Hub partials, verbatim, with only these changes:
  - Hub pages use the Hub logo (hub-logo.png / hub-logo-white.png), linking to the Hub homepage
  - an account icon in the header actions (Hub pages only): Sign in when signed out, My account when signed in
  - a "Hub" section in the phone menu drawer (Businesses, Categories, Areas, Pricing, List your business),
    (desktop gets the same links as a "Hub" dropdown in the main menu)
  - one extra line in the footer legal block on Hub pages: data attribution + Hub legal pages (brief 01, 6.2)

    curl -s https://harrowonline.org/ -o live.html
    python tools/sync_live_chrome.py live.html

Re-run whenever the live header/footer changes, then python build.py.
"""
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PARTIALS = ROOT / "src/partials"
html = Path(sys.argv[1]).read_text(encoding="utf-8")


def cut(start, end, include_end=False):
    a = html.find(start)
    b = html.find(end, a)
    assert a >= 0 and b >= 0, f"markers not found: {start!r} / {end!r}"
    return html[a:b + (len(end) if include_end else 0)]


def swap(text, old, new):
    assert old in text, f"not found in live markup: {old[:80]!r}"
    return text.replace(old, new, 1)


LOGO = '<img src="https://harrowonline.org/wp-content/uploads/2020/08/Website-Logo-Small-1.png" width="233" height="90" alt="Harrow Online" fetchpriority="high">'
LOGO_LAZY = '<img src="https://harrowonline.org/wp-content/uploads/2020/08/Website-Logo-Small-1.png" width="233" height="90" alt="Harrow Online" loading="lazy">'
FOOT_LOGO = '<img src="https://harrowonline.org/wp-content/uploads/2020/08/bottomlogo.png" width="231" height="90" alt="Harrow Online" loading="lazy">'
SEARCH_BTN = '<button class="ho-header__icon-btn ho-header__search-btn"'
USER_SVG = ('<svg class="ho-icon ho-icon--user" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">'
            '<circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/>'
            '<path d="M4 20.5c1.2-3.8 4.3-5.7 8-5.7s6.8 1.9 8 5.7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>')
# One account control. hub-bar__out / hub-bar__in swap on body.is-logged-in.
ACCOUNT = ('{{site:hub}}<a class="ho-header__icon-btn ho-header__account hub-bar__out" href="/sign-in.html" aria-label="Sign in">' + USER_SVG + '</a>'
           '<a class="ho-header__icon-btn ho-header__account hub-bar__in" href="/account.html" aria-label="My account">' + USER_SVG + '</a>{{/site}}\n                ')
DRAWER_NL = '<a class="ho-btn ho-btn--primary ho-btn--block" href="https://harrowonline.org/newsletter/">'
DRAWER_HUB = ('{{site:hub}}<nav class="ho-drawer__hub" aria-label="Hub">'
              '<p class="ho-drawer__label">Hub</p>'
              '<ul class="ho-drawer__list ho-drawer__list--small">'
              '<li><a href="/search.html">Businesses</a></li>'
              '<li><a href="/categories.html">Categories</a></li>'
              '<li><a href="/#areas">Areas</a></li>'
              '<li><a href="/pricing.html">Pricing</a></li>'
              '<li><a href="/add-business.html">List your business</a></li>'
              '</ul></nav>{{/site}}\n            ')

# ---- header
header = cut('<header class="ho-header"', '<div id="ho-main"').rstrip() + "\n"
header = swap(header,
              '<a class="ho-header__logo" href="https://harrowonline.org/" rel="home">',
              '<a class="ho-header__logo{{site:hub}} ho-header__logo--hub{{/site}}" href="{{site:hub}}/{{/site}}{{site:news}}https://harrowonline.org/{{/site}}" rel="home">')
header = swap(header, LOGO,
              '{{site:hub}}<img src="/assets/img/hub-logo.png" width="269" height="90" alt="Harrow Online Hub" fetchpriority="high">{{/site}}{{site:news}}' + LOGO + '{{/site}}')
header = swap(header, SEARCH_BTN, ACCOUNT + SEARCH_BTN)
header = swap(header,
              '<a class="ho-drawer__logo" href="https://harrowonline.org/">',
              '<a class="ho-drawer__logo{{site:hub}} ho-drawer__logo--hub{{/site}}" href="{{site:hub}}/{{/site}}{{site:news}}https://harrowonline.org/{{/site}}">')
header = swap(header, LOGO_LAZY,
              '{{site:hub}}<img src="/assets/img/hub-logo.png" width="269" height="90" alt="Harrow Online Hub" loading="lazy">{{/site}}{{site:news}}' + LOGO_LAZY + '{{/site}}')
header = swap(header, DRAWER_NL, DRAWER_HUB + DRAWER_NL)
# Hub pages use the shared main menu from brief 01, section 3 (News, What's On, Classes, Offers, Hub), in the live
# menu's markup and styling. No "Newsletter" item: the red Newsletter button in the header (and "Get the newsletter"
# in the phone drawer) already covers it. News-site mock pages keep the live menu.
HUB_MENU = [
    ("hub", "/", "Hub"),
    ("news", "https://harrowonline.org/category/news/", "News"),
    ("events", "/events.html", "What&rsquo;s On"),
    ("classes", "/classes.html", "Classes"),
    ("offers", "/offers.html", "Offers"),
    ("businesses", "/search.html", "Businesses"),
]


# Desktop "Businesses" dropdown (Categories, Areas, Pricing, List your business). Same links as the phone drawer's Hub section.
HUB_SUBMENU = [
    ("categories", "/categories.html", "Categories"),
    ("", "/#areas", "Areas"),
    ("pricing", "/pricing.html", "Pricing"),
]


def hub_submenu():
    items = "".join(f'<li><a href="{href}"{"{{hubnav:" + key + "}}" if key else ""}>{label}</a></li>' for key, href, label in HUB_SUBMENU)
    cta = '<li class="sub-menu__cta"><a href="/add-business.html"><svg class="ho-icon"><use href="#i-plus"/></svg>List your business</a></li>'
    return ('<button class="ho-nav__toggle" type="button" aria-expanded="false" aria-controls="ho-hub-menu" aria-label="More business pages" data-ho-sub-toggle>'
            '<svg class="ho-icon"><use href="#i-chevron-down"/></svg></button>'
            f'<ul class="sub-menu" id="ho-hub-menu">{items}{cta}</ul>')


def hub_menu(dropdown=False):
    out = []
    for key, href, label in HUB_MENU:
        sub = dropdown and key == "businesses"
        cls = "menu-item" + (" menu-item-has-children" if sub else "") + "{{navcls:" + key + "}}"
        out.append(f'<li class="{cls}"><a href="{href}"{"{{navaria:" + key + "}}"}>{label}</a>{hub_submenu() if sub else ""}</li>')
    return "\n".join(out)


def menu_swap(text, ul_id, dropdown=False):
    import re
    m = re.search(rf'(<ul id="{ul_id}" class="[^"]+">)(.*?)(</ul>)', text, flags=re.S)
    assert m, ul_id
    return text[:m.start(2)] + "{{site:hub}}" + hub_menu(dropdown) + "{{/site}}{{site:news}}" + m.group(2) + "{{/site}}" + text[m.end(2):]


header = menu_swap(header, "ho-menu", dropdown=True)   # desktop: Hub dropdown
header = menu_swap(header, "ho-menu-mobile")           # phone drawer: has its own Hub section below the menu
(PARTIALS / "header.html").write_text(header, encoding="utf-8", newline="\n")

# ---- footer
footer = cut('<footer class="ho-footer"', '</footer>', include_end=True) + "\n</body>\n</html>\n"
footer = swap(footer,
              '<a class="ho-footer__logo" href="https://harrowonline.org/">',
              '<a class="ho-footer__logo{{site:hub}} ho-footer__logo--hub{{/site}}" href="{{site:hub}}/{{/site}}{{site:news}}https://harrowonline.org/{{/site}}">')
footer = swap(footer, FOOT_LOGO,
              '{{site:hub}}<img src="/assets/img/hub-logo-white.png" width="269" height="90" alt="Harrow Online Hub" loading="lazy">{{/site}}{{site:news}}' + FOOT_LOGO + '{{/site}}')
# Footer "Events" goes to the Hub calendar on Hub pages (it replaces the Evvnt page at launch: brief 01, section 3)
footer = swap(footer, '<li><a href="https://harrowonline.org/events/">Events</a></li>',
              '<li><a href="{{site:hub}}/events.html{{/site}}{{site:news}}https://harrowonline.org/events/{{/site}}">Events</a></li>')
legal_end = 'Registered office: 79 College Road, Harrow, HA1 1BD.</p>'
footer = swap(footer, legal_end, legal_end +
              '\n                    {{site:hub}}<p>Hub business listings include data from <a href="/data-sources.html">Overture Maps Foundation</a>. '
              '<a href="/privacy.html">Hub privacy</a> &middot; <a href="/terms.html">Hub terms</a></p>{{/site}}')
(PARTIALS / "footer.html").write_text(footer, encoding="utf-8", newline="\n")
print("header.html and footer.html written from live markup")
