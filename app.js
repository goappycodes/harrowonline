(() => {
  "use strict";

  const PAGE = 24;
  const GROUPS = {
    shopping: "Shopping",
    food_and_drink: "Food & Drink",
    services_and_business: "Services",
    lifestyle_services: "Beauty & Wellbeing",
    health_care: "Health",
    travel_and_transportation: "Motoring & Travel",
    sports_and_recreation: "Sport & Fitness",
    arts_and_entertainment: "Entertainment",
    lodging: "Places to Stay",
  };
  const AREAS = {
    HA0: "Wembley",
    HA1: "Harrow",
    HA2: "South & North Harrow",
    HA3: "Kenton & Wealdstone",
    HA5: "Pinner & Hatch End",
    HA7: "Stanmore",
    HA8: "Edgware",
  };
  const SOURCES = {
    BrightQuery: "BrightQuery",
    meta: "Meta",
    Microsoft: "Microsoft",
    Uberall: "Uberall",
    DAC: "DAC",
    PinMeTo: "PinMeTo",
    AllThePlaces: "AllThePlaces",
    Foursquare: "Foursquare",
  };
  const PALETTE = ["#dd3333", "#111111", "#3d3d3d", "#b82424", "#6b6b6b", "#8a1c1c"];
  const DEFAULTS = { q: "", g: "", s: "", area: "", web: false, email: false, conf: 80, sort: "name", view: "list" };

  let DATA = [];
  let BY_ID = new Map();
  let state = { ...DEFAULTS };
  let shown = PAGE;
  let map = null;

  const app = document.getElementById("app");
  const searchInput = document.getElementById("q");

  // ---------- helpers ----------
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const label = (slug) => {
    if (!slug) return "";
    const s = slug.replace(/_/g, " ");
    return s.charAt(0).toUpperCase() + s.slice(1);
  };
  // Some sources supply addresses in capitals; show them in title case.
  const tidyAddress = (a) =>
    a && a === a.toUpperCase() && /[A-Z]{3}/.test(a)
      ? a.toLowerCase().replace(/\b([a-z])/g, (m) => m.toUpperCase())
      : a;
  const groupLabel = (g) => GROUPS[g] || label(g);
  const areaLabel = (d) => (AREAS[d] ? `${d} · ${AREAS[d]}` : d);
  const initials = (n) => {
    const w = n.replace(/[^A-Za-z0-9 &]/g, " ").split(/\s+/).filter((x) => x && x !== "&");
    return ((w[0] || "?")[0] + (w[1] ? w[1][0] : "")).toUpperCase();
  };
  const colourFor = (id) => {
    let h = 0;
    for (const ch of id) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
    return PALETTE[h % PALETTE.length];
  };
  const fmtPhone = (raw) => {
    let p = raw.replace(/[^\d+]/g, "");
    if (p.startsWith("+44")) p = "0" + p.slice(3);
    else if (p.startsWith("44") && p.length === 12) p = "0" + p.slice(2);
    if (/^02\d{9}$/.test(p)) return `${p.slice(0, 3)} ${p.slice(3, 7)} ${p.slice(7)}`;
    if (/^0\d{10}$/.test(p)) return `${p.slice(0, 5)} ${p.slice(5)}`;
    return raw;
  };
  const telHref = (raw) => {
    let p = raw.replace(/[^\d+]/g, "");
    if (p.startsWith("0")) p = "+44" + p.slice(1);
    return "tel:" + p;
  };
  const href = (u) => (/^https?:\/\//i.test(u) ? u : "https://" + u);
  const host = (u) => {
    try { return new URL(href(u)).hostname.replace(/^www\./, ""); } catch { return u; }
  };
  const socialName = (u) => {
    const h = host(u);
    if (h.includes("facebook")) return "Facebook";
    if (h.includes("instagram")) return "Instagram";
    if (h.includes("twitter") || h === "x.com") return "X / Twitter";
    if (h.includes("linkedin")) return "LinkedIn";
    if (h.includes("tiktok")) return "TikTok";
    if (h.includes("youtube")) return "YouTube";
    return h;
  };
  const distKm = (a, b) => {
    const R = 6371, rad = Math.PI / 180;
    const dLat = (b.lat - a.lat) * rad, dLng = (b.lng - a.lng) * rad;
    const x = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(x));
  };
  const count = (arr, key) => arr.reduce((m, x) => m.set(x[key], (m.get(x[key]) || 0) + 1), new Map());

  // ---------- state <-> hash ----------
  function stateToQuery(s) {
    const p = new URLSearchParams();
    for (const [k, v] of Object.entries(s)) {
      if (v === DEFAULTS[k]) continue;
      p.set(k, typeof v === "boolean" ? (v ? "1" : "0") : String(v));
    }
    const str = p.toString();
    return str ? "?" + str : "";
  }
  function queryToState(q) {
    const p = new URLSearchParams(q);
    const s = { ...DEFAULTS };
    for (const k of Object.keys(DEFAULTS)) {
      if (!p.has(k)) continue;
      const v = p.get(k);
      if (typeof DEFAULTS[k] === "boolean") s[k] = v === "1";
      else if (typeof DEFAULTS[k] === "number") s[k] = Number(v) || DEFAULTS[k];
      else s[k] = v;
    }
    return s;
  }
  function setState(patch, { resetPage = true } = {}) {
    state = { ...state, ...patch };
    if (resetPage) shown = PAGE;
    const target = "#/" + stateToQuery(state);
    if (location.hash !== target) history.replaceState(null, "", target);
    renderList();
  }

  // ---------- filtering ----------
  function matches(b, s, skip) {
    if (skip !== "g" && s.g && b.g !== s.g) return false;
    if (skip !== "s" && s.s && b.s !== s.s) return false;
    if (s.area && b.d !== s.area) return false;
    if (s.web && !b.w.length) return false;
    if (s.email && !b.e.length) return false;
    if (b.cf * 100 < s.conf) return false;
    if (s.q) {
      const terms = s.q.toLowerCase().split(/\s+/).filter(Boolean);
      if (!terms.every((t) => b._text.includes(t))) return false;
    }
    return true;
  }
  function filtered() {
    const out = DATA.filter((b) => matches(b, state));
    if (state.sort === "name") out.sort((a, b) => a.n.localeCompare(b.n, "en", { sensitivity: "base" }));
    else if (state.sort === "conf") out.sort((a, b) => b.cf - a.cf || a.n.localeCompare(b.n));
    else if (state.sort === "complete") out.sort((a, b) => b._complete - a._complete || a.n.localeCompare(b.n));
    return out;
  }

  // ---------- list view ----------
  function cardHTML(b) {
    const chips = [];
    if (b.w.length) chips.push('<span class="chip ok">Website</span>');
    if (b.e.length) chips.push('<span class="chip ok">Email</span>');
    if (b.so.length) chips.push('<span class="chip">Social</span>');
    chips.push(`<span class="chip">${esc(b.d)}</span>`);
    return `<a class="card" href="#/b/${encodeURIComponent(b.id)}">
      <div class="card-top">
        <span class="avatar" style="background:${colourFor(b.id)}" aria-hidden="true">${esc(initials(b.n))}</span>
        <div>
          <h3>${esc(b.n)}</h3>
          <div class="cat">${esc(label(b.c))}</div>
        </div>
      </div>
      <p class="addr">${esc([b.a, b.pc].filter(Boolean).join(", "))}</p>
      <div class="meta">${chips.join("")}</div>
    </a>`;
  }

  function renderList() {
    const list = filtered();
    const pool = DATA.filter((b) => matches(b, state, "g"));
    const gCounts = count(pool, "g");
    const subPool = DATA.filter((b) => matches(b, state, "s"));
    const sCounts = count(subPool, "s");
    const aCounts = count(DATA.filter((b) => matches(b, { ...state, area: "" })), "d");

    const groups = Object.keys(GROUPS).filter((g) => gCounts.get(g));
    const subs = [...sCounts.entries()].filter(([k]) => k).sort((a, b) => b[1] - a[1]);
    const areas = Object.keys(AREAS).filter((d) => aCounts.get(d) || state.area === d);

    const withWeb = DATA.filter((b) => b.w.length).length;
    const withEmail = DATA.filter((b) => b.e.length).length;

    app.innerHTML = `
      <section class="hero">
        <div>
          <h1>Find local businesses in Harrow</h1>
          <p>Shops, restaurants, trades, health and services across the borough.</p>
        </div>
        <div class="stats">
          <div class="stat"><b>${DATA.length.toLocaleString()}</b><span>listings</span></div>
          <div class="stat"><b>${withWeb.toLocaleString()}</b><span>with website</span></div>
          <div class="stat"><b>${withEmail.toLocaleString()}</b><span>with email</span></div>
        </div>
      </section>

      <nav class="cats" aria-label="Categories">
        <button class="cat-pill" data-g="" aria-pressed="${!state.g}">All <small>${pool.length.toLocaleString()}</small></button>
        ${groups.map((g) => `<button class="cat-pill" data-g="${g}" aria-pressed="${state.g === g}">${esc(groupLabel(g))} <small>${gCounts.get(g)}</small></button>`).join("")}
      </nav>

      <div class="layout">
        <aside class="filters" id="filters" aria-label="Filters">
          <h2>Refine</h2>
          <div class="field">
            <label for="f-sub">Type of business</label>
            <select id="f-sub">
              <option value="">All types</option>
              ${subs.map(([k, n]) => `<option value="${esc(k)}" ${state.s === k ? "selected" : ""}>${esc(label(k))} (${n})</option>`).join("")}
            </select>
          </div>
          <div class="field">
            <label for="f-area">Area</label>
            <select id="f-area">
              <option value="">All of Harrow</option>
              ${areas.map((d) => `<option value="${d}" ${state.area === d ? "selected" : ""}>${esc(areaLabel(d))} (${aCounts.get(d) || 0})</option>`).join("")}
            </select>
          </div>
          <div class="field">
            <span class="label">Contact details</span>
            <label class="check"><input type="checkbox" id="f-web" ${state.web ? "checked" : ""}> Has a website</label>
            <label class="check"><input type="checkbox" id="f-email" ${state.email ? "checked" : ""}> Has an email</label>
          </div>
          <div class="field">
            <label for="f-conf">Minimum data confidence</label>
            <div class="range-row">
              <input type="range" id="f-conf" min="80" max="100" step="1" value="${state.conf}">
              <output id="f-conf-out">${state.conf}%</output>
            </div>
          </div>
          <button class="reset" id="f-reset" type="button">Clear filters</button>
        </aside>

        <section>
          <div class="results-bar">
            <div class="count">${list.length.toLocaleString()} businesses <span>${state.q ? `matching “${esc(state.q)}”` : ""}</span></div>
            <div class="bar-controls">
              <button class="filters-toggle" id="f-toggle" type="button" aria-controls="filters" aria-expanded="false">Filters</button>
              <label class="sr-only" for="f-sort">Sort</label>
              <select id="f-sort" ${state.view === "map" ? "hidden" : ""}>
                <option value="name" ${state.sort === "name" ? "selected" : ""}>A–Z</option>
                <option value="complete" ${state.sort === "complete" ? "selected" : ""}>Most complete</option>
                <option value="conf" ${state.sort === "conf" ? "selected" : ""}>Highest confidence</option>
              </select>
              <div class="view-toggle" role="group" aria-label="View">
                <button type="button" data-view="list" aria-pressed="${state.view === "list"}">List</button>
                <button type="button" data-view="map" aria-pressed="${state.view === "map"}">Map</button>
              </div>
            </div>
          </div>
          <div id="results"></div>
        </section>
      </div>`;

    const results = document.getElementById("results");
    if (!list.length) {
      results.innerHTML = `<div class="empty">No businesses match these filters. <button class="reset" style="width:auto;margin-left:8px" id="empty-reset">Clear filters</button></div>`;
      document.getElementById("empty-reset").onclick = resetFilters;
    } else if (state.view === "map") {
      results.innerHTML = '<div id="map" role="region" aria-label="Map of results"></div>';
      drawMap(list);
    } else {
      results.innerHTML = `<div class="grid">${list.slice(0, shown).map(cardHTML).join("")}</div>
        ${list.length > shown ? `<button class="more" id="more">Show more (${(list.length - shown).toLocaleString()} left)</button>` : ""}`;
      const more = document.getElementById("more");
      if (more) more.onclick = () => { shown += PAGE * 2; renderList(); };
    }

    // wire controls
    app.querySelectorAll(".cat-pill").forEach((btn) => (btn.onclick = () => setState({ g: btn.dataset.g, s: "" })));
    document.getElementById("f-sub").onchange = (e) => setState({ s: e.target.value });
    document.getElementById("f-area").onchange = (e) => setState({ area: e.target.value });
    document.getElementById("f-web").onchange = (e) => setState({ web: e.target.checked });
    document.getElementById("f-email").onchange = (e) => setState({ email: e.target.checked });
    const conf = document.getElementById("f-conf");
    conf.oninput = () => (document.getElementById("f-conf-out").textContent = conf.value + "%");
    conf.onchange = () => setState({ conf: Number(conf.value) });
    document.getElementById("f-sort").onchange = (e) => setState({ sort: e.target.value });
    document.getElementById("f-reset").onclick = resetFilters;
    app.querySelectorAll(".view-toggle button").forEach((btn) => (btn.onclick = () => setState({ view: btn.dataset.view })));
    const toggle = document.getElementById("f-toggle");
    toggle.onclick = () => {
      const f = document.getElementById("filters");
      const open = f.classList.toggle("open");
      toggle.setAttribute("aria-expanded", String(open));
    };
    searchInput.value = state.q;
  }

  function resetFilters() {
    searchInput.value = "";
    setState({ ...DEFAULTS, view: state.view });
  }

  function tiles(m) {
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(m);
  }

  function drawMap(list) {
    if (map) { map.remove(); map = null; }
    map = L.map("map", { scrollWheelZoom: true });
    tiles(map);
    const cluster = L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 50 });
    for (const b of list) {
      const mk = L.marker([b.lat, b.lng], { title: b.n });
      mk.bindPopup(`<strong>${esc(b.n)}</strong><br>${esc(label(b.c))}<br><a href="#/b/${encodeURIComponent(b.id)}">View details →</a>`);
      cluster.addLayer(mk);
    }
    map.addLayer(cluster);
    map.fitBounds(cluster.getBounds(), { padding: [20, 20], maxZoom: 16 });
  }

  // ---------- detail view ----------
  function renderDetail(id) {
    if (map) { map.remove(); map = null; }
    const b = BY_ID.get(id);
    const backHref = "#/" + stateToQuery(state);
    if (!b) {
      app.innerHTML = `<a class="back" href="${backHref}">← All businesses</a><div class="empty">This listing could not be found.</div>`;
      return;
    }
    document.title = `${b.n} — Harrow Business Directory`;

    const rows = [];
    if (b.a || b.pc) rows.push(["Address", esc([b.a, b.pc].filter(Boolean).join(", "))]);
    b.ph.forEach((p) => rows.push(["Phone", `<a href="${telHref(p)}">${esc(fmtPhone(p))}</a>`]));
    b.w.forEach((w) => rows.push(["Website", `<a href="${esc(href(w))}" target="_blank" rel="noopener nofollow">${esc(host(w))}</a>`]));
    b.e.forEach((e) => rows.push(["Email", `<a href="mailto:${esc(e)}">${esc(e)}</a>`]));
    b.so.forEach((s) => rows.push(["Social", `<a href="${esc(href(s))}" target="_blank" rel="noopener nofollow">${esc(socialName(s))}</a>`]));

    const nearby = DATA.filter((x) => x.id !== b.id && x.s === b.s && b.s)
      .map((x) => ({ x, d: distKm(b, x) }))
      .sort((p, q) => p.d - q.d)
      .slice(0, 6);

    const pct = Math.round(b.cf * 100);
    const filled = [b.ph.length, b.w.length, b.e.length, b.so.length, b.a].filter(Boolean).length;

    app.innerHTML = `
      <a class="back" href="${backHref}">← All businesses</a>
      <div class="detail">
        <article class="panel">
          <div class="detail-head">
            <span class="avatar" style="background:${colourFor(b.id)}" aria-hidden="true">${esc(initials(b.n))}</span>
            <div>
              <h1>${esc(b.n)}</h1>
              <div class="crumbs">
                <a href="#/?g=${b.g}"><span class="chip">${esc(groupLabel(b.g))}</span></a>
                ${b.s ? `<a href="#/?g=${b.g}&s=${b.s}"><span class="chip">${esc(label(b.s))}</span></a>` : ""}
                <span class="chip">${esc(label(b.c))}</span>
                <a href="#/?area=${b.d}"><span class="chip">${esc(areaLabel(b.d))}</span></a>
              </div>
            </div>
          </div>

          <div class="unclaimed">
            <p><strong>Is this your business?</strong> Claim it to update details, add photos and opening hours.</p>
            <button class="btn" id="claim" type="button">Claim this business</button>
          </div>

          <h2 class="section-title">Contact</h2>
          <ul class="contact">${rows.map(([k, v]) => `<li><span>${k}</span><span>${v}</span></li>`).join("")}</ul>

          <h2 class="section-title">Location</h2>
          <div id="detail-map" role="region" aria-label="Map"></div>
          <p class="map-link"><a href="https://www.google.com/maps/search/?api=1&query=${b.lat},${b.lng}" target="_blank" rel="noopener">Open in Google Maps</a></p>
        </article>

        <aside class="side">
          <div class="panel">
            <h2 class="section-title">Listing quality</h2>
            <div class="quality">
              <div>Data confidence <strong>${pct}%</strong></div>
              <div class="meter"><i style="width:${pct}%"></i></div>
              <div>Profile completeness <strong>${filled}/5</strong> (address, phone, website, email, social)</div>
              <div class="fine">Sources: ${b.src.map((s) => esc(SOURCES[s] || s)).join(", ")} via Overture Maps.</div>
              <div class="fine">Not yet verified by the owner. Spotted a mistake? <a href="#" id="report">Request a correction</a>.</div>
            </div>
          </div>
          ${nearby.length ? `<div class="panel">
            <h2 class="section-title">Similar nearby · ${esc(label(b.s))}</h2>
            <div class="nearby">${nearby.map(({ x, d }) => `<a href="#/b/${encodeURIComponent(x.id)}"><span>${esc(x.n)}</span><small>${d < 1 ? Math.round(d * 1000) + " m" : d.toFixed(1) + " km"}</small></a>`).join("")}</div>
          </div>` : ""}
        </aside>
      </div>
      <dialog id="demo-dialog">
        <h2 id="dlg-title">Coming in the live directory</h2>
        <p id="dlg-body"></p>
        <form method="dialog"><button class="btn">OK</button></form>
      </dialog>`;

    map = L.map("detail-map", { scrollWheelZoom: false }).setView([b.lat, b.lng], 16);
    tiles(map);
    L.marker([b.lat, b.lng], { title: b.n }).addTo(map);

    const dlg = document.getElementById("demo-dialog");
    const show = (text) => { document.getElementById("dlg-body").textContent = text; dlg.showModal(); };
    document.getElementById("claim").onclick = () =>
      show("Owners will verify by email or phone, then manage their profile and choose a free or paid package. This preview only shows the listings.");
    document.getElementById("report").onclick = (e) => {
      e.preventDefault();
      show("Owners and visitors will be able to request corrections or removal from each listing page.");
    };
    window.scrollTo(0, 0);
  }

  // ---------- routing ----------
  function route() {
    const h = location.hash || "#/";
    const m = h.match(/^#\/b\/([^?]+)/);
    if (m) return renderDetail(decodeURIComponent(m[1]));
    document.title = "Harrow Business Directory — Preview";
    const q = h.includes("?") ? h.slice(h.indexOf("?") + 1) : "";
    state = queryToState(q);
    shown = PAGE;
    renderList();
  }

  document.getElementById("search-form").addEventListener("submit", (e) => {
    e.preventDefault();
    const q = searchInput.value.trim();
    if (location.hash.startsWith("#/b/")) {
      state = { ...DEFAULTS, q, view: state.view };
      location.hash = "#/" + stateToQuery(state);
    } else {
      setState({ q });
    }
  });
  window.addEventListener("hashchange", route);

  fetch("data/listings.json")
    .then((r) => {
      if (!r.ok) throw new Error(r.status);
      return r.json();
    })
    .then((rows) => {
      DATA = rows.map((b) => ({
        ...b,
        a: tidyAddress(b.a),
        _text: [b.n, b.c, b.s, b.g, GROUPS[b.g], b.a, b.pc, b.d, AREAS[b.d]].join(" ").toLowerCase().replace(/_/g, " "),
        _complete: [b.ph.length, b.w.length, b.e.length, b.so.length, b.a].filter(Boolean).length,
      }));
      BY_ID = new Map(DATA.map((b) => [b.id, b]));
      route();
    })
    .catch(() => {
      app.innerHTML = '<div class="empty">The listings could not be loaded. Please refresh the page.</div>';
    });
})();
