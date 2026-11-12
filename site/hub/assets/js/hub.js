/* Harrow Online Hub: front-end behaviour (vanilla JS, no dependencies).
   Everything here is progressive: pages read fine without it.
   In WordPress, the directory search (section 6) is replaced by server-rendered results + the same markup. */
(() => {
  "use strict";
  const BASE = document.documentElement.dataset.base || ""; // set by build.py (e.g. "/hub")
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const ICON = (id, cls = "ho-icon") => `<svg class="${cls}"><use href="${BASE}/assets/img/icons.svg#i-${id}"/></svg>`;
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  /* 0. Scrollbar width as --ho-sbw, so full-width layouts can line up with the centred container (100vw includes it) */
  const setSbw = () => document.documentElement.style.setProperty("--ho-sbw", (window.innerWidth - document.documentElement.clientWidth) + "px");
  setSbw();
  window.addEventListener("resize", setSbw);

  /* 1. Drawer (mobile menu) */
  const drawer = $("#ho-drawer");
  let lastFocus = null;
  const openDrawer = () => {
    if (!drawer) return;
    lastFocus = document.activeElement;
    drawer.hidden = false;
    requestAnimationFrame(() => drawer.classList.add("is-open"));
    document.body.classList.add("ho-lock");
    $$("[data-ho-drawer-open]").forEach((b) => b.setAttribute("aria-expanded", "true"));
    const first = $("input, a, button", $(".ho-drawer__panel", drawer));
    first && first.focus();
  };
  const closeDrawer = () => {
    if (!drawer || drawer.hidden) return;
    drawer.classList.remove("is-open");
    document.body.classList.remove("ho-lock");
    $$("[data-ho-drawer-open]").forEach((b) => b.setAttribute("aria-expanded", "false"));
    setTimeout(() => { drawer.hidden = true; }, 200);
    lastFocus && lastFocus.focus();
  };
  $$("[data-ho-drawer-open]").forEach((b) => b.addEventListener("click", openDrawer));
  $$("[data-ho-drawer-close]").forEach((b) => b.addEventListener("click", closeDrawer));

  /* 2. Header search panel */
  $$("[data-ho-search-toggle]").forEach((btn) => btn.addEventListener("click", () => {
    const panel = document.getElementById(btn.getAttribute("aria-controls"));
    if (!panel) return;
    panel.hidden = !panel.hidden;
    btn.setAttribute("aria-expanded", String(!panel.hidden));
    if (!panel.hidden) $("input", panel).focus();
  }));

  /* 3. Mobile filter sheet */
  $$("[data-filters-open]").forEach((b) => b.addEventListener("click", () => {
    const f = document.getElementById(b.getAttribute("aria-controls") || "filters");
    if (f) { f.classList.add("is-open"); document.body.classList.add("ho-lock"); b.setAttribute("aria-expanded", "true"); }
  }));
  $$("[data-filters-close]").forEach((b) => b.addEventListener("click", () => {
    const f = b.closest(".filters");
    if (f) { f.classList.remove("is-open"); document.body.classList.remove("ho-lock"); }
    $$("[data-filters-open]").forEach((o) => o.setAttribute("aria-expanded", "false"));
  }));

  /* 3b. Main menu dropdowns (the "Hub" item). Hover and keyboard focus open it in CSS; the chevron button
         toggles it for touch screens. Escape or a click elsewhere closes it. */
  const closeSubs = (except) => $$(".menu-item-has-children.is-open").forEach((li) => {
    if (li === except) return;
    li.classList.remove("is-open");
    const t = $("[data-ho-sub-toggle]", li);
    if (t) t.setAttribute("aria-expanded", "false");
  });
  $$("[data-ho-sub-toggle]").forEach((btn) => btn.addEventListener("click", (e) => {
    e.stopPropagation();
    const li = btn.closest(".menu-item-has-children");
    const open = !li.classList.contains("is-open");
    closeSubs(li);
    li.classList.toggle("is-open", open);
    li.classList.toggle("is-dismissed", !open);   // closing beats :focus-within while the button keeps focus
    btn.setAttribute("aria-expanded", String(open));
  }));
  $$(".menu-item-has-children").forEach((li) => {
    // Once focus or the pointer leaves the item, it can open again on hover or focus.
    li.addEventListener("focusout", (e) => { if (!li.contains(e.relatedTarget)) li.classList.remove("is-dismissed"); });
    li.addEventListener("mouseleave", () => li.classList.remove("is-dismissed"));
  });
  document.addEventListener("click", (e) => { if (!e.target.closest(".menu-item-has-children")) closeSubs(); });
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    const li = document.activeElement && document.activeElement.closest(".menu-item-has-children");
    closeSubs();
    if (li) { li.classList.add("is-dismissed"); const t = $("[data-ho-sub-toggle]", li); if (t) t.focus(); }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    closeDrawer();
    $$(".filters.is-open").forEach((f) => f.classList.remove("is-open"));
    document.body.classList.remove("ho-lock");
  });

  /* 4. Monthly / annual billing toggle: <div data-billing="monthly"> ... <button data-billing-set="annual"> */
  $$("[data-billing-set]").forEach((btn) => btn.addEventListener("click", () => {
    const scope = btn.closest("[data-billing]") || $("[data-billing]");
    if (!scope) return;
    scope.dataset.billing = btn.dataset.billingSet;
    $$("[data-billing-set]", scope).forEach((b) => b.setAttribute("aria-pressed", String(b === btn)));
  }));

  /* 5. Newsletter forms (prototype: no network call; WordPress posts to /wp-json/ho/v1/subscribe) */
  $$("[data-nl-form]").forEach((form) => form.addEventListener("submit", (e) => {
    e.preventDefault();
    const msg = $(".ho-nl__msg", form) || $("[role=status]", form);
    const email = $("input[type=email]", form);
    if (!email || !email.value.includes("@")) { if (msg) msg.textContent = "Please enter a valid email address."; return; }
    const btn = $("button[type=submit]", form);
    if (btn) { btn.innerHTML = ICON("check") + "<span>You&rsquo;re signed up</span>"; btn.disabled = true; }
    if (msg) msg.textContent = "Thanks! Your first newsletter arrives tomorrow morning.";
  }));

  /* 6. Subscriber-only offers: the code is NOT in the page. Submitting starts the email confirmation;
        WordPress returns the code only after the reader confirms (POST /wp-json/ho/v1/unlock → confirm link → code). */
  $$("[data-unlock]").forEach((form) => form.addEventListener("submit", (e) => {
    e.preventDefault();
    const lock = form.closest(".offer__lock");
    if (!lock) return;
    const email = esc(($("input[type=email]", form) || {}).value || "your inbox");
    lock.innerHTML = `<p role="status">${ICON("mail", "ho-icon ho-icon--sm")}Check ${email} and tap the confirmation link. Your code appears here once you have confirmed.</p>`;
  }));

  /* 5b. Homepage stats: numbers count up once when the strip scrolls into view.
        The real number is already in the HTML (no-JS, search engines and reduced motion keep it as is). */
  const counters = $$("[data-count]");
  if (counters.length && "IntersectionObserver" in window && !matchMedia("(prefers-reduced-motion: reduce)").matches) {
    const fmt = (n) => n.toLocaleString("en-GB");
    const run = (el) => {
      const target = parseInt(el.dataset.count, 10);
      if (!Number.isFinite(target)) return;
      const t0 = performance.now(), dur = 1400;
      const step = (now) => {
        const p = Math.min(1, (now - t0) / dur), eased = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(Math.round(target * eased));
        if (p < 1) requestAnimationFrame(step);
      };
      el.textContent = "0";
      requestAnimationFrame(step);
    };
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { run(en.target); io.unobserve(en.target); }
    }), { threshold: 0.4 });
    counters.forEach((el) => io.observe(el));
  }

  /* 5c. Offer editor: show the team's note when opened from a "Needs changes" offer (?changes=1 in the preview) */
  const changes = $("[data-offer-changes]");
  if (changes && new URLSearchParams(location.search).get("changes") === "1") changes.hidden = false;

  /* 5d. Account lists (offers, events): status tabs filter the table by ?status=published|pending|draft|ended.
        A row's status comes from its .status--* pill; "needs changes" (rejected) counts as a draft. */
  const statusTabs = $(".dash-main .tabs");
  if (statusTabs && $("a[href*='status=']", statusTabs)) {
    const want = new URLSearchParams(location.search).get("status") || "";
    const table = $(".dash-main table");
    const norm = (s) => ({ rejected: "draft", active: "published", live: "published" }[s] || s);
    if (table && want) {
      let shown = 0;
      $$("tbody tr", table).forEach((tr) => {
        const pill = $(".status", tr);
        const cls = pill ? [...pill.classList].find((c) => c.startsWith("status--")) || "" : "";
        const on = norm(cls.slice(8)) === want;
        tr.hidden = !on;
        tr.style.display = on ? "" : "none";
        if (on) shown += 1;
      });
      if (!shown) {
        const row = document.createElement("tr");
        row.className = "table__empty";
        const cell = document.createElement("td");
        cell.colSpan = $$("thead th", table).length || 1;
        cell.textContent = "Nothing here yet.";
        row.appendChild(cell);
        $("tbody", table).appendChild(row);
      }
    }
    $$("a", statusTabs).forEach((a) => {
      const s = new URL(a.href, location.href).searchParams.get("status") || "";
      if (s === want) a.setAttribute("aria-current", "page"); else a.removeAttribute("aria-current");
    });
  }

  /* 5e. Offer form: live preview (headline value, title, end date, code, subscriber tag) */
  const offerForm = $("[data-offer-form]");
  if (offerForm) {
    const out = (k) => $(`[data-preview-out="${k}"]`);
    const valueOf = (title) => {
      const t = title.trim();
      let m = t.match(/(\d+)%\s*off/i); if (m) return `${m[1]}% OFF`;
      m = t.match(/£\s?(\d+)\s*off/i); if (m) return `£${m[1]} OFF`;
      m = t.match(/(\d)-for-(\d)/i); if (m) return `${m[1]}-FOR-${m[2]}`;
      m = t.match(/^(?:a\s+)?free\s+(\w+)/i); if (m) return `FREE ${m[1].toUpperCase()}`;
      return (t.split(",")[0] || "Your offer").toUpperCase();
    };
    const fmt = (iso) => { if (!iso) return "No end date"; const d = new Date(iso + "T12:00:00"); return "Ends " + d.toLocaleDateString("en-GB", { day: "numeric", month: "short" }); };
    const update = () => {
      const title = ($("#of-title") || {}).value || "";
      const code = (($("#of-code") || {}).value || "").trim().toUpperCase();
      const sub = (($("input[name=audience]:checked", offerForm) || {}).value) === "subscribers";
      if (out("title")) out("title").textContent = title || "Your offer headline";
      if (out("value")) out("value").textContent = valueOf(title);
      if (out("ends")) out("ends").textContent = fmt(($("#of-end") || {}).value);
      if (out("code")) out("code").textContent = sub ? (code ? "Code after email confirmation" : "Unlock with email") : (code || "No code needed");
      if (out("sub")) { out("sub").hidden = !sub; out("sub").style.display = sub ? "" : "none"; }
    };
    offerForm.addEventListener("input", update);
    offerForm.addEventListener("change", update);
    update();
    window.addEventListener("pageshow", update);   // browsers restore typed values after load (back button, reload)
  }

  /* 5f. Event form: live preview (kind, category, title, when, where, price, age) */
  const eventForm = $("[data-event-form]");
  if (eventForm) {
    const out = (k) => $(`[data-ev-out="${k}"]`);
    const val = (sel) => (($(sel, eventForm) || {}).value || "").trim();
    const checked = (name) => $(`input[name="${name}"]:checked`, eventForm);
    const time12 = (hhmm) => {
      if (!hhmm) return "";
      const [h, m] = hhmm.split(":").map(Number);
      const s = (h % 12 || 12) + (m ? "." + String(m).padStart(2, "0") : "") + (h < 12 ? "am" : "pm");
      return s;
    };
    const update = () => {
      const weekly = (checked("recurrence") || {}).value === "weekly";
      const cat = checked("category");
      if (out("kind")) { out("kind").style.display = weekly ? "" : "none"; }
      if (out("cat") && cat) out("cat").textContent = cat.parentElement.textContent.trim();
      if (out("title")) out("title").textContent = val("#ev-title") || "Your event title";
      let when = "";
      if (weekly) when = `Every ${val("#sl1-day") || "week"}${val("#sl1-s") ? ", " + time12(val("#sl1-s")) : ""}`;
      else {
        const d = val("#ev-sd");
        const ds = d ? new Date(d + "T12:00:00").toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" }) : "Date to be set";
        when = ds + (val("#ev-st") ? ", " + time12(val("#ev-st")) : "");
      }
      if (out("when")) out("when").textContent = when;
      const home = (checked("venue_is_listing") || {}).value !== "0";
      if (out("where")) out("where").textContent = home ? "Harrow Town Centre" : (val("#ev-venue") || "Venue to be set");
      const hint = $("[data-venue-home]", eventForm);
      if (hint) hint.style.display = home ? "" : "none";
      if (out("price")) out("price").textContent = val("#ev-price") || "Free";
      if (out("age")) out("age").textContent = val("#ev-age") || "All ages";
    };
    eventForm.addEventListener("input", update);
    eventForm.addEventListener("change", update);
    update();
    window.addEventListener("pageshow", update);
  }

  /* 6a. Local Offers page: filter by ?cat=, ?area= and ?sub=1 (chips and the filter form both send these).
        WordPress filters server-side with the same parameters. */
  const offerList = $("[data-offers-list]");
  if (offerList) {
    const q = new URLSearchParams(location.search);
    const cat = q.get("cat") || "", area = q.get("area") || "", sub = q.get("sub") === "1";
    let n = 0;
    $$(".offer, .offer-card", offerList).forEach((o) => {
      const on = (!cat || o.dataset.cat === cat) && (!area || o.dataset.area === area) && (!sub || o.dataset.sub === "1");
      o.hidden = !on;
      o.style.display = on ? "" : "none";
      if (on) n += 1;
    });
    const catSel = $("#o-cat"), areaSel = $("#o-area"), subBox = $("#o-sub");
    if (catSel) catSel.value = cat;
    if (areaSel) areaSel.value = area;
    if (subBox) subBox.checked = sub;
    $$(".chip[data-cat]").forEach((c) => c.toggleAttribute("aria-current", c.dataset.cat === cat));
    if (cat || area || sub) {
      const parts = [];
      if (cat && catSel && catSel.selectedOptions[0]) parts.push(catSel.selectedOptions[0].textContent.trim().toLowerCase());
      if (sub) parts.push("subscriber-only");
      const where = area && areaSel && areaSel.selectedOptions[0] ? `in ${areaSel.selectedOptions[0].textContent.trim()}` : "across Harrow";
      const count = $("[data-offers-count]");
      if (count) count.innerHTML = `<strong>${n}</strong> ${esc(parts.join(" "))}${parts.length ? " " : ""}${n === 1 ? "offer" : "offers"} ${esc(where)}`;
      const empty = $("[data-offers-empty]");
      if (empty) empty.hidden = n > 0;
    }
  }

  /* 6c. Classes page: filter cards by ?type=, ?age=, ?area= and ?day= (chips and the filter form send these) */
  const classList = $("[data-classes-list]");
  if (classList) {
    const q = new URLSearchParams(location.search);
    const want = { type: q.get("type") || "", age: q.get("age") || "", area: q.get("area") || "", day: q.get("day") || "" };
    let n = 0;
    $$(".event-card", classList).forEach((card) => {
      const on = (!want.type || card.dataset.type === want.type) && (!want.area || card.dataset.area === want.area)
        && (!want.day || card.dataset.day === want.day) && (!want.age || (card.dataset.ages || "").split(" ").includes(want.age));
      card.hidden = !on;
      card.style.display = on ? "" : "none";
      if (on) n += 1;
    });
    ["age", "area", "day"].forEach((k) => { const s = $(`#c-${k}`); if (s) s.value = want[k]; });
    $$(".chip[data-type]").forEach((c) => { if (c.dataset.type === want.type) c.setAttribute("aria-current", "page"); else c.removeAttribute("aria-current"); });
    if (want.type || want.age || want.area || want.day) {
      const count = $("[data-classes-count]");
      if (count) count.innerHTML = `<strong>${n}</strong> ${n === 1 ? "class matches" : "classes match"}`;
      const empty = $("[data-classes-empty]");
      if (empty) empty.hidden = n > 0;
    }
  }

  /* 6b. What's On list: date filter from ?when=today|weekend|7days|date (&date=YYYY-MM-DD).
        The preview has a fixed "today" (data-today); WordPress filters server-side with the same parameters. */
  const evList = $("[data-events-list]");
  if (evList) {
    const q = new URLSearchParams(location.search);
    const when = q.get("when") || "";
    const today = evList.dataset.today;
    const [w0, w1] = (evList.dataset.weekend || ",").split(",");
    const addDays = (iso, n) => { const d = new Date(iso + "T12:00:00"); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
    const nice = (iso) => new Date(iso + "T12:00:00").toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long" });
    const ranges = { today: [today, today], weekend: [w0, w1], "7days": [today, addDays(today, 6)] };
    const range = when === "date" && /^\d{4}-\d{2}-\d{2}$/.test(q.get("date") || "") ? [q.get("date"), q.get("date")] : ranges[when];
    if (range) {
      const [from, to] = range;
      let n = 0;
      $$(".day-group[data-date]", evList).forEach((g) => {
        const on = g.dataset.date >= from && g.dataset.date <= to;
        g.hidden = !on;
        g.style.display = on ? "" : "none";
        if (on) n += $$(".event-row", g).length;
      });
      const span = from === to ? nice(from) : `${nice(from)} to ${nice(to)}`;
      const count = $("[data-events-count]", evList);
      if (count) count.innerHTML = `<strong>${n}</strong> ${n === 1 ? "event or class" : "events and classes"}, ${esc(span)}`;
      const shown = $("[data-events-range]", evList);
      if (shown) shown.textContent = `Showing ${span}.`;
      const empty = $("[data-events-empty]", evList);
      if (empty) empty.hidden = n > 0;
      $$("[data-when]", evList).forEach((c) => c.toggleAttribute("aria-current", c.dataset.when === when));
      const radio = $(`#filters input[name="when"][value="${when}"]`);
      if (radio) radio.checked = true;
      const pick = $("#f-date");
      if (pick && when === "date") { pick.value = from; const box = $("#pick-date"); if (box) box.hidden = false; }
    }
  }

  /* 7. Character counters: <textarea maxlength="140" data-counter> */
  $$("[data-counter]").forEach((el) => {
    const out = document.createElement("span");
    out.className = "counter";
    el.after(out);
    const upd = () => { out.textContent = `${el.value.length} / ${el.maxLength}`; };
    el.addEventListener("input", upd); upd();
  });

  /* 8. Sponsor slot picker: .slot buttons inside [data-slots]; summary in [data-slot-count] / [data-slot-total] */
  $$("[data-slots]").forEach((wrap) => {
    const price = Number(wrap.dataset.price || 0);
    const update = () => {
      const n = $$(".slot.is-selected", wrap).length;
      $$("[data-slot-count]").forEach((el) => { el.textContent = String(n); });
      $$("[data-slot-total]").forEach((el) => { el.textContent = "£" + (n * price).toFixed(2); });
    };
    wrap.addEventListener("click", (e) => {
      const s = e.target.closest(".slot");
      if (!s || s.classList.contains("is-taken") || s.classList.contains("is-held")) return;
      s.classList.toggle("is-selected");
      s.setAttribute("aria-pressed", String(s.classList.contains("is-selected")));
      update();
    });
    update();
  });

  /* 9. Share / copy link */
  $$("[data-copy]").forEach((b) => b.addEventListener("click", async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy || location.href); b.textContent = b.dataset.copied || "Link copied"; } catch (_) { /* ignore */ }
  }));

  /* 10. Choice cards that reveal a panel: <input data-reveal="#panel-id"> */
  const syncReveal = () => $$("[data-reveal]").forEach((inp) => {
    const p = $(inp.dataset.reveal);
    if (p) p.hidden = !inp.checked;
  });
  $$("input[type=radio], input[type=checkbox]").forEach((i) => i.addEventListener("change", syncReveal));
  syncReveal();

  /* 10b. Pages opened with ?listing={slug} (claim, report): fill [data-listing-field="n|ad|pc|area|cat|link"] */
  const listingSlug = new URLSearchParams(location.search).get("listing");
  if (listingSlug && $("[data-listing-field]")) {
    fetch(BASE + "/data/hub-index.json").then((r) => r.json()).then((d) => {
      const x = d.items.find((i) => i.k === listingSlug);
      if (!x) return;
      const vals = { n: x.n, ad: x.ad || "", pc: x.pc, area: d.areas[x.a], cat: (d.cats[x.c] || {}).n, link: BASE + "/business/" + x.k + "/" };
      $$("[data-listing-field]").forEach((el) => {
        const f = el.dataset.listingField;
        if (f === "link") el.setAttribute("href", vals.link);
        else if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") el.value = vals[f] || "";
        else el.textContent = vals[f] || "";
      });
    }).catch(() => {});
  }

  /* ------------------------------------------------------------------
     11. Directory search over all listings (search, category, area pages)
     <div data-directory data-cat="" data-area="" data-page-size="20">
       results go in [data-results], count in [data-count], pager in [data-pager]
     Reads /data/hub-index.json (built by build.py from data/listings.json).
     ------------------------------------------------------------------ */
  const dir = $("[data-directory]");
  if (dir) {
    const PAGE = Number(dir.dataset.pageSize || 20);
    const fixed = { cat: dir.dataset.cat || "", area: dir.dataset.area || "" };
    const form = $("[data-directory-form]") || null;
    const resultsEl = $("[data-results]", dir);
    const countEl = $("[data-count]");
    const pagerEl = $("[data-pager]", dir);
    const activeEl = $("[data-active]");
    let INDEX = null;

    const params = () => {
      const u = new URLSearchParams(location.search);
      return {
        q: (u.get("q") || "").trim(),
        cat: fixed.cat || u.get("cat") || "",
        area: fixed.area || u.get("area") || "",
        web: u.get("web") === "1",
        phone: u.get("phone") === "1",
        sort: u.get("sort") || "relevance",
        page: Math.max(1, Number(u.get("page") || 1)),
      };
    };

    const tierRank = { premium: 0, partner: 0, enhanced: 1, basic: 2, unclaimed: 3 };
    const row = (x) => {
      const cat = INDEX.cats[x.c] || {};
      const area = INDEX.areas[x.a] || "";
      const initials = x.n.replace(/[^A-Za-z0-9 ]/g, "").split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join("").toUpperCase() || "?";
      const tier = x.t || "unclaimed";
      // Claimed listings (sample or real) show Verified; Featured for Premium and Local Partner
      const badge = tier === "unclaimed" ? `<span class="badge badge--unclaimed">Unclaimed</span>` :
        `<span class="badge badge--verified">${ICON("verified")}Verified</span>`;
      if (tier !== "unclaimed") {
        // Claimed listing: photo, tagline, verified; Premium and Local Partner are featured (brief 01, 6.2)
        const top = tier === "premium" || tier === "partner";
        return `<article class="biz-row${top ? " biz-row--featured" : ""}">
        <div class="ph${x.im ? " ph--photo" : ""}">${x.im ? `<img src="${BASE}${esc(x.im)}" alt="" width="1200" height="800" loading="lazy" decoding="async">` : ICON(x.i || "store")}</div>
        <div class="biz-row__main">
          <span class="biz-card__cat">${esc(cat.n || "")}${x.s ? ` · <span class="muted">${esc(x.s)}</span>` : ""}</span>
          <h3 class="biz-row__title"><a href="${BASE}/business/${x.k}/">${esc(x.n)}</a></h3>
          <p class="card__meta"><span>${ICON("pin")}${esc(x.ad ? x.ad + ", " : "")}${esc(x.pc)}</span><span>${esc(area)}</span></p>
          ${x.tg ? `<p class="card__text">${esc(x.tg)}</p>` : ""}
        </div>
        <div class="biz-row__side"><div class="cluster">${top ? `<span class="badge badge--featured">${ICON("star")}Featured</span>` : ""}${badge}</div>${x.p ? `<a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:${esc(x.p)}">${ICON("phone")}Call</a>` : ""}</div>
      </article>`;
      }
      return `<article class="biz-row biz-row--mono${tier === "unclaimed" ? " biz-row--unclaimed" : ""}">
        <span class="mono mono--soft" aria-hidden="true">${esc(initials)}</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">${esc(cat.n || "")}${x.s ? ` · <span class="muted">${esc(x.s)}</span>` : ""}</span>
          <h3 class="biz-row__title"><a href="${BASE}/business/${x.k}/">${esc(x.n)}</a></h3>
          <p class="card__meta"><span>${ICON("pin")}${esc(x.ad ? x.ad + ", " : "")}${esc(x.pc)}</span><span>${esc(area)}</span></p>
        </div>
        <div class="biz-row__side">${badge}${x.p ? `<a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:${esc(x.p)}">${ICON("phone")}Call</a>` : ""}</div>
      </article>`;
    };

    const render = () => {
      const p = params();
      const terms = p.q.toLowerCase().split(/\s+/).filter(Boolean);
      let items = INDEX.items.filter((x) =>
        (!p.cat || x.c === p.cat) && (!p.area || x.a === p.area) && (!p.web || x.w) && (!p.phone || x.p));
      if (terms.length) {
        items = items.map((x) => {
          const hay = (x.n + " " + (x.s || "") + " " + (INDEX.cats[x.c] || {}).n + " " + x.pc + " " + (x.ad || "")).toLowerCase();
          const name = x.n.toLowerCase();
          let score = 0;
          for (const t of terms) {
            if (!hay.includes(t)) return null;
            score += name.startsWith(t) ? 6 : name.includes(t) ? 4 : 1;
          }
          return [score, x];
        }).filter(Boolean).sort((a, b) => b[0] - a[0]).map((a) => a[1]);
      }
      // Ranking rule (brief 01, 6.2): paid featured first, then claimed, then unclaimed
      items = items.map((x, i) => [x, i]).sort((a, b) => (tierRank[a[0].t || "unclaimed"] - tierRank[b[0].t || "unclaimed"]) || (p.sort === "name" ? a[0].n.localeCompare(b[0].n) : a[1] - b[1])).map((a) => a[0]);

      const total = items.length;
      const pages = Math.max(1, Math.ceil(total / PAGE));
      const page = Math.min(p.page, pages);
      const slice = items.slice((page - 1) * PAGE, page * PAGE);

      if (countEl) countEl.innerHTML = `<strong>${total.toLocaleString("en-GB")}</strong> ${total === 1 ? "business" : "businesses"}${p.q ? ` for &ldquo;${esc(p.q)}&rdquo;` : ""}`;
      resultsEl.innerHTML = slice.length ? slice.map(row).join("") :
        `<div class="empty">${ICON("search")}<h3>No businesses found</h3><p>Try a different word, or clear a filter. Can&rsquo;t find a business you know? Add it to the Hub.</p><a class="ho-btn ho-btn--primary" href="${BASE}/add-business/">${ICON("plus")}Add a business</a></div>`;

      if (activeEl) {
        const chips = [];
        const link = (key) => { const u = new URLSearchParams(location.search); u.delete(key); u.delete("page"); return "?" + u.toString(); };
        if (p.q) chips.push(`<a class="chip" href="${link("q")}">&ldquo;${esc(p.q)}&rdquo; ${ICON("close")}</a>`);
        if (p.cat && !fixed.cat) chips.push(`<a class="chip" href="${link("cat")}">${esc((INDEX.cats[p.cat] || {}).n)} ${ICON("close")}</a>`);
        if (p.area && !fixed.area) chips.push(`<a class="chip" href="${link("area")}">${esc(INDEX.areas[p.area])} ${ICON("close")}</a>`);
        if (p.web) chips.push(`<a class="chip" href="${link("web")}">Has a website ${ICON("close")}</a>`);
        if (p.phone) chips.push(`<a class="chip" href="${link("phone")}">Has a phone number ${ICON("close")}</a>`);
        activeEl.innerHTML = chips.join("");
        activeEl.hidden = !chips.length;
      }

      if (pagerEl) {
        const href = (n) => { const u = new URLSearchParams(location.search); u.set("page", n); return "?" + u.toString(); };
        let html = "";
        if (page > 1) html += `<a href="${href(page - 1)}" aria-label="Previous page">${ICON("chevron-left")}</a>`;
        const show = new Set([1, pages, page - 1, page, page + 1].filter((n) => n >= 1 && n <= pages));
        let prev = 0;
        [...show].sort((a, b) => a - b).forEach((n) => {
          if (n - prev > 1) html += `<span class="gap">&hellip;</span>`;
          html += n === page ? `<span aria-current="page">${n}</span>` : `<a href="${href(n)}">${n}</a>`;
          prev = n;
        });
        if (page < pages) html += `<a href="${href(page + 1)}" aria-label="Next page">${ICON("chevron")}</a>`;
        pagerEl.innerHTML = pages > 1 ? html : "";
      }

      // Reflect state in the filter form
      if (form) {
        const set = (name, v) => { const el = form.elements[name]; if (!el) return; if (el.type === "checkbox") el.checked = !!v; else el.value = v; };
        set("q", p.q); if (!fixed.cat) set("cat", p.cat); if (!fixed.area) set("area", p.area); set("web", p.web); set("phone", p.phone); set("sort", p.sort);
        $$("input[name=cat][type=radio]", form).forEach((r) => { r.checked = r.value === p.cat; });
        $$("input[name=area][type=radio]", form).forEach((r) => { r.checked = r.value === p.area; });
      }
    };

    // Clean empty params on submit so URLs stay tidy
    if (form) form.addEventListener("submit", (e) => {
      e.preventDefault();
      const u = new URLSearchParams();
      new FormData(form).forEach((v, k) => { if (v !== "" && !(k === "sort" && v === "relevance")) u.set(k, v); });
      history.pushState(null, "", "?" + u.toString());
      render();
      document.body.classList.remove("ho-lock");
      $$(".filters.is-open").forEach((f) => f.classList.remove("is-open"));
      resultsEl.scrollIntoView({ block: "start" });
    });
    if (form) $$("select, input[type=checkbox], input[type=radio]", form).forEach((el) => el.addEventListener("change", () => {
      if (window.matchMedia("(min-width: 1019px)").matches) form.requestSubmit();
    }));
    window.addEventListener("popstate", render);
    document.addEventListener("click", (e) => {
      const a = e.target.closest("[data-pager] a, [data-active] a");
      if (!a) return;
      e.preventDefault();
      history.pushState(null, "", a.getAttribute("href"));
      render();
      resultsEl.scrollIntoView({ block: "start" });
    });

    fetch(BASE + "/data/hub-index.json").then((r) => r.json()).then((d) => { INDEX = d; render(); })
      .catch(() => { /* keep the server-rendered first page */ });
  }
})();
