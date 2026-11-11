"""
SEO and HTML-structure audit for the built Hub (site/hub).

    python tools/seo_audit.py            # summary + first examples of each problem
    python tools/seo_audit.py --all      # every example

Checks every page for: title, meta description, canonical, robots, Open Graph / Twitter tags,
JSON-LD (parses, expected types, BreadcrumbList when a visible breadcrumb exists), one <h1>,
heading order, landmarks, image alt text, generic or empty link text, target=_blank without
rel=noopener, duplicate ids, form fields without labels, and duplicate titles/descriptions
among indexable pages. Use it on the WordPress staging build too (point SITE at a crawl dump).
"""
import json
import re
import sys
from collections import defaultdict
from html.parser import HTMLParser
from pathlib import Path

SITE = Path(__file__).resolve().parent.parent / "site" / "hub"
GENERIC = {"click here", "here", "read more", "more", "view", "see more", "learn more", "details", "link", "this", "go", "see all", "view all", "more info"}
VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr"}


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title, self.in_title = "", False
        self.meta, self.links_rel = {}, defaultdict(list)
        self.jsonld, self.in_ld, self.ld_buf = [], False, ""
        self.headings, self.cur_h = [], None
        self.anchors, self.cur_a = [], None
        self.imgs_no_alt, self.ids, self.tags = [], defaultdict(int), defaultdict(int)
        self.labels_for, self.fields = set(), []
        self.label_depth, self.hidden_depth = 0, 0
        self.stack = []
        self.lang = ""
        self.crumbs = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        self.tags[tag] += 1
        if tag not in VOID:
            self.stack.append((tag, a))
        if a.get("id"):
            self.ids[a["id"]] += 1
        if tag == "html":
            self.lang = a.get("lang", "")
        elif tag == "title":
            self.in_title = True
        elif tag == "meta":
            k = a.get("name") or a.get("property")
            if k:
                self.meta[k] = a.get("content", "")
        elif tag == "link" and a.get("rel"):
            self.links_rel[a["rel"]].append(a.get("href", ""))
        elif tag == "script" and a.get("type") == "application/ld+json":
            self.in_ld, self.ld_buf = True, ""
        elif re.fullmatch(r"h[1-6]", tag):
            self.cur_h = [int(tag[1]), ""]
        elif tag == "a":
            self.cur_a = {"href": a.get("href", ""), "text": "", "aria": a.get("aria-label", ""), "target": a.get("target"), "rel": a.get("rel", "")}
        elif tag == "img":
            if "alt" not in a:
                self.imgs_no_alt.append(a.get("src", ""))
            if self.cur_a is not None and a.get("alt"):
                self.cur_a["text"] += " " + a["alt"]
        elif tag == "label":
            self.label_depth += 1
            if a.get("for"):
                self.labels_for.add(a["for"])
        elif tag in ("input", "select", "textarea"):
            if a.get("type") not in ("hidden", "submit", "button", "reset", "image"):
                self.fields.append((a.get("id"), a.get("name"), bool(a.get("aria-label") or a.get("aria-labelledby") or a.get("title")), self.label_depth > 0))
        elif tag == "nav" and "crumbs" in a.get("class", ""):
            self.crumbs = True

    def handle_endtag(self, tag):
        if self.stack and self.stack[-1][0] == tag:
            self.stack.pop()
        if tag == "title":
            self.in_title = False
        elif tag == "script" and self.in_ld:
            self.in_ld = False
            try:
                self.jsonld.append(json.loads(self.ld_buf))
            except ValueError as ex:
                self.jsonld.append({"__error__": str(ex)})
        elif re.fullmatch(r"h[1-6]", tag) and self.cur_h:
            self.headings.append((self.cur_h[0], " ".join(self.cur_h[1].split())))
            self.cur_h = None
        elif tag == "a" and self.cur_a is not None:
            self.cur_a["text"] = " ".join(self.cur_a["text"].split())
            self.anchors.append(self.cur_a)
            self.cur_a = None
        elif tag == "label":
            self.label_depth = max(0, self.label_depth - 1)

    def handle_data(self, data):
        if self.in_title:
            self.title += data
        if self.in_ld:
            self.ld_buf += data
        if self.cur_h is not None:
            self.cur_h[1] += data
        if self.cur_a is not None:
            self.cur_a["text"] += data


def types_in(node, out):
    if isinstance(node, dict):
        t = node.get("@type")
        if t:
            out.update(t if isinstance(t, list) else [t])
        for v in node.values():
            types_in(v, out)
    elif isinstance(node, list):
        for v in node:
            types_in(v, out)
    return out


def audit():
    problems = defaultdict(list)
    seen_title, seen_desc = defaultdict(list), defaultdict(list)
    # news-site/ holds mock-ups of harrowonline.org pages (Yoast owns their SEO); assets/ holds no pages
    pages = sorted(f for f in SITE.rglob("*.html") if not {"news-site", "assets"} & set(f.relative_to(SITE).parts))
    stats = defaultdict(int)
    for f in pages:
        rel = f.relative_to(SITE).as_posix()
        p = Page()
        p.feed(f.read_text(encoding="utf-8"))
        add = lambda k, msg="": problems[k].append(f"{rel} {msg}".strip())
        robots = p.meta.get("robots", "")
        indexable = "noindex" not in robots
        stats["indexable" if indexable else "noindex"] += 1

        if p.lang != "en-GB":
            add("html lang not en-GB")
        t = " ".join(p.title.split())
        if not t:
            add("missing <title>")
        elif indexable and not 20 <= len(t) <= 70:
            add("indexable title length outside 20-70", f"({len(t)}) {t}")
        d = p.meta.get("description", "")
        if not d:
            add("missing meta description")
        elif not 70 <= len(d) <= 160:
            add("description length outside 70-160", f"({len(d)})")
        if indexable:
            seen_title[t].append(rel)
            seen_desc[d].append(rel)
        can = p.links_rel.get("canonical", [])
        if len(can) != 1:
            add("canonical missing or repeated")
        elif not can[0].startswith("https://"):
            add("canonical not absolute", can[0])
        for k in ("og:title", "og:description", "og:url", "og:image", "og:type", "og:site_name", "twitter:card"):
            if k not in p.meta:
                add(f"missing {k}")
        if not p.jsonld:
            add("no JSON-LD")
        types = set()
        for j in p.jsonld:
            if "__error__" in j:
                add("JSON-LD does not parse", j["__error__"])
            types_in(j, types)
        if p.crumbs and "BreadcrumbList" not in types:
            add("visible breadcrumb but no BreadcrumbList")
        if p.jsonld and not any(x in types for x in ("WebPage", "CollectionPage", "SearchResultsPage", "AboutPage", "ContactPage", "ProfilePage", "ItemPage", "FAQPage", "CheckoutPage")):
            add("no WebPage node")
        stats_types = ",".join(sorted(types))
        stats["types:" + stats_types] += 1

        h1 = [h for h in p.headings if h[0] == 1]
        if len(h1) != 1:
            add("h1 count != 1", str(len(h1)))
        prev = 0
        for lvl, text in p.headings:
            if not text:
                add("empty heading", f"h{lvl}")
            if prev and lvl > prev + 1:
                add("heading level skipped", f"h{prev}->h{lvl} '{text[:40]}'")
            prev = lvl
        for tag in ("header", "footer"):
            if p.tags[tag] < 1:
                add(f"no <{tag}>")
        if p.tags["main"] != 1:
            add("main count != 1")
        for src in p.imgs_no_alt:
            add("img without alt", src)
        for a in p.anchors:
            text = a["text"].strip().lower().rstrip(" →›>")
            if not text and not a["aria"]:
                add("link with no text", a["href"])
            elif text in GENERIC and not a["aria"]:
                add("generic link text", f"'{a['text']}' -> {a['href']}")
            if a["target"] == "_blank" and "noopener" not in a["rel"]:
                add("target=_blank without noopener", a["href"])
        for i, n in p.ids.items():
            if n > 1:
                add("duplicate id", i)
        for fid, name, aria, wrapped in p.fields:
            if not (aria or wrapped or (fid and fid in p.labels_for)):
                add("form field without label", fid or name or "?")

    for t, rels in seen_title.items():
        if len(rels) > 1:
            problems["duplicate title (indexable)"].append(f"{t}: {', '.join(rels[:4])}")
    for d, rels in seen_desc.items():
        if len(rels) > 1 and d:
            problems["duplicate description (indexable)"].append(f"{d[:60]}: {', '.join(rels[:4])}")
    return pages, problems, stats


if __name__ == "__main__":
    pages, problems, stats = audit()
    show = None if "--all" in sys.argv else 4
    print(f"{len(pages)} pages: {stats['indexable']} indexable, {stats['noindex']} noindex")
    for k in sorted(problems, key=lambda k: -len(problems[k])):
        v = problems[k]
        print(f"\n[{len(v)}] {k}")
        for line in v[:show]:
            print("   ", line)
    if "--types" in sys.argv:
        for k, v in sorted(stats.items()):
            if k.startswith("types:"):
                print(v, k)
    sys.exit(1 if problems else 0)
