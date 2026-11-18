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
    // Once everything is done the checklist has nothing left to say: hide it
    if (done === items.length) setup.hidden = true;
  }
  // Account menu as a sideways strip (phones): scroll the current page's tab into view
  const navCur = $(".dash-nav a[aria-current]");
  if (navCur) {
    const strip = navCur.closest("ul");
    if (strip && strip.scrollWidth > strip.clientWidth) strip.scrollLeft = Math.max(0, navCur.offsetLeft - strip.offsetLeft - 16);
  }
  // Billing: the failed-payment example only shows with ?state=failed (WordPress: after invoice.payment_failed)
  if (new URLSearchParams(location.search).get("state") === "failed") $$('[data-example-state="failed"]').forEach((el) => { el.hidden = false; });

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
    // Each edition is its own opt-in: the reader gets only the ones ticked (or the one a dedicated form names)
    const ticks = $$('input[type=checkbox][name="editions[]"]', form);
    const NAMES = { daily: "Daily News (every morning)", weekend: "The Weekend (Thursdays)", business: "Harrow Business (Mondays)" };
    if (ticks.length) {
      const chosen = ticks.filter((t) => t.checked).map((t) => NAMES[t.value] || t.value);
      if (!chosen.length) { if (msg) msg.textContent = "Tick at least one newsletter."; return; }
      const list = chosen.length > 1 ? chosen.slice(0, -1).join(", ") + " and " + chosen[chosen.length - 1] : chosen[0];
      form.dataset.nlDone = `Thanks! Check your inbox to confirm. You've signed up to ${list}.`;
    }
    const btn = $("button[type=submit]", form);
    if (btn) { btn.innerHTML = ICON("check") + "<span>You&rsquo;re signed up</span>"; btn.disabled = true; }
    if (msg) msg.textContent = form.dataset.nlDone || "Thanks! Your first newsletter arrives tomorrow morning.";
    // Preview: remember the reader, like the confirmation link does, so other sign-up forms don't ask again
    document.cookie = `ho_sub_preview=${encodeURIComponent(email.value.trim())}; path=/; max-age=${60 * 60 * 24 * 90}; SameSite=Lax`;
  }));

  /* 5i. Existing subscribers aren't asked to sign up again. Known reader (confirmation-link cookie, or signed in as a
         subscriber): each newsletter sign-up form becomes a short "you're signed up" line with a link to manage editions.
         WordPress: the same check as GET /wp-json/ho/v1/codes ({subscriber: true}); the preview reads its own cookie. */
  const knownReader = (document.cookie.split("; ").find((c) => c.startsWith("ho_sub_preview=")) || "").slice(15);
  if (knownReader) {
    const who = decodeURIComponent(knownReader);
    $$("[data-nl-form]").forEach((form) => {
      form.innerHTML = `<p class="nl-known" role="status">${ICON("check")}<span>You&rsquo;re signed up to Harrow Online newsletters${who.includes("@") ? ` as <strong>${esc(who)}</strong>` : ""}. <a href="https://harrowonline.org/newsletter/">Choose your editions</a></span></p>`;
    });
  }

  /* 5h. Listing edit: the card preview follows the name, category, type and summary as they're typed */
  const listingForm = $("[data-listing-form]");
  if (listingForm) {
    const sync = () => $$("[data-lp-in]", listingForm).forEach((inp) => {
      const out = $(`[data-lp-out="${inp.dataset.lpIn}"]`);
      if (!out) return;
      const v = inp.tagName === "SELECT" ? inp.options[inp.selectedIndex].text : inp.value.trim();
      out.textContent = v || out.dataset.empty || "";
    });
    listingForm.addEventListener("input", sync);
    listingForm.addEventListener("change", sync);
  }

  /* 5g. Add a business: "Recommend a business" wording (?recommend=1), duplicate check against the Hub, thank-you.
         WordPress: the duplicate check and the thank-you come from the server; the form posts to the listing queue. */
  const addForm = $("[data-add-form]");
  if (addForm) {
    const recommend = new URLSearchParams(location.search).get("recommend") === "1";
    if (recommend) {
      const set = (sel, html) => { const el = $(sel); if (el) el.innerHTML = html; };
      set("[data-add-title]", "Recommend a business");
      set("[data-add-crumb]", "Recommend a business");
      set("[data-add-lead]", "Know a Harrow business that&rsquo;s missing? Tell us and we&rsquo;ll add it. Our team checks every one first.");
      set("[data-add-declare]", "The details are correct as far as I know, and I agree to the <a href=\"" + BASE + "/terms/\">terms</a>.");
      set("[data-add-submit]", "Send recommendation");
      $$("[data-owner-only]").forEach((el) => { el.hidden = true; $$("input, select, textarea", el).forEach((f) => { f.disabled = true; }); });
      $$("[data-recommend-only]").forEach((el) => { el.hidden = false; });
      document.title = document.title.replace("Add your business", "Recommend a business");
    }
    // Same name and postcode as a business already on the Hub: say so, link to it and offer the claim
    const dupe = $("[data-add-dupe]");
    let IDX = null;
    const key = (s) => (s || "").toLowerCase().replace(/&/g, "and").replace(/[^a-z0-9]/g, "");
    const check = () => {
      if (!IDX || !dupe) return;
      const n = key($("#a-name").value), pc = key($("#a-pc").value);
      const hit = n.length > 2 && pc.length > 4 && IDX.items.find((x) => key(x.pc) === pc && (key(x.n) === n || key(x.n).startsWith(n) || n.startsWith(key(x.n))));
      dupe.hidden = !hit;
      if (hit) {
        const a = $("[data-add-dupe-link]", dupe);
        a.href = `${BASE}/business/${hit.k}/`;
        a.textContent = `${hit.n}, ${hit.ad ? hit.ad + ", " : ""}${hit.pc}`;
        const c = $("[data-add-dupe-claim]", dupe);
        if (c) c.href = `${BASE}/claim/?listing=${hit.k}`;
      }
    };
    fetch(BASE + "/data/hub-index.json").then((r) => r.json()).then((d) => { IDX = d; check(); }).catch(() => {});
    ["#a-name", "#a-pc"].forEach((s) => { const el = $(s); if (el) el.addEventListener("change", check); });
    // Preview: the browser has already checked the required fields; show the thank-you in place
    addForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const name = esc($("#a-name").value.trim());
      const box = document.createElement("div");
      box.className = "add-biz__done form-card";
      box.setAttribute("role", "status");
      box.innerHTML = `<span class="add-biz__done-icon">${ICON("check")}</span>
        <h2>${recommend ? "Thanks for the recommendation" : "Thanks, we&rsquo;re checking it"}</h2>
        <p>We&rsquo;ve got <strong>${name}</strong>. A member of the Harrow Online team will check it, usually within 2 working days${recommend ? "" : ", and email you either way"}.</p>
        <div class="cluster"><a class="ho-btn ho-btn--primary" href="${BASE}/">Back to the Hub</a>${recommend ? `<a class="ho-btn ho-btn--outline" href="${BASE}/add-business/?recommend=1">Recommend another</a>` : `<a class="ho-btn ho-btn--outline" href="${BASE}/account/">Go to my account</a>`}</div>`;
      addForm.replaceWith(box);
      const check2 = $(".add-biz__check");
      if (check2) check2.remove();
      box.scrollIntoView({ block: "center" });
    });
  }

  /* 6. Subscriber-only offers. The code is NEVER in the page (pages are cached and public). Who sees codes:
        - a reader who tapped the confirmation link in their email: WordPress checks the email is a newsletter
          subscriber (Beehiiv), sets a signed, HttpOnly "ho_sub" cookie (90 days) and redirects to the offer
          without the token. They stay recognised while signed out, on every offer, until the cookie ends.
        - a signed-in reader whose account email is a subscriber.
        - anyone else: the unlock form, no code.
        WordPress flow:
          POST /wp-json/ho/v1/unlock {email, offer}  → emails a confirm link (and signs them up if they are not yet)
          GET  /hub/offers/{slug}/?ho_confirm=TOKEN   → checks the token, sets the cookie, redirects to the clean URL
          GET  /wp-json/ho/v1/codes?offers=a,b        → (credentials: include, never cached) {subscriber: true,
               email: "…", codes: {slug: "CODE"}} for a known subscriber, otherwise {subscriber: false}
          POST /wp-json/ho/v1/forget                  → clears the cookie ("Not you?")
        The preview has no server: ?ho_confirm=… sets a readable preview cookie instead, and the codes shown are
        made-up preview codes, so no real subscriber code is ever published here. */
  const SUB_COOKIE = "ho_sub_preview";
  const subQuery = new URLSearchParams(location.search);
  if (subQuery.has("ho_confirm")) {
    let who = "";
    try { who = sessionStorage.getItem("ho_sub_email") || ""; } catch (_) { /* private mode */ }
    document.cookie = `${SUB_COOKIE}=${encodeURIComponent(who || "1")}; path=/; max-age=${60 * 60 * 24 * 90}; SameSite=Lax`;
    subQuery.delete("ho_confirm");
    history.replaceState(null, "", location.pathname + (subQuery.toString() ? "?" + subQuery : "") + location.hash);
  }
  const subCookie = () => (document.cookie.split("; ").find((c) => c.startsWith(SUB_COOKIE + "=")) || "").slice(SUB_COOKIE.length + 1);
  // Preview stand-in for GET /wp-json/ho/v1/codes
  const fetchCodes = (slugs) => {
    const who = subCookie();
    if (!who) return Promise.resolve({ subscriber: false });
    const fake = (s) => "PREVIEW" + String([...s].reduce((h, c) => (h * 31 + c.charCodeAt(0)) % 9000, 7) + 1000);
    return Promise.resolve({ subscriber: true, email: who === "1" ? "" : decodeURIComponent(who), codes: Object.fromEntries(slugs.map((s) => [s, fake(s)])) });
  };
  const gates = $$("[data-sub-gate]"), cardCodes = $$("[data-sub-code]");
  if (gates.length || cardCodes.length) {
    const slugs = [...new Set([...gates, ...cardCodes].map((el) => el.dataset.offer))];
    fetchCodes(slugs).then((r) => {
      if (!r.subscriber) return;
      const who = r.email ? ` as <strong>${esc(r.email)}</strong>` : "";
      const forget = `<button class="link-btn" type="button" data-sub-forget>Not you?</button>`;
      gates.forEach((g) => {
        const code = r.codes[g.dataset.offer];
        g.innerHTML = (g.dataset.hasCode === "1" && code ?
          `<div class="voucher__row"><span class="voucher__code">${esc(code)}</span><button class="ho-btn ho-btn--dark" type="button" data-copy="${esc(code)}" data-copied="Code copied">Copy code</button></div>` :
          `<p class="voucher__state">No code needed</p>`) +
          `<p class="sub-known">${ICON("check", "ho-icon ho-icon--sm")}<span>Unlocked for subscribers${who}. ${forget}</span></p>`;
      });
      cardCodes.forEach((c) => {
        const code = r.codes[c.dataset.offer];
        if (!code) return;
        c.textContent = code;
        c.classList.remove("offer__code--pending");
        const card = c.closest(".offer");
        const lock = card && $(".offer__lock", card);
        if (lock) lock.remove();
        if (card) card.classList.remove("offer--locked");
      });
    });
  }
  document.addEventListener("click", (e) => {
    if (!e.target.closest("[data-sub-forget]")) return;
    document.cookie = `${SUB_COOKIE}=; path=/; max-age=0; SameSite=Lax`;   // WordPress: POST /wp-json/ho/v1/forget
    location.reload();
  });
  // Unlock form: starts the email confirmation. Preview: a link stands in for the email's confirm link.
  $$("[data-unlock]").forEach((form) => form.addEventListener("submit", (e) => {
    e.preventDefault();
    const lock = form.closest(".offer__lock");
    if (!lock) return;
    const raw = (($("input[type=email]", form) || {}).value || "").trim();
    const email = esc(raw || "your inbox");
    const offer = ($("input[name=offer]", form) || {}).value || "";
    try { sessionStorage.setItem("ho_sub_email", raw); } catch (_) { /* private mode */ }   // never in the URL
    const intro = form.closest("[data-sub-gate]") && $("[data-sub-gate] > p.small", form.closest(".voucher") || document);
    if (intro) intro.remove();   // the "Check your email" message says it; no second "Your code appears here" line
    const back = `${BASE}/offers/${offer}/?ho_confirm=preview`;
    lock.innerHTML = `<p role="status">${ICON("mail", "ho-icon ho-icon--sm")}Check ${email} and tap the confirmation link. Your code appears here once you have confirmed.</p>
      <p class="sub-preview">Preview: <a href="${back}">open the confirmation link</a></p>`;
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

  /* 6b. What's On list filters: date (?when=today|weekend|7days|date&date=YYYY-MM-DD), category (?cat= or cat[]=),
        area, age (age[]=), free (?free=1) and "hide weekly classes" (?classes=0). Chips and the sidebar form use the
        same parameters. Each row carries data-date, data-cat, data-area, data-ages, data-free and data-kind.
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
    const cats = [...q.getAll("cat"), ...q.getAll("cat[]")].filter(Boolean);
    const ages = [...q.getAll("age"), ...q.getAll("age[]")].filter(Boolean);
    const area = q.get("area") || "";
    const free = q.get("free") === "1";
    const noClasses = q.get("classes") === "0";
    const other = cats.length || ages.length || area || free || noClasses;
    const rowOn = (row, useDate) => (!useDate || !range || (row.dataset.date >= range[0] && row.dataset.date <= range[1]))
      && (!cats.length || cats.includes(row.dataset.cat) || (cats.includes("classes") && row.dataset.kind === "class")
        // "Family": the Family category, plus anything that welcomes children (ages under 5 or 5 to 11)
        || (cats.includes("family") && /(^| )(under-5|5-11)( |$)/.test(row.dataset.ages || "")))
      && (!area || row.dataset.area === area)
      && (!ages.length || ages.some((a) => (row.dataset.ages || "").split(" ").includes(a)))
      && (!free || row.dataset.free === "1")
      && (!noClasses || row.dataset.kind !== "class");
    if (range || other) {
      let n = 0;
      $$(".day-group[data-date]", evList).forEach((g) => {
        const inDates = !range || (g.dataset.date >= range[0] && g.dataset.date <= range[1]);
        let k = 0;
        $$(".event-row", g).forEach((row) => {
          const on = inDates && rowOn(row, false);
          row.hidden = !on;
          row.style.display = on ? "" : "none";
          if (on) k += 1;
        });
        g.hidden = !k;
        g.style.display = k ? "" : "none";
        const c = $("[data-day-count]", g);
        if (c && other) c.textContent = `${k} ${k === 1 ? "thing" : "things"} on`;
        n += k;
      });
      // Featured events block: rows inside the chosen dates and filters; hide the block when none are
      $$("[data-featured-group]", evList).forEach((g) => {
        let k = 0;
        $$(".event-row", g).forEach((row) => {
          const on = rowOn(row, true);
          row.hidden = !on;
          row.style.display = on ? "" : "none";
          if (on) k += 1;
        });
        g.hidden = !k;
        g.style.display = k ? "" : "none";
        const c = $("[data-day-count]", g);
        if (c) c.textContent = range ? `${k} on these dates` : `${k} coming up`;
        n += k;
      });
      const span = range ? (range[0] === range[1] ? nice(range[0]) : `${nice(range[0])} to ${nice(range[1])}`) : "";
      const count = $("[data-events-count]", evList);
      if (count) {
        const what = [cats.length ? cats.map((c) => c.replace(/-/g, " ")).join(", ") : "", free ? "free" : ""].filter(Boolean).join(", ");
        count.innerHTML = `<strong>${n}</strong> ${n === 1 ? "event or class" : "events and classes"}${what ? ` (${esc(what)})` : ""}${span ? `, ${esc(span)}` : ""}`;
      }
      const shown = $("[data-events-range]", evList);
      if (shown && span) shown.textContent = `Showing ${span}.`;
      const empty = $("[data-events-empty]", evList);
      if (empty) empty.hidden = n > 0;
    }
    // Chips: the one matching the page's filters is shown as selected (date chips by data-when, others by their link)
    $$(".chip", evList).forEach((c) => {
      let on = false;
      if (c.dataset.when !== undefined) on = c.dataset.when === when;
      else if ((c.getAttribute("href") || "").includes("?")) {
        const cq = new URL(c.href, location.href).searchParams;
        on = [...cq.keys()].length > 0 && [...cq.entries()].every(([k, v]) => (k === "cat" ? cats.includes(v) : q.get(k) === v));
      }
      c.toggleAttribute("aria-current", on);
    });
    // Sidebar form shows the current filters
    const form = $("#filters form");
    if (form) {
      const radio = $(`input[name="when"][value="${when}"]`, form);
      if (radio) radio.checked = true;
      const pick = $("#f-date", form);
      if (pick && when === "date" && range) { pick.value = range[0]; const box = $("#pick-date"); if (box) box.hidden = false; }
      $$('input[name="cat[]"]', form).forEach((i) => { i.checked = cats.includes(i.value); });
      $$('input[name="age[]"]', form).forEach((i) => { i.checked = ages.includes(i.value); });
      const ar = $('select[name="area"]', form); if (ar) ar.value = area;
      const fr = $('input[name="free"]', form); if (fr) fr.checked = free;
      const nc = $('input[name="classes"]', form); if (nc) nc.checked = noClasses;
    }
  }

  /* 6f. Search, category and area pages: the filter line applies as soon as a select or tick changes
         (hub.js section 11 handles the submit without a reload). */
  $$(".dline[data-directory-form]").forEach((form) => {
    const go = () => { if (form.requestSubmit) form.requestSubmit(); else form.dispatchEvent(new Event("submit", { cancelable: true })); };
    form.addEventListener("change", (e) => { if (e.target.matches("select, input[type=checkbox]")) go(); });
    // Controls outside the form (form="filters": sort, and the Search page's filters on the count line)
    $$('[form="' + form.id + '"]').forEach((el) => { if (el.matches("select, input[type=checkbox]")) el.addEventListener("change", go); });
    const kw = $("input[type=search]", form);
    if (kw) kw.addEventListener("search", go);   // the clear (x) button
  });

  /* 6i. Privacy, Terms, Data sources: highlight the section you're reading in "On this page" */
  const tocLinks = $$(".legal__toc a[href^='#']");
  if (tocLinks.length) {
    const items = tocLinks.map((a) => [a, document.getElementById(decodeURIComponent(a.hash.slice(1)))]).filter((it) => it[1]);
    let ticking = false;
    const mark = () => {
      ticking = false;
      if (!items.length) return;
      const line = 140;   // just under the sticky header
      let cur = items[0];
      items.forEach((it) => { if (it[1].getBoundingClientRect().top <= line) cur = it; });
      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) cur = items[items.length - 1];   // last section at the very bottom
      items.forEach((it) => {
        const on = it === cur;
        it[0].classList.toggle("is-active", on);
        if (on) it[0].setAttribute("aria-current", "location"); else it[0].removeAttribute("aria-current");
      });
      if (tocCurrent) tocCurrent.textContent = cur[0].textContent.trim();   // phones: the sticky bar shows the current section
      // Phones: the bar gets its white strip and divider only while it's stuck under the header
      const side = tocCurrent && tocCurrent.closest(".legal__side");
      if (side) {
        const hdr = $(".ho-header"), top = hdr ? hdr.getBoundingClientRect().bottom : 0;
        side.classList.toggle("is-stuck", window.scrollY > 0 && side.getBoundingClientRect().top <= top + 1);
      }
    };
    // Phones and tablets: the sticky "On this page" bar opens and closes the list; picking a section closes it
    const tocToggle = $("[data-toc-toggle]"), tocCurrent = $("[data-toc-current]"), tocCard = tocToggle && tocToggle.closest(".legal__card");
    const setTocOpen = (open) => { if (!tocCard) return; tocCard.classList.toggle("is-open", open); tocToggle.setAttribute("aria-expanded", open ? "true" : "false"); };
    if (tocToggle) {
      tocToggle.addEventListener("click", () => setTocOpen(!tocCard.classList.contains("is-open")));
      tocLinks.forEach((a) => a.addEventListener("click", () => setTocOpen(false)));
      document.addEventListener("keydown", (e) => { if (e.key === "Escape" && tocCard.classList.contains("is-open")) { setTocOpen(false); tocToggle.focus(); } });
    }
    window.addEventListener("scroll", () => { if (!ticking) { ticking = true; setTimeout(mark, 60); } }, { passive: true });
    window.addEventListener("resize", mark);
    mark();
  }

  /* 6h. Status icon hover names: shift the label sideways so it stays inside its card (or the screen) */
  const placeTip = (t) => {
    t.style.setProperty("--tip-x", "0px");
    const tip = getComputedStyle(t, "::after");
    const w = parseFloat(tip.width) || 0;
    if (!w) return;
    let box = { left: 0, right: document.documentElement.clientWidth };
    for (let el = t.parentElement; el && el !== document.body; el = el.parentElement) {
      if (getComputedStyle(el).overflowX !== "visible") { const r = el.getBoundingClientRect(); box = { left: Math.max(box.left, r.left), right: Math.min(box.right, r.right) }; break; }
    }
    const r = t.getBoundingClientRect(), mid = r.left + r.width / 2, pad = 6;
    let shift = 0;
    if (mid - w / 2 < box.left + pad) shift = box.left + pad - (mid - w / 2);
    else if (mid + w / 2 > box.right - pad) shift = box.right - pad - (mid + w / 2);
    t.style.setProperty("--tip-x", `${Math.round(shift)}px`);
  };
  const noHover = window.matchMedia("(hover: none)");
  let tipOpen = null, tipTimer = 0;
  const closeTip = () => { if (tipOpen) { tipOpen.classList.remove("is-tip"); tipOpen = null; } clearTimeout(tipTimer); };
  document.addEventListener("click", (e) => {
    const t = e.target.closest && e.target.closest(".card__tick");
    if (!t || !noHover.matches) { closeTip(); return; }
    e.preventDefault();          // the icon sits inside the name link: show the name, don't open the page
    e.stopPropagation();
    if (tipOpen === t) { closeTip(); return; }
    closeTip();
    t.classList.add("is-tip");
    placeTip(t);
    tipOpen = t;
    tipTimer = setTimeout(closeTip, 3000);
  }, true);
  window.addEventListener("scroll", closeTip, { passive: true });
  ["pointerover", "focusin"].forEach((ev) => document.addEventListener(ev, (e) => {
    const t = e.target.closest && (e.target.closest(".card__tick") || (e.type === "focusin" && e.target.querySelector(".card__tick")));
    if (t) placeTip(t);
  }));

  /* 6g. What's On month view: ?area= and ?cat= filter the calendar and the agenda (same data-* as the list view) */
  const monthCal = $("[data-month-cal]");
  if (monthCal) {
    const q = new URLSearchParams(location.search);
    const area = q.get("area") || "", cat = q.get("cat") || "";
    ["area", "cat"].forEach((k) => { const sel = $(`#m-${k}`); if (sel) sel.value = q.get(k) || ""; });
    if (area || cat) {
      $$(".cal__ev", monthCal).forEach((ev) => {
        const okCat = !cat || ev.dataset.cat === cat || (cat === "family" && /(^| )(under-5|5-11)( |$)/.test(ev.dataset.ages || ""));
        const on = okCat && (!area || ev.dataset.area === area);
        ev.hidden = !on;
      });
      $$(".cal__more", monthCal).forEach((m) => { m.hidden = true; });   // the "+N more" counts no longer apply
      $$(".cal__agenda .day-group", monthCal).forEach((g) => { g.hidden = !$$(".cal__ev", g).some((ev) => !ev.hidden); });
    }
  }

  /* 6e. Filter panels (Offers, Classes): filters apply as soon as they change (the Show button stays for no-JS),
         empty fields are left out of the address, the class type chosen with a chip is kept, and "Clear filters"
         shows when anything is filtered. */
  $$("[data-autofilter]").forEach((form) => {
    const q = new URLSearchParams(location.search);
    const keep = $("[data-keep-type]", form);
    if (keep) keep.value = q.get("type") || "";
    const go = $(".fpanel__go", form);
    if (go) go.hidden = true;
    const clear = $("[data-filter-clear]", form);
    if (clear) clear.hidden = ![...q.entries()].some(([k, v]) => v && k !== "v");
    form.addEventListener("submit", () => { $$("input, select", form).forEach((f) => { if (!f.value || (f.type === "checkbox" && !f.checked)) f.disabled = true; }); });
    form.addEventListener("change", () => { if (form.requestSubmit) form.requestSubmit(); else form.submit(); });
  });

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

  /* 8b. Homepage "Popular": one line only. Chips that don't fit whole are hidden (none cut in half). */
  $$(".popular--one").forEach((row) => {
    const chips = $$("a", row);
    const fit = () => {
      chips.forEach((c) => { c.hidden = false; });
      const right = row.getBoundingClientRect().right;
      let full = false;   // keep the order: once one doesn't fit, the rest go too
      chips.forEach((c) => { if (full || c.getBoundingClientRect().right > right + 0.5) { full = true; c.hidden = true; } });
    };
    fit();
    window.addEventListener("resize", fit);
  });

  /* 9. Share / copy link */
  // Delegated: also covers subscriber codes added after the page loads (section 6)
  document.addEventListener("click", async (e) => {
    const b = e.target.closest("[data-copy]");
    if (!b) return;
    try { await navigator.clipboard.writeText(b.dataset.copy || location.href); b.textContent = b.dataset.copied || "Link copied"; } catch (_) { /* ignore */ }
  });

  /* 10. Choice cards that reveal a panel: <input data-reveal="#panel-id"> */
  const syncReveal = () => $$("[data-reveal]").forEach((inp) => {
    const p = $(inp.dataset.reveal);
    if (p) p.hidden = !inp.checked;
  });
  $$("input[type=radio], input[type=checkbox]").forEach((i) => i.addEventListener("change", syncReveal));
  syncReveal();

  /* 10b. Pages opened with ?listing={slug} (claim, report): fill [data-listing-field="n|ad|pc|area|cat|link|claim"] */
  const listingSlug = new URLSearchParams(location.search).get("listing");
  if (listingSlug && $("[data-listing-field]")) {
    fetch(BASE + "/data/hub-index.json").then((r) => r.json()).then((d) => {
      const x = d.items.find((i) => i.k === listingSlug);
      if (!x) return;
      const vals = { n: x.n, ad: (x.ad || "").trim(), pc: x.pc, area: d.areas[x.a], cat: (d.cats[x.c] || {}).n, link: BASE + "/business/" + x.k + "/" };
      $$("[data-listing-field]").forEach((el) => {
        const f = el.dataset.listingField;
        if (f === "link") el.setAttribute("href", vals.link);
        else if (f === "claim") el.setAttribute("href", BASE + "/claim/?listing=" + encodeURIComponent(x.k));   // report page: claim this one
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
      // Status as a small tick after the name (Checked by Harrow Online / Verified by the business); imported keeps its label
      const [tickKind, tickText, tickIcon] = x.ck ? ["checked", "Checked by Harrow Online", "check"]
        : tier !== "unclaimed" ? ["verified", "Verified by the business", "verified"]
        : ["imported", "Information imported from public data", "globe"];
      const tick = `<span class="card__tick card__tick--${tickKind}" role="img" aria-label="${tickText}" data-tip="${tickText}"><svg class="ho-icon" aria-hidden="true"><use href="${BASE}/assets/img/icons.svg#i-${tickIcon}"/></svg></span>`;
      // last word + icon in one no-wrap span: the icon never sits on a line of its own
      // no icon for imported listings in results (only Verified and Checked stand out)
      const nameTick = tickKind === "imported" ? esc(x.n) : (() => { const n = x.n.trim(), k = n.lastIndexOf(" "); return (k > 0 ? esc(n.slice(0, k)) + " " : "") + `<span class="name-end">${esc(n.slice(k + 1))}${tick}</span>`; })();
      // Claimed listings (sample or real) show Verified; Featured for Premium and Local Partner
      // Three statuses: verified by the business (claimed), checked by Harrow Online, or imported from public data
      const badge = tier === "unclaimed" ? (x.ck ? `<span class="badge badge--checked">${ICON("check")}Checked by Harrow Online</span>` : `<span class="badge badge--unclaimed">Information imported from public data</span>`) :
        /* no Sample tag (18 Nov 2026) */ false ? `<span class="badge badge--sample" title="Made-up business for the preview. Removed before launch.">${ICON("alert")}Sample</span>`
          : x.ck ? `<span class="badge badge--checked">${ICON("check")}Checked by Harrow Online</span>`
          : `<span class="badge badge--verified">${ICON("verified")}Verified by the business</span>`;   // a sample is never Verified or Featured
      if (tier !== "unclaimed") {
        // Claimed listing: photo, tagline, verified; Premium and Local Partner are featured (brief 01, 6.2)
        const top = tier === "premium" || tier === "partner";
        // biz-row--media: a photo or logo, shown on top of the card on phones
        return `<article class="biz-row${top ? " biz-row--featured" : ""}${x.im ? " biz-row--media" : ""}">
        <div class="ph${x.lg ? " ph--logo" : x.im ? " ph--photo" : ""}">${x.im ? `<img src="${BASE}${esc(x.im)}" alt="" width="1200" height="800" loading="lazy" decoding="async">` : ICON(x.i || "store")}</div>
        <div class="biz-row__main">
          <span class="biz-card__cat">${esc(cat.n || "")}${x.s ? ` · <span class="muted">${esc(x.s)}</span>` : ""}</span>
          <h3 class="biz-row__title"><a href="${BASE}/business/${x.k}/">${nameTick}</a></h3>
          <p class="card__meta"><span>${ICON("pin")}${esc(x.ad ? x.ad + ", " : "")}${esc(x.pc)}</span><span>${esc(area)}</span></p>
          ${x.tg ? `<p class="card__text">${esc(x.tg)}</p>` : ""}
        </div>
        <div class="biz-row__side"><div class="cluster">${top ? `<span class="badge badge--featured">${ICON("star")}Featured</span>` : ""}</div>${x.p ? `<a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:${esc(x.p)}">${ICON("phone")}Call</a>` : ""}</div>
      </article>`;
      }
      return `<article class="biz-row biz-row--mono${tier === "unclaimed" ? " biz-row--unclaimed" : ""}">
        <span class="mono mono--soft" aria-hidden="true">${esc(initials)}</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">${esc(cat.n || "")}${x.s ? ` · <span class="muted">${esc(x.s)}</span>` : ""}</span>
          <h3 class="biz-row__title"><a href="${BASE}/business/${x.k}/">${nameTick}</a></h3>
          <p class="card__meta"><span>${ICON("pin")}${esc(x.ad ? x.ad + ", " : "")}${esc(x.pc)}</span><span>${esc(area)}</span></p>
        </div>
        <div class="biz-row__side">${x.p ? `<a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:${esc(x.p)}">${ICON("phone")}Call</a>` : ""}</div>
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
    // Order: best match first (when searching), then Featured, then the list's own order (date for events,
    // weekday for classes). With no search words, Featured events, classes and offers come first.
    const rank = (list, terms) => list.map((x, i) => {
      let score = 0;
      for (const t of terms) {
        if (!has(x._h, t)) return null;
        score += x._n.startsWith(t) ? 6 : x._n.includes(t) ? 4 : 1;
      }
      return [score, x.f ? 1 : 0, i, x];
    }).filter(Boolean).sort((a, b) => b[0] - a[0] || b[1] - a[1] || a[2] - b[2]).map((a) => a[3]);
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
          <div class="cluster mb-2">${x.f ? FEAT : ""}<span class="biz-card__cat">${esc(x.c)}</span></div>
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
          <div class="cluster mb-2">${x.f ? FEAT : ""}<span class="badge badge--outline">${ICON("repeat")}Weekly class</span><span class="biz-card__cat">${esc(x.c)}</span></div>
          <h3 class="event-row__title"><a href="${BASE}${x.h}">${esc(x.t)}</a></h3>
          <p class="card__meta"><span>${ICON("repeat")}Every ${DAYS[x.wd]}, ${esc(x.tm)}</span><span>${ICON("pin")}${esc(x.v)}, ${esc(x.an)}</span>${x.ag ? `<span>${ICON("people")}${esc(x.ag)}</span>` : ""}</p>
        </div>
        <div class="event-row__side"><strong>${esc(x.p || "")}</strong></div>
      </article>`;
    const offerCard = (x) => {
      const soon = /^Ends (in|today|tomorrow)/.test(x.e || "");
      const tags = (x.f ? FEAT : "") + (x.sub ? `<span class="card__tick card__tick--sub" role="img" aria-label="Subscribers only" data-tip="Subscribers only">${ICON("lock")}</span>` : "") +
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
      // Filler words ("plumber near me", "cafe in pinner") are skipped, like the events, classes and offers search;
      // a search made only of them ("harrow") still uses them
      const typed = p.q.toLowerCase().split(/\s+/).filter(Boolean);
      const kept = typed.filter((t) => !STOP.has(t));
      const terms = kept.length ? kept : typed;
      let items = INDEX.items.filter((x) =>
        (!p.cat || x.c === p.cat) && (!p.area || x.a === p.area) && (!p.web || x.w) && (!p.phone || x.p));
      if (terms.length) {
        items = items.map((x) => {
          const hay = (x.n + " " + (x.s || "") + " " + (INDEX.cats[x.c] || {}).n + " " + x.pc + " " + (x.ad || "") + " " + (INDEX.areas[x.a] || "")).toLowerCase();   // area name: "cafe in pinner"
          const name = x.n.toLowerCase();
          let score = 0;
          for (const t of terms) {
            if (!hasWord(hay, t)) return null;
            score += name.startsWith(t) ? 6 : name.includes(t) ? 4 : 1;
          }
          return [score, x];
        }).filter(Boolean);
      } else {
        items = items.map((x) => [0, x]);
      }
      // Order (18 Nov 2026): Verified by the business, then Checked by Harrow Online, then imported from public data;
      // the plan tier (brief 01, 6.2: paid featured first) orders listings within each group.
      // Best match with search words: the closest name match comes first and the status order breaks ties,
      // so a pizzeria called "... Pizza" is not pushed below every Verified business that mentions pizza.
      const statusOf = (x) => x.ck ? 1 : (x.t && x.t !== "unclaimed") ? 0 : 2;
      const rankOf = (x) => statusOf(x) * 10 + tierRank[x.t || "unclaimed"];
      const byMatch = p.sort !== "name" && terms.length;
      items = items.map((a, i) => [a[0], a[1], i]).sort((a, b) =>
        (byMatch ? b[0] - a[0] : 0) || (rankOf(a[1]) - rankOf(b[1])) || (p.sort === "name" ? a[1].n.localeCompare(b[1].n) : a[2] - b[2])
      ).map((a) => a[1]);

      const type = ALL ? renderGroups(p, items) : "businesses";
      // Business filters (category, website, phone, sort) only show where businesses are listed
      $$(".dline:not(.dline--bare), .dtoolbar .fsel--sort, [data-biz-filters], .dtoolbar__key").forEach((el) => { el.hidden = !(type === "all" || type === "businesses"); });
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
