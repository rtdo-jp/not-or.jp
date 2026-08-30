/*
  File: nor.js
  Author: Ryousuke Tamura
  Created: 2026-1-2
  Update: 2026-2-27
  Note: JavaScript for nør.
*/

// ========================================
// Shared
// ========================================
const NorShared = (() => {
  const qs = (sel, el = document) => el.querySelector(sel);
  const qsa = (sel, el = document) => Array.from(el.querySelectorAll(sel));
  const mq = (query) => {
    if (!window.matchMedia) return null;
    try {
      return window.matchMedia(query);
    } catch {
      return null;
    }
  };

  const getPrefersReducedMotion = () =>
    Boolean(mq("(prefers-reduced-motion: reduce)")?.matches);

  const onDomReady = (fn) => {
    if (typeof fn !== "function") return;
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn, { once: true });
      return;
    }
    fn();
  };

  const onPageShow = (fn, { persistedOnly = false } = {}) => {
    if (typeof fn !== "function") return;
    window.addEventListener("pageshow", (ev) => {
      if (persistedOnly && !(ev && ev.persisted)) return;
      fn(ev);
    });
  };

  const onPageHide = (fn, options) => {
    if (typeof fn !== "function") return;
    window.addEventListener("pagehide", fn, options);
  };

  const createStorage = (key) => {
    const stores = [
      () => window.localStorage,
      () => window.sessionStorage,
    ];

    const withStore = (getStore, fn, fallback) => {
      try {
        const store = getStore();
        if (!store) return fallback;
        return fn(store);
      } catch {
        return fallback;
      }
    };

    const safeGet = () => {
      for (const getStore of stores) {
        const value = withStore(getStore, (store) => store.getItem(key), null);
        if (value !== null) return value;
      }
      return null;
    };

    const safeSet = (value) => {
      let ok = false;

      for (const getStore of stores) {
        const didSet = withStore(getStore, (store) => {
          store.setItem(key, value);
          return true;
        }, false);
        if (didSet) ok = true;
      }

      return ok;
    };

    return { get: safeGet, set: safeSet };
  };

  const setButtonVisible = (btn, isVisible) => {
    if (!btn) return;

    if (isVisible) {
      btn.hidden = false;
      btn.removeAttribute("aria-hidden");
      if (btn.style && btn.style.display === "none") btn.style.display = "";
      return;
    }

    btn.hidden = true;
    btn.setAttribute("aria-hidden", "true");
    if (btn.style) btn.style.display = "none";
  };

  const focusElement = (el, { preventScroll = true } = {}) => {
    if (!el) return;
    try {
      el.focus({ preventScroll });
    } catch {
      try {
        el.focus();
      } catch {
      }
    }
  };

  const bindClearButtons = (rootEl = document, options = {}) => {
    const ATTR_CLEAR_BOUND = "data-nor-clear-bound";
    const ATTR_CLEAR_RESET_BOUND = "data-nor-clear-reset-bound";
    const selectorClearButton = String(options.selectorClearButton || ".btn-clear");
    const selectorControl = String(options.selectorControl || ".control");
    const selectorTextInputs = String(options.selectorTextInputs || "input, textarea");
    const excludeWithinSelector = options.excludeWithinSelector ? String(options.excludeWithinSelector) : "";
    const syncOnReset = Boolean(options.syncOnReset);
    const onBeforeClear =
      typeof options.onBeforeClear === "function" ? options.onBeforeClear : null;

    const updateOne = (btn) => {
      if (!btn) return null;

      const control = btn.closest(selectorControl);
      if (!control) {
        setButtonVisible(btn, false);
        return null;
      }

      const target = qs(selectorTextInputs, control);
      if (!target || target.disabled) {
        setButtonVisible(btn, false);
        return null;
      }

      setButtonVisible(btn, String(target.value || "").length > 0);
      return target;
    };

    const buttons = qsa(selectorClearButton, rootEl);
    if (buttons.length === 0) return;

    for (const btn of buttons) {
      if (!btn) continue;

      if (excludeWithinSelector && btn.closest(excludeWithinSelector)) continue;

      const target = updateOne(btn);
      if (!target) continue;

      if (btn.getAttribute(ATTR_CLEAR_BOUND) === "1") continue;

      const sync = () => updateOne(btn);

      target.addEventListener("input", sync);
      target.addEventListener("change", sync);

      btn.addEventListener("click", () => {
        if (target.disabled) return;

        if (onBeforeClear) {
          onBeforeClear({ button: btn, target, root: rootEl });
        }

        target.value = "";
        target.dispatchEvent(new Event("input", { bubbles: true }));
        target.dispatchEvent(new Event("change", { bubbles: true }));
        focusElement(target, { preventScroll: false });

        sync();
      });

      btn.setAttribute(ATTR_CLEAR_BOUND, "1");
    }

    if (
      syncOnReset &&
      rootEl instanceof HTMLFormElement &&
      rootEl.getAttribute(ATTR_CLEAR_RESET_BOUND) !== "1"
    ) {
      rootEl.addEventListener("reset", () => {
        requestAnimationFrame(() => {
          bindClearButtons(rootEl, options);
        });
      });
      rootEl.setAttribute(ATTR_CLEAR_RESET_BOUND, "1");
    }
  };

  return {
    qs,
    qsa,
    mq,
    getPrefersReducedMotion,
    onDomReady,
    onPageShow,
    onPageHide,
    createStorage,
    setButtonVisible,
    focusElement,
    bindClearButtons,
  };
})();

// ========================================
// Cookie
// ========================================
(() => {
  const KEY = "nor.cookieConsent.v1";
  const VALUE = "accepted";
  const SELECTOR_BANNER = ".cookie-agree";
  const SELECTOR_ACCEPT_BTN = "[data-cookie-accept]";
  const SELECTOR_FALLBACK_BTN = ".btn";
  const SELECTOR_FALLBACK_BUTTON = "button";

  const storage = NorShared.createStorage(KEY);
  const banners = NorShared.qsa(SELECTOR_BANNER);

  const isAccepted = () => storage.get() === VALUE;

  const setAllVisible = (isVisible) => {
    for (const b of banners) b.hidden = !isVisible;
  };

  const getAcceptBtn = (banner) =>
    banner.querySelector(SELECTOR_ACCEPT_BTN) ||
    banner.querySelector(SELECTOR_FALLBACK_BTN) ||
    banner.querySelector(SELECTOR_FALLBACK_BUTTON);

  const bind = () => {
    for (const b of banners) {
      const acceptBtn = getAcceptBtn(b);
      if (!acceptBtn) continue;

      acceptBtn.addEventListener("click", () => {
        storage.set(VALUE);
        setAllVisible(false);
      });
    }
  };

  const init = () => {
    if (banners.length === 0) return;
    setAllVisible(!isAccepted());
    bind();
  };

  init();
})();

// ========================================
// Theme
// ========================================
(() => {
  const KEY = "nor.themeMode.v1";
  const MODES = ["auto", "light", "dark"];
  const MODE_AUTO = "auto";
  const THEME_DARK = "dark";
  const THEME_LIGHT = "light";
  const SELECTOR_MODE_BUTTON = "[data-mode-cycle]";
  const SELECTOR_MODE_ICON = ".mode-select-icon";
  const SELECTOR_MODE_LABEL = ".mode-select-label";
  const CLASS_MODE_LABEL = "mode-select-label";
  const ATTR_THEME = "data-theme";
  const ATTR_MODE = "data-mode";
  const ATTR_THEME_EFFECTIVE = "data-theme-effective";

  const storage = NorShared.createStorage(KEY);
  const root = document.documentElement;
  const buttons = NorShared.qsa(SELECTOR_MODE_BUTTON);
  const mq = NorShared.mq("(prefers-color-scheme: dark)");
  let currentMode = MODE_AUTO;
  let mqListenerAttached = false;

  const normalizeMode = (v) => {
    const s = String(v || "").trim().toLowerCase();
    return MODES.includes(s) ? s : MODE_AUTO;
  };

  const systemTheme = () => {
    if (!mq) return THEME_LIGHT;
    return mq.matches ? THEME_DARK : THEME_LIGHT;
  };

  const applyThemeAttr = (theme) => {
    if (theme === THEME_DARK) root.setAttribute(ATTR_THEME, THEME_DARK);
    else root.setAttribute(ATTR_THEME, THEME_LIGHT);

    // The inline background-color set in <head> only guards against a flash
    // before CSS loads. Once JS owns data-theme, drop it so html[data-theme]
    // stays in sync on later mode switches instead of freezing at page-load's color.
    root.style.removeProperty("background-color");
  };

  const ensureLabelSpan = (btn) => {
    const icon = btn.querySelector(SELECTOR_MODE_ICON);

    let label = btn.querySelector(SELECTOR_MODE_LABEL);
    if (label) return label;

    const textNodesAfterIcon = [];
    for (const n of Array.from(btn.childNodes)) {
      if (n === icon) continue;
      if (n.nodeType === Node.TEXT_NODE && String(n.nodeValue || "").trim()) {
        textNodesAfterIcon.push(n);
      }
    }

    const migratedText = textNodesAfterIcon
      .map((n) => String(n.nodeValue || "").trim())
      .join(" ")
      .trim();

    for (const n of textNodesAfterIcon) {
      try {
        btn.removeChild(n);
      } catch {
      }
    }

    label = document.createElement("span");
    label.className = CLASS_MODE_LABEL;

    const space = document.createTextNode(" ");

    if (icon) {
      if (icon.nextSibling) {
        btn.insertBefore(space, icon.nextSibling);
        btn.insertBefore(label, space.nextSibling);
      } else {
        btn.appendChild(space);
        btn.appendChild(label);
      }
    } else {
      btn.insertBefore(label, btn.firstChild);
    }

    if (migratedText) {
      label.textContent = migratedText;
    }

    return label;
  };

  const getUiText = (mode, effectiveTheme) => {
    if (mode === "light") {
      return { icon: "☀️", label: "Mode: Light" };
    }
    if (mode === "dark") {
      return { icon: "🌙", label: "Mode: Dark" };
    }

    const sysLabel = effectiveTheme === "dark" ? "Mode: System (Dark)" : "Mode: System (Light)";
    return { icon: "🌗", label: sysLabel };
  };

  const onSystemChange = () => {
    if (currentMode !== MODE_AUTO) return;
    applyThemeAttr(systemTheme());
    updateButtons();
  };

  const attachMqListener = () => {
    if (!mq || mqListenerAttached) return;
    mqListenerAttached = true;

    if (typeof mq.addEventListener === "function") {
      mq.addEventListener("change", onSystemChange);
    } else if (typeof mq.addListener === "function") {
      mq.addListener(onSystemChange);
    }
  };

  const detachMqListener = () => {
    if (!mq || !mqListenerAttached) return;
    mqListenerAttached = false;

    if (typeof mq.removeEventListener === "function") {
      mq.removeEventListener("change", onSystemChange);
    } else if (typeof mq.removeListener === "function") {
      mq.removeListener(onSystemChange);
    }
  };

  const applyMode = (mode) => {
    currentMode = normalizeMode(mode);

    if (currentMode === MODE_AUTO) {
      applyThemeAttr(systemTheme());
      attachMqListener();
    } else {
      detachMqListener();
      applyThemeAttr(currentMode);
    }

    storage.set(currentMode);
    updateButtons();
  };

  const updateButtons = () => {
    if (buttons.length === 0) return;
    const resolved = currentMode === MODE_AUTO ? systemTheme() : currentMode;
    const ui = getUiText(currentMode, resolved);

    for (const btn of buttons) {
      const iconEl = btn.querySelector(SELECTOR_MODE_ICON);
      if (iconEl) iconEl.textContent = ui.icon;

      const labelEl = ensureLabelSpan(btn);
      if (labelEl) {
        labelEl.textContent = ui.label;
      } else {
        btn.setAttribute("aria-label", ui.label);
      }

      btn.setAttribute(ATTR_MODE, currentMode);
      btn.setAttribute(ATTR_THEME_EFFECTIVE, resolved);
    }
  };

  const nextMode = (mode) => {
    const i = MODES.indexOf(normalizeMode(mode));
    const idx = i >= 0 ? i : 0;
    return MODES[(idx + 1) % MODES.length];
  };

  const bind = () => {
    for (const btn of buttons) {
      btn.addEventListener("click", () => {
        applyMode(nextMode(currentMode));
      });
    }

    NorShared.onPageShow(() => {
      applyMode(normalizeMode(storage.get()));
    });
  };

  const init = () => {
    if (!root) return;

    bind();

    currentMode = normalizeMode(storage.get());
    applyMode(currentMode);

    if (!root.classList.contains("is-theme-ready")) {
      requestAnimationFrame(() => {
        root.classList.add("is-theme-ready");
      });
    }
  };

  init();
})();

// ========================================
// Anchor scroll
// ========================================
(() => {
  const SELECTOR_ANCHOR = 'a[href^="#"]';
  const FOCUSABLE_SEL =
    'input, textarea, select, button, a[href], [tabindex]:not([tabindex="-1"])';

  const prefersReduced = NorShared.getPrefersReducedMotion();

  const onClick = (e) => {
    const a = e.target.closest(SELECTOR_ANCHOR);
    if (!a) return;

    const hash = a.getAttribute("href");
    if (!hash || hash === "#") return;

    const id = decodeURIComponent(hash.slice(1));
    const target = document.getElementById(id);
    if (!target) return;

    e.preventDefault();

    target.scrollIntoView({
      behavior: prefersReduced ? "auto" : "smooth",
      block: "start",
      inline: "nearest",
    });

    history.pushState(null, "", `#${encodeURIComponent(id)}`);

    const focusTarget = target.matches(FOCUSABLE_SEL)
      ? target
      : target.querySelector(FOCUSABLE_SEL) || target;

    if (!focusTarget.hasAttribute("tabindex") && focusTarget === target) {
      focusTarget.setAttribute("tabindex", "-1");
    }

    NorShared.focusElement(focusTarget, { preventScroll: true });
  };

  const bind = () => {
    document.addEventListener("click", onClick);
  };

  const init = () => {
    bind();
  };

  init();
})();

// ========================================
// Progress
// ========================================
(() => {
  const SHOW_DELAY_MS = 0;
  const prefersReduced = NorShared.getPrefersReducedMotion();

  const state = {
    inited: false,
    mounted: false,
    started: false,
    startTimer: 0,
    raf: 0,
    hideTimer: 0,
    startAt: 0,
    value: 0,
    doneRequested: false,
    el: null,
    bar: null,
    label: null,
    _syncHidden: null,
  };

  const clamp = (n, min, max) => Math.min(max, Math.max(min, n));

  const mount = () => {
    if (state.mounted) return;
    state.mounted = true;

    const el = document.createElement("div");
    el.className = "progress";
    el.hidden = true;
    el.setAttribute("aria-hidden", "true");

    const track = document.createElement("div");
    track.className = "track";

    const bar = document.createElement("div");
    bar.className = "bar";

    track.appendChild(bar);

    const label = document.createElement("div");
    label.className = "progress-label";
    label.textContent = "0%";
    label.setAttribute("aria-hidden", "true");

    el.appendChild(track);
    document.body.appendChild(el);
    document.body.appendChild(label);

    state.el = el;
    state.bar = bar;
    state.label = label;

    const syncHidden = () => {
      label.hidden = Boolean(el.hidden);
    };

    syncHidden();
    state._syncHidden = syncHidden;
  };

  const render = () => {
    if (!state.bar || !state.label) return;
    const v = clamp(state.value, 0, 100);
    state.bar.style.width = `${v}%`;
    state.label.textContent = `${Math.round(v)}%`;
  };

  const show = () => {
    mount();
    if (!state.el) return;
    state.el.hidden = false;
    if (state._syncHidden) state._syncHidden();
  };

  const hide = () => {
    if (!state.el) return;
    state.el.hidden = true;
    if (state._syncHidden) state._syncHidden();
  };

  const cancelTimers = () => {
    if (state.startTimer) {
      clearTimeout(state.startTimer);
      state.startTimer = 0;
    }
    if (state.raf) {
      cancelAnimationFrame(state.raf);
      state.raf = 0;
    }
    if (state.hideTimer) {
      clearTimeout(state.hideTimer);
      state.hideTimer = 0;
    }
  };

  const reset = () => {
    state.started = false;
    state.doneRequested = false;
    state.value = 0;
    render();
  };

  const tick = () => {
    const now = performance.now();
    const t = now - state.startAt;

    let next = state.value;

    if (!state.doneRequested) {
      if (t < 600) {
        next = (t / 600) * 70;
      } else if (t < 2200) {
        next = 70 + ((t - 600) / 1600) * 20;
      } else {
        next = 90;
      }
      next = Math.max(state.value, next);
      state.value = clamp(next, 0, 90);
      render();
      state.raf = requestAnimationFrame(tick);
      return;
    }

    const base = Math.max(state.value, 90);
    const prog = clamp(t / 180, 0, 1);
    state.value = clamp(base + prog * (100 - base), 0, 100);
    render();

    if (state.value < 100) {
      state.raf = requestAnimationFrame(tick);
      return;
    }

    state.hideTimer = window.setTimeout(() => {
      hide();
      cancelTimers();
      reset();
    }, 180);
  };

  const done = () => {
    if (!state.started) {
      cancelTimers();
      return;
    }

    state.doneRequested = true;
    if (!state.raf) state.raf = requestAnimationFrame(tick);
  };

  const start = (delayMs = SHOW_DELAY_MS) => {
    mount();

    if (state.started) return;

    cancelTimers();

    const run = () => {
      state.started = true;
      state.doneRequested = false;
      state.value = 0;
      state.startAt = performance.now();

      if (prefersReduced) {
        show();
        state.value = 100;
        render();
        state.hideTimer = window.setTimeout(() => {
          hide();
          cancelTimers();
          reset();
        }, 0);
        return;
      }

      show();
      render();
      state.raf = requestAnimationFrame(tick);

      if (document.readyState !== "loading") {
        setTimeout(done, 60);
      }
    };

    if (!delayMs || delayMs <= 0) {
      run();
      return;
    }

    state.startTimer = window.setTimeout(run, Math.max(0, Number(delayMs) || 0));
  };

  const bind = () => {
    NorShared.onDomReady(done);
    window.addEventListener("load", done, { once: true });

    NorShared.onPageShow(() => {
      cancelTimers();
      hide();
      reset();
    }, { persistedOnly: true });

    NorShared.onPageHide(() => {
      cancelTimers();
    });
  };

  const init = () => {
    if (state.inited) return;
    state.inited = true;

    bind();
    start(SHOW_DELAY_MS);
  };

  init();
})();

// ========================================
// Count
// ========================================
(() => {
  const SELECTOR_COUNT = ".count";

  const COUNT_EASINGS = {
    linear: (t) => t,
    expo: (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t)),
    cubic: (t) => 1 - Math.pow(1 - t, 3),
    back: (t) => {
      const s = 1.25;
      const x = t - 1;
      return 1 + (s + 1) * x * x * x + s * x * x;
    },
  };

  const prefersReduced = NorShared.getPrefersReducedMotion();
  const { qsa } = NorShared;

  const parseNumberText = (text) => {
    const raw = String(text || "").trim();
    const digits = raw.replace(/[^\d]/g, "");
    const value = digits ? Number(digits) : 0;
    const width = digits.length;
    const hasComma = raw.includes(",");
    return { value, width, hasComma };
  };

  const formatNumber = (n, { width, hasComma }) => {
    const int = Math.max(0, Math.floor(n));

    if (hasComma) return int.toLocaleString("en-US");

    let s = String(int);
    if (width > 1) s = s.padStart(width, "0");
    return s;
  };

  const animateTo = (
    el,
    toValue,
    meta,
    { duration = 700, easing = "back" } = {}
  ) => {
    if (!el) return;

    if (prefersReduced) {
      el.textContent = formatNumber(toValue, meta);
      return;
    }

    const ease = COUNT_EASINGS[easing] || COUNT_EASINGS.back;

    const startAt = performance.now();
    const fromValue = 0;

    const tick = (now) => {
      const d = Math.max(120, Number(duration) || 700);
      const t = Math.min(1, (now - startAt) / d);

      const eased = ease(t);
      const current = fromValue + (toValue - fromValue) * eased;

      const display = easing === "back" ? current : Math.min(current, toValue);

      el.textContent = formatNumber(display, meta);

      if (t < 1) {
        requestAnimationFrame(tick);
      } else {
        el.textContent = formatNumber(toValue, meta);
      }
    };

    requestAnimationFrame(tick);
  };

  const prepare = (el) => {
    if (!el) return;
    if (el.getAttribute("data-nor-count-prepared") === "1") return;

    const { value, width, hasComma } = parseNumberText(el.textContent);

    el.setAttribute("data-nor-count-value", String(value));
    el.setAttribute("data-nor-count-width", String(width));
    el.setAttribute("data-nor-count-comma", hasComma ? "1" : "0");

    el.textContent = formatNumber(0, { width, hasComma });

    el.setAttribute("data-nor-count-prepared", "1");
  };

  const runOne = (el) => {
    if (!el) return;
    if (el.getAttribute("data-nor-count-done") === "1") return;

    const toValue = Number(el.getAttribute("data-nor-count-value") || "0");
    const width = Number(el.getAttribute("data-nor-count-width") || "0");
    const hasComma = el.getAttribute("data-nor-count-comma") === "1";

    const durationAttrRaw = el.getAttribute("data-nor-count-duration");
    const hasDurationOverride = durationAttrRaw !== null && String(durationAttrRaw).trim() !== "";
    const duration = Number(durationAttrRaw || "700");

    const easeAttr = String(el.getAttribute("data-nor-count-ease") || "").trim();

    const digitsByValue = String(Math.max(0, Math.floor(Math.abs(toValue)))).length;
    const digitsByWidth = Number.isFinite(width) && width > 0 ? width : 0;
    const digitCount = Math.max(1, digitsByWidth || digitsByValue);

    const easing =
      easeAttr ||
      (digitCount <= 2 ? "linear" : "back");

    const autoDuration = (() => {
      if (hasDurationOverride) return duration;

      if (digitCount === 1) return 420;
      if (digitCount === 2) return 520;

      return duration;
    })();

    animateTo(el, toValue, { width, hasComma }, { duration: autoDuration, easing });

    el.setAttribute("data-nor-count-done", "1");
  };

  const run = () => {
    const els = qsa(SELECTOR_COUNT);
    if (els.length === 0) return;

    els.forEach(prepare);

    if (!("IntersectionObserver" in window)) {
      els.forEach(runOne);
      return;
    }

    const io = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          runOne(entry.target);
          io.unobserve(entry.target);
        }
      },
      {
        root: null,
        rootMargin: "0px 0px -10% 0px",
        threshold: 0.1,
      }
    );

    els.forEach((el) => {
      const r = el.getBoundingClientRect();
      const inView = r.bottom > 0 && r.top < (window.innerHeight || 0);
      if (inView) {
        runOne(el);
        return;
      }
      io.observe(el);
    });
  };

  const bind = () => {
    NorShared.onDomReady(run);
    NorShared.onPageShow(() => {
      run();
    }, { persistedOnly: true });
  };

  const init = () => {
    bind();
  };

  init();
})();

// ========================================
// Hero visual
// ========================================
(() => {
  const CLASS_HOME = "home";
  const SELECTOR_HERO = ".hero";
  const SELECTOR_VISUAL_TOP = ".visual .visual-top";
  const SELECTOR_VISUAL_BOTTOM = ".visual .visual-bottom";
  const MQ_DESKTOP = "(min-width: 1024px)";
  const MQ_TABLET = "(min-width: 768px)";

  const prefersReduced = NorShared.getPrefersReducedMotion();
  const body = document.body;
  const hero = document.querySelector(SELECTOR_HERO);
  const topEl = hero ? hero.querySelector(SELECTOR_VISUAL_TOP) : null;
  const bottomEl = hero ? hero.querySelector(SELECTOR_VISUAL_BOTTOM) : null;
  const root = document.documentElement;

  let mqDesktop = null;
  let mqTablet = null;
  let raf = 0;
  let cfg = { reachY: 0, moveX: 0 };

  const clamp01 = (n) => Math.min(1, Math.max(0, n));

  const readRootTokenNumber = (tokenName, fallback) => {
    if (!root) return fallback;

    const raw = window.getComputedStyle(root).getPropertyValue(tokenName);
    const n = Number.parseFloat(String(raw || "").trim());
    return Number.isFinite(n) ? n : fallback;
  };

  const getConfig = () => {
    if (mqDesktop?.matches) {
      return {
        reachY: readRootTokenNumber("--visual-desktop-reach-y", 600),
        moveX: readRootTokenNumber("--visual-desktop-shift-x", 200),
      };
    }
    if (mqTablet?.matches) {
      return {
        reachY: readRootTokenNumber("--visual-tablet-reach-y", 500),
        moveX: readRootTokenNumber("--visual-tablet-shift-x", 140),
      };
    }
    return {
      reachY: readRootTokenNumber("--visual-mobile-reach-y", 400),
      moveX: readRootTokenNumber("--visual-mobile-shift-x", 100),
    };
  };

  const apply = () => {
    raf = 0;

    const y = Math.max(0, window.scrollY || window.pageYOffset || 0);
    const p = clamp01(y / Math.max(1, cfg.reachY));

    const x = Math.round((1 - p) * cfg.moveX);

    topEl.style.transform = `translate3d(${-x}px, 0, 0)`;
    bottomEl.style.transform = `translate3d(${x}px, 0, 0)`;
  };

  const request = () => {
    if (raf) return;
    raf = window.requestAnimationFrame(apply);
  };

  const onResize = () => {
    cfg = getConfig();
    request();
  };

  const bind = () => {
    window.addEventListener("scroll", request, { passive: true });
    window.addEventListener("resize", onResize);

    NorShared.onPageShow(() => {
      cfg = getConfig();
      request();
    });

    NorShared.onPageHide(() => {
      if (raf) {
        cancelAnimationFrame(raf);
        raf = 0;
      }
    });
  };

  const init = () => {
    if (!body?.classList.contains(CLASS_HOME)) return;
    if (!hero || !topEl || !bottomEl) return;

    if (prefersReduced) {
      topEl.style.transform = "";
      bottomEl.style.transform = "";
      return;
    }

    mqDesktop = NorShared.mq(MQ_DESKTOP);
    mqTablet = NorShared.mq(MQ_TABLET);
    cfg = getConfig();

    request();
    bind();
  };

  init();
})();

// ========================================
// Skeleton (Works detail only / lightweight)
// ========================================
(() => {
  const SELECTOR_WORKS_CONTENT = ".content-works, .content-writings";
  const SELECTOR_FIGURE = "figure.skeleton-figure";
  const SELECTOR_IMG = "img";
  const CLASS_SKELETON = "skeleton";
  const CLASS_SKELETON_LOADER = "skeleton-loader";
  const CLASS_IS_SKELETON = "is-skeleton";
  const CLASS_IS_LOADED = "is-loaded";
  const ATTR_NOR_SKELETON_REL = "data-nor-skeleton-rel";
  const ATTR_NOR_SKELETON_HIDE = "data-nor-skeleton-hide";
  const ATTR_NOR_FIGURE_SKELETON = "data-nor-figure-skeleton";
  const ATTR_NOR_SKELETON = "data-nor-skeleton";
  const ATTR_SKELETON = "data-skeleton";
  const ATTR_SKELETON_ANIM = "data-skeleton-anim";

  const ANIM_KEYS = ["bars-1", "bars-2", "bars-3", "bars-4", "bars-5", "bars-6"];
  const prefersReduced = NorShared.getPrefersReducedMotion();
  const { qs, qsa } = NorShared;
  const worksContentEl = qs(SELECTOR_WORKS_CONTENT);

  // Skip the whole skeleton module on non-works-detail pages.
  if (!worksContentEl) return;

  const getPageAnimKey = () => {
    const seqKey = "nor.skeleton.anim.seq.v1";
    try {
      const raw = window.sessionStorage.getItem(seqKey);
      const n0 = raw === null ? -1 : Number(raw);
      const next = Number.isFinite(n0) ? (n0 + 1) : 0;
      const idx = ((next % ANIM_KEYS.length) + ANIM_KEYS.length) % ANIM_KEYS.length;
      window.sessionStorage.setItem(seqKey, String(idx));
      return ANIM_KEYS[idx];
    } catch {
    }

    try {
      return ANIM_KEYS[Math.floor(Math.random() * ANIM_KEYS.length)] || "bars-1";
    } catch {
      return "bars-1";
    }
  };

  const getPageAnimId = (animKey) => {
    const m = String(animKey || "").match(/(\d+)$/);
    return m ? m[1] : "1";
  };

  const isImgLoaded = (img) => Boolean(img && img.complete && img.naturalWidth > 0);
  const shouldUseDecode = (img) => {
    if (prefersReduced || typeof img?.decode !== "function") return false;
    const loadingHint = String(img.getAttribute("loading") || img.loading || "").toLowerCase();
    return loadingHint !== "lazy";
  };

  const ensureRelative = (el) => {
    if (!el || !(el instanceof HTMLElement)) return;
    const cs = window.getComputedStyle(el);
    if (cs.position === "static") {
      el.style.position = "relative";
      el.setAttribute(ATTR_NOR_SKELETON_REL, "1");
    }
  };

  const restoreRelative = (el) => {
    if (!el || !(el instanceof HTMLElement)) return;
    if (el.getAttribute(ATTR_NOR_SKELETON_REL) === "1") {
      el.style.position = "";
      if (el.getAttribute("style") === "") el.removeAttribute("style");
      el.removeAttribute(ATTR_NOR_SKELETON_REL);
    }
  };

  const hideImgForSkeleton = (img) => {
    if (!img) return;
    if (img.getAttribute(ATTR_NOR_SKELETON_HIDE) === "1") return;

    img.style.opacity = "0";
    img.style.transition = prefersReduced ? "" : "opacity 240ms ease";
    img.setAttribute(ATTR_NOR_SKELETON_HIDE, "1");
  };

  const showImgAfterSkeleton = (img) => {
    if (!img) return;
    if (img.getAttribute(ATTR_NOR_SKELETON_HIDE) !== "1") return;

    img.style.opacity = "";
    img.style.transition = "";
    if (img.getAttribute("style") === "") img.removeAttribute("style");
    img.removeAttribute(ATTR_NOR_SKELETON_HIDE);
  };

  const syncSkeletonBox = (figure, sk, img) => {
    if (!figure || !sk || !img) return;

    const fr = figure.getBoundingClientRect();
    const ir = img.getBoundingClientRect();

    const top = Math.max(0, ir.top - fr.top);
    const left = Math.max(0, ir.left - fr.left);
    const width = Math.max(0, ir.width);
    const height = Math.max(0, ir.height);

    sk.style.top = `${top}px`;
    sk.style.left = `${left}px`;
    sk.style.width = `${width}px`;
    sk.style.height = `${height}px`;
  };

  const getDirectSkeleton = (figure) => {
    if (!figure) return null;
    for (const node of Array.from(figure.children)) {
      if (node instanceof HTMLElement && node.classList.contains(CLASS_SKELETON)) {
        return node;
      }
    }
    return null;
  };

  const mountSkeletonLayer = (figure, img, pageAnimId, pageAnimKey) => {
    if (!figure || !img) return null;

    const existing = getDirectSkeleton(figure);
    if (existing) {
      existing.setAttribute(ATTR_SKELETON, pageAnimId);
      existing.setAttribute(ATTR_SKELETON_ANIM, pageAnimKey);
      syncSkeletonBox(figure, existing, img);
      return existing;
    }

    ensureRelative(figure);

    const sk = document.createElement("div");
    sk.className = CLASS_SKELETON;
    sk.setAttribute("aria-hidden", "true");
    sk.setAttribute(ATTR_NOR_SKELETON, "1");
    sk.setAttribute(ATTR_SKELETON, pageAnimId);
    sk.setAttribute(ATTR_SKELETON_ANIM, pageAnimKey);

    const loader = document.createElement("div");
    loader.className = CLASS_SKELETON_LOADER;
    loader.setAttribute("aria-hidden", "true");
    sk.appendChild(loader);

    sk.style.position = "absolute";
    sk.style.inset = "auto";
    sk.style.borderRadius = "inherit";
    sk.style.pointerEvents = "none";
    sk.style.top = "0";
    sk.style.left = "0";
    sk.style.width = "0";
    sk.style.height = "0";

    figure.appendChild(sk);
    syncSkeletonBox(figure, sk, img);
    requestAnimationFrame(() => syncSkeletonBox(figure, sk, img));

    return sk;
  };

  const unmountSkeletonLayer = (figure) => {
    if (!figure) return;
    const sk = getDirectSkeleton(figure);
    if (sk) sk.remove();
    restoreRelative(figure);
  };

  const applySkeletonToFigure = (figure, pageAnimId, pageAnimKey) => {
    if (!figure) return;
    if (figure.getAttribute(ATTR_NOR_FIGURE_SKELETON) === "1") return;

    const img = figure.querySelector(SELECTOR_IMG);
    if (!img) return;

    if (isImgLoaded(img)) {
      figure.classList.remove(CLASS_IS_SKELETON);
      figure.classList.add(CLASS_IS_LOADED);
      return;
    }

    figure.classList.add(CLASS_IS_SKELETON);
    figure.setAttribute(ATTR_NOR_FIGURE_SKELETON, "1");
    figure.setAttribute(ATTR_SKELETON, pageAnimId);

    mountSkeletonLayer(figure, img, pageAnimId, pageAnimKey);
    hideImgForSkeleton(img);

    const markLoaded = () => {
      figure.classList.remove(CLASS_IS_SKELETON);
      figure.classList.add(CLASS_IS_LOADED);
      figure.removeAttribute(ATTR_SKELETON);

      unmountSkeletonLayer(figure);
      showImgAfterSkeleton(img);

      if (figure.getAttribute("style") === "") figure.removeAttribute("style");
    };

    const done = () => {
      markLoaded();
    };

    img.addEventListener("load", done, { once: true });
    img.addEventListener("error", done, { once: true });

    if (shouldUseDecode(img)) {
      img
        .decode()
        .then(() => {
          if (isImgLoaded(img)) done();
        })
        .catch(() => {
        });
    }
  };

  const run = (pageAnimId, pageAnimKey) => {
    const figures = qsa(SELECTOR_FIGURE, worksContentEl).filter((f) => Boolean(f.querySelector(SELECTOR_IMG)));
    if (figures.length === 0) return;

    figures.forEach((figure) => applySkeletonToFigure(figure, pageAnimId, pageAnimKey));
  };

  const init = () => {
    const pageAnimKey = getPageAnimKey();
    const pageAnimId = getPageAnimId(pageAnimKey);

    NorShared.onDomReady(() => run(pageAnimId, pageAnimKey));
    NorShared.onPageShow(() => run(pageAnimId, pageAnimKey));
  };

  init();
})();

// ========================================
// Clear buttons
// ========================================
(() => {
  const SELECTOR_CONTACT_FORM = "form.contact-form";

  const bindClearButtons = () => {
    NorShared.bindClearButtons(document, {
      excludeWithinSelector: SELECTOR_CONTACT_FORM,
    });
  };

  const bind = () => {
    NorShared.onDomReady(bindClearButtons);
    NorShared.onPageShow(bindClearButtons);
  };

  const init = () => {
    bind();
  };

  init();
})();

// ========================================
// Contact form
// ========================================
(() => {
  const STATUS_ERROR = "error";
  const STATUS_SUCCESS = "success";
  const STATUS_NONE = "none";
  const CLASS_IS_ERROR = "is-error";
  const CLASS_IS_SUCCESS = "is-success";
  const SELECTOR_CONTACT_FORM = "form.contact-form";
  const SELECTOR_CONTACT_PAGE_HOOK = ".contact-form";
  const SELECTOR_ANNOUNCEMENT = ".announcement";
  const SELECTOR_MAIN = "#site-main";
  const SELECTOR_FIELD = ".field";
  const SELECTOR_FIELDSET = "fieldset.field";
  const SELECTOR_CONTROL = ".control";
  const SELECTOR_FORM_CONTROLS = "input, textarea, select";
  const FIELD_ID = {
    name: "#name",
    organization: "#organization",
    email: "#email",
    urls: "#urls",
    details: "#details",
  };

  const root = document.documentElement;
  const { qs, qsa, focusElement } = NorShared;

  const isContactPage = () =>
    document.body?.classList.contains("contact") || qs(SELECTOR_CONTACT_PAGE_HOOK);

  const normalizeUrlTokens = (value) => {
    return value
      .split(/[\s,]+/g)
      .map((s) => String(s || "").trim())
      .map((s) => s.replace(/[)\]}>、。，．,]+$/g, ""))
      .filter(Boolean);
  };

  const isValidEmail = (value) => {
    const v = String(value || "").trim();
    if (!v) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  };

  const isValidUrl = (value) => {
    const raw = String(value || "").trim();
    if (!raw) return false;

    try {
      const u = new URL(raw);
      return u.protocol === "http:" || u.protocol === "https:";
    } catch {
      return false;
    }
  };

  const setAnnouncement = (announcementEl, status) => {
    if (!announcementEl) return;

    announcementEl.classList.remove(CLASS_IS_ERROR, CLASS_IS_SUCCESS);

    const errorMsg = qs(".message.is-error", announcementEl);
    const successMsg = qs(".message.is-success", announcementEl);

    if (errorMsg) errorMsg.setAttribute("hidden", "");
    if (successMsg) successMsg.setAttribute("hidden", "");

    announcementEl.removeAttribute("role");
    announcementEl.removeAttribute("aria-live");
    announcementEl.removeAttribute("aria-atomic");

    if (status === STATUS_ERROR) {
      announcementEl.classList.add(CLASS_IS_ERROR);
      if (errorMsg) errorMsg.removeAttribute("hidden");

      announcementEl.setAttribute("role", "alert");
      announcementEl.setAttribute("aria-live", "assertive");
      announcementEl.setAttribute("aria-atomic", "true");
    }

    if (status === STATUS_SUCCESS) {
      announcementEl.classList.add(CLASS_IS_SUCCESS);
      if (successMsg) successMsg.removeAttribute("hidden");

      announcementEl.setAttribute("role", "status");
      announcementEl.setAttribute("aria-live", "polite");
      announcementEl.setAttribute("aria-atomic", "true");
    }

    if (status !== STATUS_NONE) {
      const main = qs(SELECTOR_MAIN);
      if (main) focusElement(main, { preventScroll: true });

      const prefersReduced = NorShared.getPrefersReducedMotion();
      announcementEl.scrollIntoView({
        block: "start",
        behavior: prefersReduced ? "auto" : "smooth",
      });
    }
  };

  const updateDescribedByToken = (targetEl, token, shouldHave) => {
    if (!targetEl || !token) return;

    const current = String(targetEl.getAttribute("aria-describedby") || "").trim();
    const tokens = current ? current.split(/\s+/g) : [];
    const has = tokens.includes(token);

    if (shouldHave && !has) {
      tokens.push(token);
      targetEl.setAttribute("aria-describedby", tokens.join(" "));
      return;
    }

    if (!shouldHave && has) {
      const next = tokens.filter((t) => t !== token);
      if (next.length) targetEl.setAttribute("aria-describedby", next.join(" "));
      else targetEl.removeAttribute("aria-describedby");
    }
  };

  const getErrorMessageId = (containerEl) => {
    if (!containerEl) return "";
    const errorMsg = qs(".field-message.is-error[id]", containerEl);
    return errorMsg ? String(errorMsg.getAttribute("id") || "").trim() : "";
  };

  const resetFormUiState = (formEl) => {
    if (!formEl) return;

    qsa(`${SELECTOR_FIELD}, ${SELECTOR_FIELDSET}`, formEl).forEach((f) => {
      f.classList.remove(CLASS_IS_ERROR, CLASS_IS_SUCCESS);

      const errorId = getErrorMessageId(f);

      if (f.tagName && f.tagName.toLowerCase() === "fieldset") {
        f.removeAttribute("aria-describedby");

        qsa(".choice", f).forEach((c) => c.classList.remove(CLASS_IS_ERROR));

        qsa('input[type="radio"]', f).forEach((radio) => {
          if (radio.disabled) return;
          radio.removeAttribute("aria-invalid");
          updateDescribedByToken(radio, errorId, false);
        });

        return;
      }

      const control = qs(SELECTOR_CONTROL, f);
      const target = control
        ? qs(SELECTOR_FORM_CONTROLS, control)
        : qs(SELECTOR_FORM_CONTROLS, f);

      if (target) {
        target.removeAttribute("aria-invalid");
        updateDescribedByToken(target, errorId, false);
      }

      qsa(".choice", f).forEach((c) => c.classList.remove(CLASS_IS_ERROR));
      qsa("[aria-invalid='true']", f).forEach((el) => el.removeAttribute("aria-invalid"));
    });
  };

  const setFieldState = (fieldEl, state) => {
    if (!fieldEl) return;

    fieldEl.classList.remove(CLASS_IS_ERROR, CLASS_IS_SUCCESS);
    if (state === STATUS_ERROR) fieldEl.classList.add(CLASS_IS_ERROR);
    if (state === STATUS_SUCCESS) fieldEl.classList.add(CLASS_IS_SUCCESS);

    const control = qs(SELECTOR_CONTROL, fieldEl);
    const target = control
      ? qs(SELECTOR_FORM_CONTROLS, control)
      : qs(SELECTOR_FORM_CONTROLS, fieldEl);

    if (!target) return;

    if (state === STATUS_ERROR) target.setAttribute("aria-invalid", "true");
    else target.removeAttribute("aria-invalid");

    const errorId = getErrorMessageId(fieldEl);
    updateDescribedByToken(target, errorId, state === STATUS_ERROR);
  };

  const setChoiceGroupState = (fieldsetEl, state) => {
    if (!fieldsetEl) return;

    const isError = state === STATUS_ERROR;
    const isSuccess = state === STATUS_SUCCESS;

    fieldsetEl.classList.toggle(CLASS_IS_ERROR, isError);
    fieldsetEl.classList.toggle(CLASS_IS_SUCCESS, isSuccess);

    const choices = qsa(".choice", fieldsetEl);
    choices.forEach((choice) => choice.classList.toggle(CLASS_IS_ERROR, isError));

    const errorId = getErrorMessageId(fieldsetEl);

    if (errorId) {
      if (isError) fieldsetEl.setAttribute("aria-describedby", errorId);
      else fieldsetEl.removeAttribute("aria-describedby");
    }

    const radios = qsa('input[type="radio"]', fieldsetEl);
    radios.forEach((radio) => {
      if (radio.disabled) return;

      if (isError) radio.setAttribute("aria-invalid", "true");
      else radio.removeAttribute("aria-invalid");

      updateDescribedByToken(radio, errorId, isError);
    });
  };

  const focusFirstInvalid = (formEl) => {
    const firstErrorField = qs(
      `${SELECTOR_FIELD}.${CLASS_IS_ERROR}, ${SELECTOR_FIELDSET}.${CLASS_IS_ERROR}`,
      formEl
    );
    if (!firstErrorField) return;

    if (firstErrorField.tagName.toLowerCase() === "fieldset") {
      const checkedRadio = qs(
        'input[type="radio"]:checked:not(:disabled)',
        firstErrorField
      );
      const firstRadio =
        checkedRadio || qs('input[type="radio"]:not(:disabled)', firstErrorField);

      focusElement(firstRadio, { preventScroll: true });
      return;
    }

    const control = qs(SELECTOR_CONTROL, firstErrorField);
    const input = control
      ? qs(SELECTOR_FORM_CONTROLS, control)
      : qs(SELECTOR_FORM_CONTROLS, firstErrorField);

    focusElement(input, { preventScroll: true });
  };

  const initLiveRevalidate = (formEl, validators, options = {}) => {
    const purposeName = String(options.purposeName || "").trim();
    const purposeRadioSel = purposeName
      ? `input[type="radio"][name="${CSS.escape(purposeName)}"]`
      : "";
    const onUserEdit = typeof options.onUserEdit === "function" ? options.onUserEdit : null;
    const announcementEl = options.announcementEl || null;
    const softValidatorsById =
      options.softValidatorsById && typeof options.softValidatorsById === "object"
        ? options.softValidatorsById
        : {};

    const runEditSideEffects = () => {
      if (onUserEdit) onUserEdit();
      if (announcementEl) setAnnouncement(announcementEl, STATUS_NONE);
    };

    const clearChoiceGroupState = (fieldsetEl) => {
      if (!fieldsetEl) return;

      fieldsetEl.classList.remove(CLASS_IS_ERROR, CLASS_IS_SUCCESS);

      const errorId = getErrorMessageId(fieldsetEl);

      fieldsetEl.removeAttribute("aria-describedby");

      const choices = qsa(".choice", fieldsetEl);
      choices.forEach((choice) => choice.classList.remove(CLASS_IS_ERROR));

      const radios = qsa('input[type="radio"]', fieldsetEl);
      radios.forEach((radio) => {
        if (radio.disabled) return;
        radio.removeAttribute("aria-invalid");
        updateDescribedByToken(radio, errorId, false);
      });
    };

    formEl.addEventListener("input", (e) => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;

      runEditSideEffects();

      if (
        purposeRadioSel &&
        el.matches(purposeRadioSel)
      ) {
        const fs = el.closest(SELECTOR_FIELDSET);
        if (fs) clearChoiceGroupState(fs);
        return;
      }

      const field = el.closest(SELECTOR_FIELD);
      if (field) setFieldState(field, STATUS_NONE);
    });

    formEl.addEventListener("change", (e) => {
      const el = e.target;
      if (!(el instanceof HTMLElement)) return;

      runEditSideEffects();

      if (
        el.matches("input, textarea") &&
        el.getAttribute("data-nor-just-cleared") === "1"
      ) {
        el.removeAttribute("data-nor-just-cleared");
        const field = el.closest(SELECTOR_FIELD);
        if (field) setFieldState(field, STATUS_NONE);
        return;
      }

      if (
        purposeRadioSel &&
        el.matches(purposeRadioSel)
      ) {
        const fs = el.closest(SELECTOR_FIELDSET);
        if (!fs) return;

        const checked = qs(
          `${purposeRadioSel}:checked`,
          fs
        );

        setChoiceGroupState(fs, checked ? STATUS_NONE : STATUS_ERROR);
        return;
      }

      const id = String(el.getAttribute("id") || "").trim();
      if (!id) return;

      const validator = softValidatorsById[id];
      if (typeof validator !== "function") return;
      validator({ mode: "soft" });
    });
  };

  const initContactForm = () => {
    if (!isContactPage()) return;

    const form = qs(SELECTOR_CONTACT_FORM);
    if (!form) return;

    const announcement = qs(SELECTOR_ANNOUNCEMENT);

    let hasSucceeded = false;

    const getFieldByControlId = (id) => {
      const el = qs(id, form) || qs(id);
      return el ? el.closest(SELECTOR_FIELD) : null;
    };

    const getPurposeFieldset = () => {
      const fieldsets = qsa(SELECTOR_FIELDSET, form);
      return (
        fieldsets.find((fs) =>
          Boolean(qs('input[type="radio"]:not(:disabled)', fs))
        ) || null
      );
    };

    const purposeFieldset = getPurposeFieldset();
    const purposeName = (() => {
      if (!purposeFieldset) return "";
      const firstRadio = qs('input[type="radio"]:not(:disabled)', purposeFieldset);
      return firstRadio ? String(firstRadio.getAttribute("name") || "").trim() : "";
    })();

    const fields = {
      purpose: purposeFieldset,
      name: getFieldByControlId(FIELD_ID.name),
      organization: getFieldByControlId(FIELD_ID.organization),
      email: getFieldByControlId(FIELD_ID.email),
      urls: getFieldByControlId(FIELD_ID.urls),
      details: getFieldByControlId(FIELD_ID.details),
    };

    const validatePurpose = ({ mode } = { mode: "hard" }) => {
      const fs = fields.purpose;
      if (!fs) return true;
      if (!purposeName) return true;

      const checked = qs(
        `input[type="radio"][name="${CSS.escape(purposeName)}"]:checked`,
        fs
      );
      const ok = Boolean(checked);

      if (!ok) setChoiceGroupState(fs, STATUS_ERROR);
      else setChoiceGroupState(fs, mode === "soft" ? STATUS_NONE : STATUS_SUCCESS);

      return ok;
    };

    const resolveValidationInput = (
      fieldEl,
      inputSel,
      { clearWhenUnavailable = true } = {}
    ) => {
      if (!fieldEl) return null;

      const input = qs(inputSel, fieldEl) || qs(inputSel);
      if (input && !input.disabled) return input;

      if (clearWhenUnavailable) setFieldState(fieldEl, STATUS_NONE);
      return null;
    };

    const reflectFieldValidationState = (fieldEl, ok, mode) => {
      if (!fieldEl) return Boolean(ok);
      if (!ok) {
        setFieldState(fieldEl, STATUS_ERROR);
        return false;
      }

      setFieldState(fieldEl, mode === "soft" ? STATUS_NONE : STATUS_SUCCESS);
      return true;
    };

    const validateRequiredText = (fieldEl, inputSel, { mode } = { mode: "hard" }) => {
      if (!fieldEl) return true;

      const input = resolveValidationInput(fieldEl, inputSel);
      if (!input) return true;

      const v = String(input.value || "").trim();
      const ok = v.length > 0;
      return reflectFieldValidationState(fieldEl, ok, mode);
    };

    const validateEmail = ({ mode } = { mode: "hard" }) => {
      const fieldEl = fields.email;
      if (!fieldEl) return true;

      const input = resolveValidationInput(fieldEl, FIELD_ID.email);
      if (!input) return true;

      const v = String(input.value || "").trim();
      const ok = Boolean(v) && isValidEmail(v);
      return reflectFieldValidationState(fieldEl, ok, mode);
    };

    const validateUrls = ({ mode } = { mode: "hard" }) => {
      const fieldEl = fields.urls;
      if (!fieldEl) return true;

      const input = resolveValidationInput(fieldEl, FIELD_ID.urls);
      if (!input) return true;

      const raw = String(input.value || "").trim();
      const tokens = normalizeUrlTokens(raw);
      const ok = Boolean(raw) && tokens.length > 0 && tokens.every(isValidUrl);
      return reflectFieldValidationState(fieldEl, ok, mode);
    };

    const validateDetails = ({ mode } = { mode: "hard" }) => {
      const fieldEl = fields.details;
      if (!fieldEl) return true;

      const textarea = resolveValidationInput(fieldEl, FIELD_ID.details, {
        clearWhenUnavailable: false,
      });
      if (!textarea) return true;

      const max = Number(textarea.getAttribute("maxlength") || "0");
      if (!max) return true;

      const len = String(textarea.value || "").length;
      const ok = len <= max;
      return reflectFieldValidationState(fieldEl, ok, mode);
    };

    const validators = [
      ({ mode } = { mode: "hard" }) => validatePurpose({ mode }),
      ({ mode } = { mode: "hard" }) =>
        validateRequiredText(fields.name, FIELD_ID.name, { mode }),
      ({ mode } = { mode: "hard" }) =>
        validateRequiredText(fields.organization, FIELD_ID.organization, { mode }),
      validateEmail,
      validateUrls,
      validateDetails,
    ];
    const softValidatorsById = {
      name: validators[1],
      organization: validators[2],
      email: validators[3],
      urls: validators[4],
      details: validators[5],
    };

    const getSubmitBtn = () => qs('button[type="submit"]', form);

    const unlockOnEdit = () => {
      if (!hasSucceeded) return;

      const submitBtn = getSubmitBtn();
      if (submitBtn) submitBtn.disabled = false;

      if (announcement) setAnnouncement(announcement, STATUS_NONE);

      resetFormUiState(form);

      hasSucceeded = false;
    };

    NorShared.bindClearButtons(form, {
      syncOnReset: true,
      onBeforeClear: ({ target }) => {
        target.setAttribute("data-nor-just-cleared", "1");
      },
    });
    initLiveRevalidate(form, validators, {
      purposeName,
      onUserEdit: unlockOnEdit,
      announcementEl: announcement,
      softValidatorsById,
    });

    form.addEventListener("submit", (e) => {
      const isUiOnly = form.hasAttribute("data-ui-only");

      resetFormUiState(form);

      const results = validators.map((fn) => fn({ mode: "hard" }));
      const ok = results.every(Boolean);

      if (!ok) {
        e.preventDefault();
        setAnnouncement(announcement, STATUS_ERROR);
        focusFirstInvalid(form);
        return;
      }

      if (isUiOnly) {
        e.preventDefault();

        setAnnouncement(announcement, STATUS_SUCCESS);

        form.reset();

        resetFormUiState(form);

        const submitBtn = getSubmitBtn();
        if (submitBtn) submitBtn.disabled = true;

        hasSucceeded = true;
      }

    });
  };

  const bind = () => {
    NorShared.onDomReady(initContactForm);
  };

  const init = () => {
    bind();

    if (isContactPage()) root.classList.add("has-contact-js");
  };

  init();
})();

// ========================================
// Tighten
// ========================================
(() => {
  const ATTR_TIGHTEN = "data-tighten";
  const ATTR_TIGHTEN_SLOT = "data-tighten-slot";
  const CLASS_COUNT = "count";
  const SELECTOR_COUNT = ".count";
  const SELECTOR_PAGES_H1 = "body.pages h1";
  const SELECTOR_RELATED_HEAD = ".related .head h2 span";
  const SELECTOR_INDICES_HEAD = ".indices .head h2 span";

  const USE_TIGHTEN_PRESET = true;

  const SIZE_SLOTS_REM = [3.2, 4.8, 7.2];

  const COUNT_SLOT_TIER_BIAS = {
    "3.2": 0,
    "4.8": 0,
    "7.2": 2,
  };

  const RULES = [
    { selector: SELECTOR_COUNT, kind: "count" },
    { selector: SELECTOR_PAGES_H1, kind: "text" },
    { selector: SELECTOR_RELATED_HEAD, kind: "text" },
    { selector: SELECTOR_INDICES_HEAD, kind: "indices" },
  ];

  const { qsa } = NorShared;

  const isGlobalDisabled = () =>
    document.documentElement?.getAttribute("data-nor-tighten") === "0" ||
    document.body?.getAttribute("data-nor-tighten") === "0";

  const isElementAutoDisabled = (el) => {
    if (!el || !(el instanceof HTMLElement)) return true;
    const v = String(el.getAttribute(ATTR_TIGHTEN) || "").trim().toLowerCase();
    return v === "off" || v === "none";
  };

  const hasExplicitTighten = (el) => {
    const v = String(el?.getAttribute(ATTR_TIGHTEN) || "").trim();
    return v.length > 0;
  };

  const clampTier = (n) => Math.max(0, Math.min(6, Math.floor(n)));

  const TIER_TO_CHARS = (() => {
    if (!USE_TIGHTEN_PRESET) return {};

    return {
      1: ["A", "I", "X"],
      2: [
        "1", "3", "4", "6", "7", "B", "D", "E", "F", "H", "J", "K",
        "L", "M", "N", "P", "R", "S", "U", "V", "W", "Y", "Z",
        "ア", "ニ", "資"
      ],
      3: ["0", "2", "5", "8", "9", "C", "G", "O", "Q", "T"],
      5: ["ビ"],
    };
  })();

  const CHAR_TO_TIER = (() => {
    const map = {};

    for (const [tierKey, chars] of Object.entries(TIER_TO_CHARS)) {
      const tier = Number(tierKey);
      if (!Number.isFinite(tier)) continue;
      if (!Array.isArray(chars)) continue;

      for (const raw of chars) {
        const c = String(raw || "").trim();
        if (!c) continue;
        if (c.length !== 1) continue;

        if (map[c] === undefined) {
          map[c] = tier;
        }
      }
    }

    return map;
  })();

  const pxToRem = (px) => {
    const n = Number(String(px).replace("px", "").trim());
    if (!Number.isFinite(n)) return null;

    const rootPx = Number(
      String(window.getComputedStyle(document.documentElement).fontSize || "16px")
        .replace("px", "")
        .trim()
    );
    const base = Number.isFinite(rootPx) && rootPx > 0 ? rootPx : 16;

    return n / base;
  };

  const nearestSizeSlot = (fontSizeRem) => {
    if (!Number.isFinite(fontSizeRem)) return null;

    let best = SIZE_SLOTS_REM[0];
    let bestDiff = Math.abs(fontSizeRem - best);

    for (const s of SIZE_SLOTS_REM) {
      const d = Math.abs(fontSizeRem - s);
      if (d < bestDiff) {
        best = s;
        bestDiff = d;
      }
    }

    if (bestDiff > 0.35) return null;

    return String(best);
  };

  const applySizeOffset = (el) => {
    if (!el || !(el instanceof HTMLElement)) return;

    const cs = window.getComputedStyle(el);
    const fs = cs ? cs.fontSize : "";
    const rem = pxToRem(fs);
    const slot = nearestSizeSlot(rem);

    if (!slot) return;

    el.setAttribute(ATTR_TIGHTEN_SLOT, slot);
  };

  const stripLegacyTightenClassesOnCount = (el) => {
    if (!el || !(el instanceof HTMLElement)) return;
    if (!el.classList.contains(CLASS_COUNT)) return;
    if (isElementAutoDisabled(el)) return;
    if (hasExplicitTighten(el)) return;

    for (let i = 1; i <= 6; i += 1) {
      el.classList.remove(`tighten-${i}`);
    }
  };

  const getFirstCharForCount = (el) => {
    const valueAttr = String(el.getAttribute("data-nor-count-value") || "").trim();
    const raw = valueAttr || String(el.textContent || "").trim();
    if (!raw) return "";

    if (!/^\d+$/.test(raw)) return "";

    return raw[0] || "";
  };

  const getFirstCharForText = (el) => {
    const raw = String(el.textContent || "").trim();
    if (!raw) return "";
    return raw[0] || "";
  };

  const applyCountSlotBias = (el, tier) => {
    if (!el || !(el instanceof HTMLElement)) return tier;
    if (!el.classList.contains(CLASS_COUNT)) return tier;

    const slot = String(el.getAttribute(ATTR_TIGHTEN_SLOT) || "").trim();
    const bias = COUNT_SLOT_TIER_BIAS[slot];

    if (bias === undefined) return tier;

    return clampTier(Number(tier) + Number(bias));
  };

  const applyTierFromFirstChar = (el, firstChar) => {
    if (!el || !(el instanceof HTMLElement)) return;
    if (isElementAutoDisabled(el)) return;
    if (hasExplicitTighten(el)) return;

    const mapped = CHAR_TO_TIER[firstChar];
    if (mapped === undefined || mapped === null) return;

    const n = Number(mapped);
    if (!Number.isFinite(n)) return;

    const tier = applyCountSlotBias(el, n);

    el.setAttribute(ATTR_TIGHTEN, String(clampTier(tier)));
  };

  const run = () => {
    if (isGlobalDisabled()) return;

    for (const rule of RULES) {
      const els = qsa(rule.selector);
      if (els.length === 0) continue;

      for (const el of els) {
        if (!(el instanceof HTMLElement)) continue;
        if (rule.kind === "count") stripLegacyTightenClassesOnCount(el);
        applySizeOffset(el);
        if (rule.kind === "indices") {
          if (isElementAutoDisabled(el)) continue;
          if (hasExplicitTighten(el)) continue;
          el.setAttribute(ATTR_TIGHTEN, "0");
          continue;
        }
        const first =
          rule.kind === "count" ? getFirstCharForCount(el) : getFirstCharForText(el);
        if (!first) continue;
        applyTierFromFirstChar(el, first);
      }
    }
  };

  const bind = () => {
    NorShared.onDomReady(run);
    NorShared.onPageShow(run);
  };

  const init = () => {
    bind();
  };

  init();
})();

// ========================================
// Glitch
// ========================================
(() => {
  const ATTR_GLITCH = "data-nor-glitch";
  const ATTR_GLITCH_RAN = "data-nor-glitch-ran";
  const ODDS = 300;
  const HOLD_MS = 160;
  const RESTORE_MS = 860;
  const TICK_MS = 36;
  const JITTER = 0.45;

  const POOL = [
    "⟡", "⌁", "⎔", "⟢", "⟣", "⟠", "⟟", "⟐", "⌬", "⟑",
    "∿", "⋄", "⋆", "⟂", "⟃", "⟄", "⟇", "⟒", "⟓", "⟔",
  ];

  const root = document.documentElement;
  const body = document.body;

  const isSkippableChar = (ch) =>
    /\s/.test(ch) || /[\.,:;!\?\-–—\(\)\[\]\{\}\/\\'\"\u2019]/.test(ch);

  const shouldSkipNode = (node) => {
    if (!node) return true;

    const parent = node.parentElement;
    if (!parent) return true;

    if (parent.closest("[data-nor-glitch-skip]")) return true;

    if (parent.closest(".hero .visual")) return true;

    const tag = (parent.tagName || "").toLowerCase();
    if (tag === "script" || tag === "style" || tag === "noscript") return true;

    if (parent.closest("input, textarea, select, button")) return true;
    if (parent.closest("[contenteditable='true']")) return true;

    return false;
  };

  const garble = (s) => {
    const chars = Array.from(String(s));
    return chars
      .map((ch) => {
        if (isSkippableChar(ch)) return ch;
        return POOL[Math.floor(Math.random() * POOL.length)];
      })
      .join("");
  };

  const bind = (cleanup) => {
    NorShared.onPageHide(cleanup, { once: true });
  };

  const init = () => {
    const enabled =
      (root && root.getAttribute(ATTR_GLITCH) === "1") ||
      (body && body.getAttribute(ATTR_GLITCH) === "1");

    const globallyDisabled =
      (root && root.getAttribute(ATTR_GLITCH) === "0") ||
      (body && body.getAttribute(ATTR_GLITCH) === "0");

    if (!enabled || globallyDisabled) return;

    const hit = Math.floor(Math.random() * ODDS) === 0;
    if (!hit) return;

    if (root && root.getAttribute(ATTR_GLITCH_RAN) === "1") return;
    if (root) root.setAttribute(ATTR_GLITCH_RAN, "1");

    const originals = [];

    try {
      const walker = document.createTreeWalker(
        body || root,
        NodeFilter.SHOW_TEXT,
        {
          acceptNode: (node) => {
            if (!node) return NodeFilter.FILTER_REJECT;
            const text = String(node.nodeValue || "");
            if (!text.trim()) return NodeFilter.FILTER_REJECT;
            if (shouldSkipNode(node)) return NodeFilter.FILTER_REJECT;
            return NodeFilter.FILTER_ACCEPT;
          },
        }
      );

      let n = walker.nextNode();
      while (n) {
        const original = String(n.nodeValue || "");
        originals.push([n, original]);
        n.nodeValue = garble(original);
        n = walker.nextNode();
      }
    } catch {
      try {
        if (root) root.removeAttribute(ATTR_GLITCH_RAN);
      } catch {
      }
      return;
    }

    if (originals.length === 0) {
      try {
        if (root) root.removeAttribute(ATTR_GLITCH_RAN);
      } catch {
      }
      return;
    }

    const perNodeOrder = new WeakMap();
    const getOrder = (node, len) => {
      const cached = perNodeOrder.get(node);
      if (cached && Array.isArray(cached) && cached.length === len) return cached;

      const arr = new Array(len);
      for (let i = 0; i < len; i += 1) {
        const rightness = i / Math.max(1, len - 1);
        const noise = (Math.random() - 0.5) * 2 * JITTER / Math.max(1, len - 1);
        arr[i] = rightness + noise;
      }

      perNodeOrder.set(node, arr);
      return arr;
    };

    const makeFrame = (originalText, order, t01) => {
      const chars = Array.from(String(originalText || ""));
      const len = chars.length;
      if (!len) return "";

      const threshold = 1 - t01;

      return chars
        .map((ch, i) => {
          if (isSkippableChar(ch)) return ch;

          const o = order[i];
          const locked = o >= threshold;
          if (locked) return ch;

          return POOL[Math.floor(Math.random() * POOL.length)];
        })
        .join("");
    };

    let timer = 0;

    const cleanup = () => {
      if (timer) {
        clearTimeout(timer);
        timer = 0;
      }

      try {
        if (root) root.removeAttribute(ATTR_GLITCH_RAN);
      } catch {
      }
    };

    const restoreInstant = () => {
      for (const [node, original] of originals) {
        try {
          if (node) node.nodeValue = original;
        } catch {
        }
      }
      cleanup();
    };

    const startRestore = () => {
      const startAt = performance.now();
      const total = Math.max(240, Number(RESTORE_MS) || 860);
      const tick = Math.max(16, Number(TICK_MS) || 36);

      const step = () => {
        const now = performance.now();
        const t01 = Math.min(1, (now - startAt) / total);

        for (const [node, original] of originals) {
          if (!node) continue;

          try {
            const chars = Array.from(String(original || ""));
            const len = chars.length;
            if (!len) continue;

            const order = getOrder(node, len);
            node.nodeValue = makeFrame(original, order, t01);
          } catch {
          }
        }

        if (t01 < 1) {
          timer = window.setTimeout(step, tick);
          return;
        }

        restoreInstant();
      };

      step();
    };

    timer = window.setTimeout(() => {
      startRestore();
    }, Math.max(0, Number(HOLD_MS) || 160));

    bind(cleanup);
  };

  init();
})();

// ========================================
// Overscroll top / bottom (prototype)
// ========================================
(() => {
  // Kept equal to --motion-duration-s so the CSS fade-back and the class
  // removal land together.
  const RELEASE_DELAY_MS = 180;
  const CLASS_OVERSCROLLING_TOP = "is-overscrolling-top";
  const CLASS_OVERSCROLLING_BOTTOM = "is-overscrolling-bottom";

  const root = document.documentElement;
  let releaseTimer = 0;
  let bottomReleaseTimer = 0;

  const release = () => {
    releaseTimer = 0;
    root.classList.remove(CLASS_OVERSCROLLING_TOP);
  };

  const releaseBottom = () => {
    bottomReleaseTimer = 0;
    root.classList.remove(CLASS_OVERSCROLLING_BOTTOM);
  };

  // Sub-pixel/rounding differences mean scrollY + innerHeight rarely lands
  // on scrollHeight exactly, so allow a small tolerance instead of requiring
  // an exact match (same reasoning as the top check's scrollY <= 0).
  const isAtBottom = () =>
    window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 1;

  const onWheel = (e) => {
    // scrollY can already read slightly negative mid-bounce on macOS Chrome,
    // so treat "at or past the top" as the trigger rather than an exact 0.
    if (window.scrollY <= 0 && e.deltaY < 0) {
      root.classList.add(CLASS_OVERSCROLLING_TOP);

      clearTimeout(releaseTimer);
      releaseTimer = window.setTimeout(release, RELEASE_DELAY_MS);
    }

    if (e.deltaY > 0 && isAtBottom()) {
      root.classList.add(CLASS_OVERSCROLLING_BOTTOM);

      clearTimeout(bottomReleaseTimer);
      bottomReleaseTimer = window.setTimeout(releaseBottom, RELEASE_DELAY_MS);
    }
  };

  const bind = () => {
    window.addEventListener("wheel", onWheel, { passive: true });
  };

  const init = () => {
    bind();
  };

  init();
})();
