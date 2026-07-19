class f {
  constructor(t, { interval: e = 5e3, onUpdate: s = null, fetchImpl: n = null } = {}) {
    this.url = t, this.interval = e, this.onUpdate = s, this.fetch = n || (typeof fetch < "u" ? fetch.bind(globalThis) : null), this.timer = null;
  }
  async poll() {
    if (!(!this.fetch || !this.url))
      try {
        const t = await this.fetch(this.url, { headers: { Accept: "application/json" } });
        if (t && t.ok) {
          const e = await t.json();
          this.onUpdate && this.onUpdate(e);
        }
      } catch {
      }
  }
  start() {
    return this.timer ? this : (this.timer = setInterval(() => this.poll(), this.interval), this.poll(), this);
  }
  stop() {
    return this.timer && (clearInterval(this.timer), this.timer = null), this;
  }
}
function p(i, t) {
  const e = [];
  for (let s = 1; s <= t; s++)
    e.push({ index: s, state: s <= i ? "filled" : "empty" });
  return e;
}
function u(i, t) {
  if (!i || !t) return i;
  const e = t.program ? t.program.stamps_required : 0, s = i.querySelector("[data-loyalty-count]");
  s && (s.textContent = String(t.stamps_count ?? 0));
  const n = i.querySelector("[data-loyalty-required]");
  n && (n.textContent = String(e));
  const a = t.stamps && t.stamps.length ? t.stamps : p(t.stamps_count ?? 0, e);
  return i.querySelectorAll("[data-loyalty-stamp]").forEach((r) => {
    const l = Number(r.getAttribute("data-index")), c = a.find((y) => y.index === l);
    c && r.setAttribute("data-state", c.state);
  }), i.toggleAttribute("data-loyalty-complete", !!t.is_complete), i;
}
class h {
  constructor(t, { channelFactory: e = null } = {}) {
    this.root = t, this.channelFactory = e, this.config = this.readConfig(), this.channel = null;
  }
  readConfig() {
    const t = this.root.querySelector("[data-loyalty-config]");
    if (!t) return {};
    try {
      return JSON.parse(t.textContent);
    } catch {
      return {};
    }
  }
  async init() {
    u(this.root, this.config), this.revealWalletActions();
    const t = this.root.getAttribute("data-loyalty-state-url");
    return t && (this.channel = this.channelFactory ? this.channelFactory(t, (e) => this.onUpdate(e)) : new f(t, { onUpdate: (e) => this.onUpdate(e) }), this.channel.start()), this;
  }
  onUpdate(t) {
    const e = this.config.stamps_count ?? 0;
    this.config = t, u(this.root, t), (t.stamps_count ?? 0) > e && this.pulse();
  }
  pulse() {
    this.root.setAttribute("data-loyalty-pulse", ""), setTimeout(() => this.root.removeAttribute("data-loyalty-pulse"), 700);
  }
  revealWalletActions() {
    this.root.querySelectorAll("[data-loyalty-wallet]").forEach((e) => {
      if (e.getAttribute("href")) {
        e.hidden = !1;
        const s = e.closest("[data-loyalty-wallet-actions]");
        s && (s.hidden = !1);
      }
    });
  }
  destroy() {
    var t;
    (t = this.channel) == null || t.stop();
  }
}
class d {
  constructor(t, { fetchImpl: e = null } = {}) {
    this.root = t, this.fetch = e || (typeof fetch < "u" ? fetch.bind(globalThis) : null), this.stampUrl = t.getAttribute("data-loyalty-stamp-url"), this.redeemUrl = t.getAttribute("data-loyalty-redeem-url"), this.input = t.querySelector("[data-loyalty-card-input]"), this.result = t.querySelector("[data-loyalty-terminal-result]");
  }
  init() {
    var e;
    const t = this.root.querySelector("[data-loyalty-terminal-form]");
    return t == null || t.addEventListener("submit", (s) => {
      s.preventDefault(), this.submit(this.stampUrl);
    }), (e = this.root.querySelector("[data-loyalty-redeem-btn]")) == null || e.addEventListener("click", () => {
      this.submit(this.redeemUrl);
    }), this;
  }
  async submit(t) {
    var a, r;
    const e = (r = (a = this.input) == null ? void 0 : a.value) == null ? void 0 : r.trim();
    if (!e || !t || !this.fetch) return null;
    let s = !1, n = {};
    try {
      const l = await this.fetch(t, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
          ...m()
        },
        body: JSON.stringify({ card_token: e })
      });
      s = l.ok, n = await l.json().catch(() => ({}));
    } catch {
      n = { message: "Network error" };
    }
    return this.showResult(s, n), { ok: s, data: n };
  }
  showResult(t, e) {
    var s;
    this.result && (this.result.hidden = !1, this.result.setAttribute("data-status", t ? "ok" : "error"), this.result.textContent = t ? `${e.stamps_count ?? "?"} / ${((s = e.program) == null ? void 0 : s.stamps_required) ?? "?"}` : e.message || "Rejected");
  }
}
function m() {
  if (typeof document > "u") return {};
  const i = document.querySelector('meta[name="csrf-token"]');
  return i ? { "X-CSRF-TOKEN": i.getAttribute("content") } : {};
}
function o(i = document) {
  const t = [];
  i.querySelectorAll("[data-loyalty-card]").forEach((s) => {
    const n = new h(s);
    n.init(), t.push(n);
  });
  const e = [];
  return i.querySelectorAll("[data-loyalty-terminal]").forEach((s) => {
    const n = new d(s);
    n.init(), e.push(n);
  }), { cards: t, terminals: e };
}
typeof window < "u" && (window.Loyalty = { init: o, LoyaltyCard: h, LoyaltyTerminal: d }, typeof document < "u" && (document.readyState !== "loading" ? o() : document.addEventListener("DOMContentLoaded", () => o())));
export {
  h as LoyaltyCard,
  d as LoyaltyTerminal,
  f as PollChannel,
  u as applyState,
  p as computeStamps,
  o as init
};
