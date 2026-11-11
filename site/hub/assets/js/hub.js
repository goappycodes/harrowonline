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

  /* 6. Subscriber-only offers: unlock (prototype reveals immediately) */
  $$("[data-unlock]").forEach((form) => form.addEventListener("submit", (e) => {
    e.preventDefault();
    const offer = form.closest(".offer");
    if (offer) offer.classList.add("is-unlocked");
  }));

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
    try { await navigator.clipboard.writeText(b.dataset.copy || location.href); b.textContent = "Link copied"; } catch (_) { /* ignore */ }
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
      const vals = { n: x.n, ad: x.ad || "", pc: x.pc, area: d.areas[x.a], cat: (d.cats[x.c] || {}).n, link: BASE + "/business/" + x.k + ".html" };
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
      const badge = tier === "unclaimed" ? `<span class="badge badge--unclaimed">Unclaimed</span>` :
        `<span class="badge badge--verified">${ICON("verified")}Verified</span>`;
      if (tier !== "unclaimed") {
        // Claimed listing: photo, tagline, verified; Premium and Local Partner are featured (brief 01, 6.2)
        const top = tier === "premium" || tier === "partner";
        return `<article class="biz-row${top ? " biz-row--featured" : ""}">
        <div class="ph">${ICON(x.i || "store")}</div>
        <div class="biz-row__main">
          <span class="biz-card__cat">${esc(cat.n || "")}${x.s ? ` · <span class="muted">${esc(x.s)}</span>` : ""}</span>
          <h3 class="biz-row__title"><a href="${BASE}/business/${x.k}.html">${esc(x.n)}</a></h3>
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
          <h3 class="biz-row__title"><a href="${BASE}/business/${x.k}.html">${esc(x.n)}</a></h3>
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
        `<div class="empty">${ICON("search")}<h3>No businesses found</h3><p>Try a different word, or clear a filter. Can&rsquo;t find a business you know? Add it to the Hub.</p><a class="ho-btn ho-btn--primary" href="${BASE}/add-business.html">${ICON("plus")}Add a business</a></div>`;

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
