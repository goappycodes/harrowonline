"""
Copy the live harrowonline.org header and footer into the Hub partials, verbatim, with only these changes:
  - Hub pages use the Hub logo (hub-logo.png / hub-logo-white.png), linking to the Hub homepage
  - an account icon in the header actions (Hub pages only)
  - one extra line in the footer legal block on Hub pages: data attribution + Hub legal pages (brief 01, 6.2)
News-site mock pages (site: news) keep the live logo.

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
ACCOUNT = ('{{site:hub}}<a class="ho-header__icon-btn ho-header__account" href="/account.html" aria-label="My account">'
           '<svg class="ho-icon ho-icon--user" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">'
           '<circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/>'
           '<path d="M4 20.5c1.2-3.8 4.3-5.7 8-5.7s6.8 1.9 8 5.7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
           '</a>{{/site}}\n                ')

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
# Hub pages use the shared main menu from brief 01, section 3 (News, What's On, Classes, Offers, Hub, Newsletter),
# in the live menu's markup and styling. News-site mock pages keep the live menu.
HUB_MENU = [
    ("news", "https://harrowonline.org/category/news/", "News"),
    ("events", "/events.html", "What&rsquo;s On"),
    ("classes", "/classes.html", "Classes"),
    ("offers", "/offers.html", "Offers"),
    ("hub", "/", "Hub"),
    ("newsletter", "https://harrowonline.org/newsletter/", "Newsletter"),
]


def hub_menu():
    return "\n".join(
        f'<li class="menu-item{"{{navcls:" + key + "}}"}"><a href="{href}"{"{{navaria:" + key + "}}"}>{label}</a></li>'
        for key, href, label in HUB_MENU)


def menu_swap(text, ul_id):
    import re
    m = re.search(rf'(<ul id="{ul_id}" class="[^"]+">)(.*?)(</ul>)', text, flags=re.S)
    assert m, ul_id
    return text[:m.start(2)] + "{{site:hub}}" + hub_menu() + "{{/site}}{{site:news}}" + m.group(2) + "{{/site}}" + text[m.end(2):]


header = menu_swap(header, "ho-menu")
header = menu_swap(header, "ho-menu-mobile")
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
