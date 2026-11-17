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
  // Account "+ Add" menu (Event, Class or Offer): close on a click outside or Escape
  $$("[data-add-menu]").forEach((m) => {
    document.addEventListener("click", (e) => { if (m.open && !m.contains(e.target)) m.open = false; });
    m.addEventListener("keydown", (e) => { if (e.key === "Escape" && m.open) { m.open = false; $("summary", m).focus(); } });
  });

  // Account Overview "Getting started" checklist: progress from the ticked tasks.
  // Preview only: ?welcome=1 shows a business that has just had its claim approved (only the claim is done).
  // WP: each task's done state comes from the listing (description and hours, photos, first event, class or offer).
  const setup = $("[data-setup]");
  if (setup) {
    if (new URLSearchParams(location.search).get("welcome") === "1") {
      $$("[data-task]", setup).forEach((li) => li.classList.toggle("is-done", li.dataset.task === "claim"));
      setup.open = true;
      $$("[data-not-welcome]").forEach((el) => { el.hidden = true; });
      $$("[data-welcome-only]").forEach((el) => { el.hidden = false; });
      $$("[data-welcome-text]").forEach((el) => { el.innerHTML = el.dataset.welcomeText; });
    }
    const items = $$("[data-task]", setup);
    const done = items.filter((li) => li.classList.contains("is-done")).length;
    $("[data-setup-count]", setup).textContent = done === items.length ? `All ${done} done` : `${done} of ${items.length} done`;
    $("[data-setup-bar]", setup).style.width = Math.round((done / items.length) * 100) + "%";
    items.forEach((li) => {
      const t = $(".setup__tick", li);
      if (t) t.setAttribute("aria-label", li.classList.contains("is-done") ? "Done" : "Not done yet");
    });
  }

  // Hub header search: "Search news instead" carries what was typed to the news site's search
  $$("[data-news-search]").forEach((a) => {
    const inp = $("input[type=search]", a.closest("form") || a.parentElement.parentElement);
    if (!inp) return;
    const sync = () => { a.href = "https://harrowonline.org/?s=" + encodeURIComponent(inp.value.trim()); };
    inp.addEventListener("input", sync);
    sync();
  });

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
    if (msg) msg.textContent = form.dataset.nlDone || "Thanks! Your first newsletter arrives tomorrow morning.";
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
      // The class form (data-kind="class") is always weekly; the event form is always one-off
      const weekly = eventForm.dataset.kind === "class" || (checked("recurrence") || {}).value === "weekly";
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
      // Class form: "£8 a session", "£60 a term"; a free trial is added after the price
      const per = val("#cl-per");
      const trial = $('input[name="free_trial"]', eventForm);
      const price = val("#ev-price") ? val("#ev-price") + (per ? ` a ${per}` : "") : "Free";
      if (out("price")) out("price").textContent = price + (trial && trial.checked && val("#ev-price") ? ", first class free" : "");
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

  /* 6d. Chip rows that run off the edge (.chips--scroll, and every chip row on phones).
        A mouse has no sideways scroll, so: arrow buttons at each end, drag to scroll, and the
        selected chip scrolled into view. Touch keeps its native swipe; the arrows are hidden there. */
  $$(".chips:not(.chips--wrap)").forEach((row) => {
    const rail = document.createElement("div");
    rail.className = "chips-rail";
    row.parentNode.insertBefore(rail, row);
    rail.appendChild(row);
    const btn = (dir, label, icon) => {
      const b = document.createElement("button");
      b.type = "button";
      b.className = `chips-rail__btn chips-rail__btn--${dir}`;
      b.setAttribute("aria-label", label);
      b.tabIndex = -1; // keyboard users tab through the chips themselves
      b.innerHTML = ICON(icon);
      b.addEventListener("click", () => row.scrollBy({ left: (dir === "prev" ? -1 : 1) * row.clientWidth * 0.7, behavior: "smooth" }));
      rail.appendChild(b);
      return b;
    };
    const prev = btn("prev", "Scroll left", "chevron-left"), next = btn("next", "Scroll right", "chevron");
    const update = () => {
      const max = row.scrollWidth - row.clientWidth;
      prev.hidden = row.scrollLeft <= 2;
      next.hidden = row.scrollLeft >= max - 2;
      rail.classList.toggle("has-prev", !prev.hidden);
      rail.classList.toggle("has-next", !next.hidden);
    };
    row.addEventListener("scroll", update, { passive: true });
    if ("ResizeObserver" in window) new ResizeObserver(update).observe(row);
    else window.addEventListener("resize", update);

    // Drag with the mouse. A drag of more than a few pixels cancels the click, so you don't land on another category.
    let startX = 0, startLeft = 0, down = false, dragged = false;
    row.addEventListener("dragstart", (e) => e.preventDefault()); // stop the browser dragging the link itself
    row.addEventListener("pointerdown", (e) => {
      if (e.pointerType !== "mouse" || e.button !== 0 || row.scrollWidth <= row.clientWidth) return;
      down = true; dragged = false; startX = e.clientX; startLeft = row.scrollLeft;
    });
    row.addEventListener("pointermove", (e) => {
      if (!down) return;
      const dx = e.clientX - startX;
      if (!dragged && Math.abs(dx) > 5) {
        dragged = true;
        row.classList.add("is-dragging");
        row.setPointerCapture(e.pointerId);
      }
      if (dragged) row.scrollLeft = startLeft - dx;
    });
    const end = () => {
      if (!down) return;
      down = false;
      row.classList.remove("is-dragging");
    };
    row.addEventListener("pointerup", end);
    row.addEventListener("pointercancel", end);
    row.addEventListener("click", (e) => {
      if (dragged) { e.preventDefault(); e.stopPropagation(); dragged = false; }
    }, true);

    // Bring the selected chip into view (e.g. a category near the end of the row)
    const cur = $(".chip[aria-current], .chip.is-active, .chip[aria-pressed=\"true\"]", row);
    if (cur && row.scrollWidth > row.clientWidth) {
      const r = row.getBoundingClientRect(), c = cur.getBoundingClientRect();
      if (c.left < r.left || c.right > r.right) row.scrollLeft += c.left - r.left - (r.width - c.width) / 2;
    }
    update();
  });

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
    // Search page only (data-all): events, weekly classes and offers beside businesses, one tab each
    const ALL = dir.hasAttribute("data-all");
    const tabsEl = $("[data-tabs]", dir);
    const groupsEl = $("[data-groups]", dir);
    const afterEl = $("[data-after]", dir);

    const params = () => {
      const u = new URLSearchParams(location.search);
      return {
        type: u.get("type") || "",
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
      const badge = tier === "unclaimed" ? `<span class="badge badge--unclaimed">Information imported from public data</span>` :
        `<span class="badge badge--verified">${ICON("verified")}Verified by the business</span>`;
      if (tier !== "unclaimed") {
        // Claimed listing: photo, tagline, verified; Premium and Local Partner are featured (brief 01, 6.2)
        const top = tier === "premium" || tier === "partner";
        // biz-row--media: a photo or logo, shown on top of the card on phones
        return `<article class="biz-row${top ? " biz-row--featured" : ""}${x.im ? " biz-row--media" : ""}">
        <div class="ph${x.lg ? " ph--logo" : x.im ? " ph--photo" : ""}">${x.im ? `<img src="${BASE}${esc(x.im)}" alt="" width="1200" height="800" loading="lazy" decoding="async">` : ICON(x.i || "store")}</div>
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

    /* ---- Search everything (search page). Common words open the matching tab: "events", "what's on",
       "this weekend" → What's On; "classes", "lessons" → Classes; "offers", "deals" → Offers. The other
       words still filter that tab ("dance classes", "pizza deals", "kids events this weekend"). ---- */
    const TYPES = [["all", "All"], ["events", "What&rsquo;s On"], ["classes", "Classes"], ["offers", "Offers"], ["businesses", "Businesses"]];
    const LABEL = Object.fromEntries(TYPES);
    const NOUN = { events: ["event", "events"], classes: ["class", "classes"], offers: ["offer", "offers"], businesses: ["business", "businesses"] };
    const SECTION = { events: ["/events/", "See everything on What&rsquo;s On", "calendar"], classes: ["/events/classes/", "See all weekly classes", "repeat"], offers: ["/offers/", "See all offers", "tag"] };
    const INTENTS = [
      ["events", ["what's on", "whats on", "what is on", "things to do", "this weekend", "this week", "weekend", "tonight", "today", "tomorrow", "events", "event"]],
      ["classes", ["weekly classes", "classes", "class", "lessons", "lesson", "courses", "course"]],
      ["offers", ["promo codes", "offers", "offer", "deals", "deal", "discounts", "discount", "vouchers", "voucher", "promotions"]],
    ];
    const WHEN = { today: "today", tonight: "today", tomorrow: "tomorrow", "this weekend": "weekend", weekend: "weekend", "this week": "week" };
    const WHEN_LABEL = { today: "today", tomorrow: "tomorrow", weekend: "this weekend", week: "this week" };
    const STOP = new Set(["in", "near", "me", "for", "the", "a", "an", "and", "at", "on", "of", "to", "any", "find", "show", "local", "harrow"]);
    const ALIAS = {
      kids: ["kid", "child", "junior", "toddler", "teen", "under ", "family", "mini"], children: ["kid", "child", "junior", "toddler", "family"],
      football: ["football", "soccer"], soccer: ["football", "soccer"], dance: ["dance", "ballet"], dancing: ["dance", "ballet"],
      fitness: ["fitness", "yoga", "spin", "bootcamp"], exercise: ["fitness", "yoga", "spin", "bootcamp"], tutoring: ["tuition", "tutor"], tutor: ["tuition", "tutor"],
    };
    const DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];   // classes: wd 0 = Monday
    const MON = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const WD = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    const norm = (s) => (s || "").toLowerCase().replace(/[‘’]/g, "'");
    const words = (s) => norm(s).replace(/[^a-z0-9' ]+/g, " ").split(/\s+/).filter(Boolean);
    // "plumbers" finds "plumber"
    const hasWord = (hay, t) => hay.includes(t) || (t.length > 3 && t.endsWith("s") && hay.includes(t.slice(0, -1)));
    const has = (hay, t) => hasWord(hay, t) || (ALIAS[t] || []).some((a) => hay.includes(a));
    const parseQ = (q) => {
      let s = ` ${words(q).join(" ")} `;
      let intent = "", when = "";
      for (const [type, list] of INTENTS) for (const w of list) {
        if (!s.includes(` ${w} `) || (intent && intent !== type)) continue;
        intent = type;
        if (WHEN[w] && !when) when = WHEN[w];
        s = s.replace(` ${w} `, " ");
      }
      return { intent, when, rest: s.trim().split(" ").filter((w) => w && !STOP.has(w)) };
    };
    const rank = (list, terms) => !terms.length ? list : list.map((x, i) => {
      let score = 0;
      for (const t of terms) {
        if (!has(x._h, t)) return null;
        score += x._n.startsWith(t) ? 6 : x._n.includes(t) ? 4 : 1;
      }
      return [score, i, x];
    }).filter(Boolean).sort((a, b) => b[0] - a[0] || a[1] - b[1]).map((a) => a[2]);
    const addDays = (iso, n) => { const d = new Date(iso + "T12:00:00"); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
    const onWhen = (x, when) => {
      const T = INDEX.today;
      if (when === "today") return x.d === T;
      if (when === "tomorrow") return x.d === addDays(T, 1);
      if (when === "weekend") return x.d >= INDEX.weekend[0] && x.d <= INDEX.weekend[1];
      return x.d < addDays(T, 7);
    };
    const prep = () => {
      (INDEX.events || []).forEach((x) => { x._n = norm(x.t); x._h = norm([x.t, x.c, x.v, x.an, x.ag, x.o, x.p].join(" ")); });
      (INDEX.classes || []).forEach((x) => { x._n = norm(x.t); x._h = norm([x.t, x.c, x.v, x.an, x.ag, x.o, x.p, "weekly every " + DAYS[x.wd]].join(" ")); });
      (INDEX.offers || []).forEach((x) => { x._n = norm(x.t); x._h = norm([x.t, x.vl, x.b, (INDEX.cats[x.c] || {}).n, INDEX.areas[x.a]].join(" ")); });
    };

    // Cards: the same markup as the What's On list and the Offers page
    const FEAT = `<span class="badge badge--featured">${ICON("star")}Featured</span>`;
    const SAMPLE = `<span class="badge badge--sample" title="Made-up content for the preview. Removed before launch.">${ICON("alert")}Sample</span>`;
    const media = (x, cls) => x.im ?
      `<div class="${cls}${x.lg ? " ph--logo" : " ph--photo"}"><img src="${BASE}${esc(x.im)}" alt="" width="1200" height="800" loading="lazy" decoding="async"></div>` :
      `<div class="${cls}">${ICON(x.i || "calendar")}</div>`;
    const eventRow = (x) => {
      const d = new Date(x.d + "T12:00:00");
      return `<article class="event-row${x.f ? " event-row--featured" : ""}">
        <time class="date-tile" datetime="${x.d}"><span class="date-tile__m">${MON[d.getMonth()]}</span><span class="date-tile__d">${d.getDate()}</span><span class="date-tile__w">${WD[d.getDay()]}</span></time>
        ${media(x, x.f ? "ph ph--red" : "ph")}
        <div>
          <div class="cluster mb-2">${x.f ? FEAT : x.sm ? SAMPLE : ""}<span class="biz-card__cat">${esc(x.c)}</span></div>
          <h3 class="event-row__title"><a href="${BASE}${x.h}">${esc(x.t)}</a></h3>
          <p class="card__meta"><span>${ICON("clock")}${esc(x.tm)}</span><span>${ICON("pin")}${esc(x.v)}, ${esc(x.an)}</span>${x.ag ? `<span>${ICON("people")}${esc(x.ag)}</span>` : ""}</p>
        </div>
        <div class="event-row__side"><strong>${esc(x.p || "")}</strong></div>
      </article>`;
    };
    const classRow = (x) => `<article class="event-row${x.f ? " event-row--featured" : ""}">
        <span class="date-tile date-tile--day"><span class="date-tile__m">Every</span><span class="date-tile__d">${DAYS[x.wd].slice(0, 3)}</span></span>
        ${media(x, x.f ? "ph ph--dark" : "ph")}
        <div>
          <div class="cluster mb-2">${x.f ? FEAT : x.sm ? SAMPLE : ""}<span class="badge badge--outline">${ICON("repeat")}Weekly class</span><span class="biz-card__cat">${esc(x.c)}</span></div>
          <h3 class="event-row__title"><a href="${BASE}${x.h}">${esc(x.t)}</a></h3>
          <p class="card__meta"><span>${ICON("repeat")}Every ${DAYS[x.wd]}, ${esc(x.tm)}</span><span>${ICON("pin")}${esc(x.v)}, ${esc(x.an)}</span>${x.ag ? `<span>${ICON("people")}${esc(x.ag)}</span>` : ""}</p>
        </div>
        <div class="event-row__side"><strong>${esc(x.p || "")}</strong></div>
      </article>`;
    const offerCard = (x) => {
      const soon = /^Ends (in|today|tomorrow)/.test(x.e || "");
      const tags = (x.f ? FEAT : "") + (x.sub ? `<span class="badge badge--sub">${ICON("lock")}Subscriber-only</span>` : "") +
        (soon ? `<span class="badge badge--soon">${ICON("clock")}Ends soon</span>` : "");
      return `<a class="offer-card${x.f ? " offer-card--featured" : ""}" href="${BASE}${x.h}">
        <span class="offer-card__top"><span class="biz-card__cat">${esc((INDEX.cats[x.c] || {}).n || "")}</span>${tags}</span>
        <strong class="offer-card__value">${esc(x.vl)}</strong>
        <span class="offer-card__title">${esc(x.t)}</span>
        <span class="offer-card__biz">${esc(x.b)} &middot; ${esc(INDEX.areas[x.a] || "")}</span>
        <span class="offer-card__foot"><span>${ICON("calendar", "ho-icon ho-icon--sm")}${esc(x.e || "")}</span><span class="offer-card__go">View offer ${ICON("arrow", "ho-icon ho-icon--sm")}</span></span>
      </a>`;
    };
    const cards = (k, xs) => k === "offers" ? `<div class="offer-cards">${xs.map(offerCard).join("")}</div>` : xs.map(k === "events" ? eventRow : classRow).join("");

    // Tabs, the "All" overview and the What's On / Classes / Offers tabs. Returns the tab shown.
    const renderGroups = (p, items) => {
      const qi = p.q ? parseQ(p.q) : { intent: "", when: "", rest: [] };
      const full = words(p.q).filter((w) => !STOP.has(w));
      const termsFor = (k) => (qi.intent === k ? qi.rest : full);
      const inArea = (x) => !p.area || x.a === p.area;
      let ev = rank((INDEX.events || []).filter(inArea), termsFor("events"));
      if (qi.intent === "events" && qi.when) ev = ev.filter((x) => onWhen(x, qi.when));
      const G = {
        events: ev,
        classes: rank((INDEX.classes || []).filter(inArea), termsFor("classes")),
        offers: rank((INDEX.offers || []).filter((x) => inArea(x) && (!p.cat || x.c === p.cat)), termsFor("offers")),
        businesses: items,
      };
      const total = G.events.length + G.classes.length + G.offers.length + items.length;
      // A common word opens its tab, unless that tab is empty and other tabs have results ("first class plumbing")
      const auto = qi.intent && (G[qi.intent].length || !total) ? qi.intent : "all";
      const type = LABEL[p.type] ? p.type : (p.q ? auto : "all");
      const href = (k) => { const u = new URLSearchParams(location.search); u.delete("page"); u.set("type", k); return "?" + u.toString(); };
      const num = (n) => n.toLocaleString("en-GB");
      const forQ = p.q ? ` for &ldquo;${esc(p.q)}&rdquo;` : "";

      tabsEl.innerHTML = TYPES.map(([k, label]) => {
        const n = k === "all" ? (p.q ? total : null) : G[k].length;
        return `<a href="${href(k)}" data-nav${k === type ? ' aria-current="page"' : ""}>${label}${n === null ? "" : `<span class="count">${num(n)}</span>`}</a>`;
      }).join("");
      tabsEl.hidden = false;
      const curTab = $("[aria-current]", tabsEl);   // phones: the tabs scroll sideways, keep the current one in view
      if (curTab) tabsEl.scrollLeft = Math.max(0, curTab.offsetLeft - 16);
      groupsEl.innerHTML = "";
      afterEl.innerHTML = "";

      if (type === "all" && p.q) {
        if (countEl) countEl.innerHTML = `<strong>${num(total)}</strong> ${total === 1 ? "result" : "results"}${forQ}`;
        // Every group gets the same preview: the top 3 and a "See all" link to its tab.
        // Only when businesses are the one group with results does All show the full, paged list.
        const keys = ["events", "classes", "offers", "businesses"].filter((k) => G[k].length);
        if (keys.length > 1 || (keys.length === 1 && keys[0] !== "businesses")) groupsEl.innerHTML = keys.map((k) => `<section class="search-group">
          <div class="search-group__head"><h2>${LABEL[k]}<span class="count">${num(G[k].length)}</span></h2>${G[k].length > 3 ? `<a href="${href(k)}" data-nav>See all ${num(G[k].length)} ${NOUN[k][1]}${ICON("arrow", "ho-icon ho-icon--sm")}</a>` : ""}</div>
          ${k === "businesses" ? G[k].slice(0, 3).map(row).join("") : cards(k, G[k].slice(0, 3))}
        </section>`).join("");
      } else if (SECTION[type]) {
        const xs = G[type];
        const whenOnly = type === qi.intent && qi.when && !qi.rest.length;
        if (countEl) countEl.innerHTML = `<strong>${num(xs.length)}</strong> ${NOUN[type][xs.length === 1 ? 0 : 1]}${whenOnly ? " " + WHEN_LABEL[qi.when] : forQ}`;
        const [path, more, ic] = SECTION[type];
        resultsEl.innerHTML = xs.length ? cards(type, xs) :
          `<div class="empty">${ICON(ic)}<h3>No ${NOUN[type][1]} found</h3><p>Try a different word, or another area.</p><a class="ho-btn ho-btn--primary" href="${BASE}${path}">${more}</a></div>`;
        const links = [`<a href="${BASE}${path}">${more}</a>`];
        if (p.q && items.length) links.push(`<a href="${href("businesses")}" data-nav>see ${num(items.length)} ${items.length === 1 ? "business" : "businesses"} matching &ldquo;${esc(p.q)}&rdquo;</a>`);
        afterEl.innerHTML = `<div class="limited-note mt-6">${ICON("info")}<p class="mb-0">${links.join(", or ")}.</p></div>`;
      } else if (type === "businesses" && qi.intent && G[qi.intent].length) {
        const k = qi.intent;
        afterEl.innerHTML = `<div class="limited-note mt-6">${ICON(SECTION[k][2])}<p class="mb-0">Looking for ${NOUN[k][1]}? <a href="${href(k)}" data-nav>See ${num(G[k].length)} ${NOUN[k][G[k].length === 1 ? 0 : 1]}${qi.rest.length ? ` for &ldquo;${esc(qi.rest.join(" "))}&rdquo;` : ""}</a>.</p></div>`;
      }
      return type;
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
            if (!hasWord(hay, t)) return null;
            score += name.startsWith(t) ? 6 : name.includes(t) ? 4 : 1;
          }
          return [score, x];
        }).filter(Boolean).sort((a, b) => b[0] - a[0]).map((a) => a[1]);
      }
      // Ranking rule (brief 01, 6.2): paid featured first, then claimed, then unclaimed
      items = items.map((x, i) => [x, i]).sort((a, b) => (tierRank[a[0].t || "unclaimed"] - tierRank[b[0].t || "unclaimed"]) || (p.sort === "name" ? a[0].n.localeCompare(b[0].n) : a[1] - b[1])).map((a) => a[0]);

      const type = ALL ? renderGroups(p, items) : "businesses";
      const overview = type === "all" && !!p.q;   // "All" with a search: count and sections set by renderGroups
      const grouped = overview && !!groupsEl.innerHTML;   // businesses are a section of the overview: no list, no pager
      const bizView = (type === "all" || type === "businesses") && !grouped;
      if (grouped) resultsEl.innerHTML = "";
      const bizNote = $("[data-biz-note]", dir);   // "Can't find a business?" only where businesses are listed
      if (bizNote) bizNote.hidden = !bizView && !grouped;

      const total = items.length;
      const pages = bizView ? Math.max(1, Math.ceil(total / PAGE)) : 1;
      const page = Math.min(p.page, pages);
      const slice = items.slice((page - 1) * PAGE, page * PAGE);

      if (bizView) {
        if (countEl && !overview) countEl.innerHTML = `<strong>${total.toLocaleString("en-GB")}</strong> ${total === 1 ? "business" : "businesses"}${p.q ? ` for &ldquo;${esc(p.q)}&rdquo;` : ""}`;
        resultsEl.innerHTML = slice.length ? slice.map(row).join("") : overview && groupsEl.innerHTML ? "" :
          `<div class="empty">${ICON("search")}<h3>${overview ? "Nothing found" : "No businesses found"}</h3><p>Try a different word, or clear a filter. Can&rsquo;t find a business you know? Add it to the Hub.</p><a class="ho-btn ho-btn--primary" href="${BASE}/add-business/">${ICON("plus")}Add a business</a></div>`;
      }

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
        set("q", p.q); set("type", p.type); if (!fixed.cat) set("cat", p.cat); if (!fixed.area) set("area", p.area); set("web", p.web); set("phone", p.phone); set("sort", p.sort);
        $$("input[name=cat][type=radio]", form).forEach((r) => { r.checked = r.value === p.cat; });
        $$("input[name=area][type=radio]", form).forEach((r) => { r.checked = r.value === p.area; });
      }
    };

    // Clean empty params on submit so URLs stay tidy
    if (form) form.addEventListener("submit", (e) => {
      e.preventDefault();
      const u = new URLSearchParams();
      new FormData(form).forEach((v, k) => { if (v !== "" && !(k === "sort" && v === "relevance")) u.set(k, v); });
      if ((u.get("q") || "") !== params().q) u.delete("type");   // a new search picks its own tab; filter changes keep it
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
      const a = e.target.closest("[data-pager] a, [data-active] a, a[data-nav]");
      if (!a || !INDEX) return;
      e.preventDefault();
      history.pushState(null, "", a.getAttribute("href"));
      render();
      if (a.hasAttribute("data-nav") && tabsEl) tabsEl.scrollIntoView({ block: "nearest" });
      else resultsEl.scrollIntoView({ block: "start" });
    });

    fetch(BASE + "/data/hub-index.json").then((r) => r.json()).then((d) => { INDEX = d; if (ALL) prep(); render(); })
      .catch(() => { /* keep the server-rendered first page */ });
  }
})();
