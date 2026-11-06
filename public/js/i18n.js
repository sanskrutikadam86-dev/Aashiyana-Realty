// =========================================================
// i18n.js - Language system ka engine
// (i18n = "internationalization", yani kai languages support karna)
//
// Kaam:
//   1. Navbar me language selector lagana
//   2. User ki language browser me yaad rakhna
//   3. Page ke texts ko us language me badalna
//
// Texts badalne ke 2 tarike hain:
//   a) HTML me   data-i18n="nav.home"   likhna
//   b) Neeche SELECTOR_MAP me CSS selector likhna (navbar/footer ke liye,
//      taaki har page ka HTML na badalna pade)
// =========================================================

(function () {
  const STORAGE_KEY = "lang";

  // Dropdown me jo naam dikhenge
  const LANGS = { en: "English", hi: "हिंदी", mr: "मराठी" };

  // ---------- Navbar aur footer: selector -> translation key ----------
  const SELECTOR_MAP = [
    // Navbar
    [".menu a[href='index.html']", "nav.home"],
    [".menu a[href='properties.html']", "nav.properties"],
    [".menu a[href='index.html#areas']", "nav.areas"],
    [".menu a[href='index.html#services']", "nav.services"],
    [".menu a[href='about.html']", "nav.about"],
    [".menu a[href='contact.html']", "nav.contact"],
    [".nav-right a[href^='tel:']", "nav.callNow"],

    // Footer
    [".foot-grid > div:nth-child(1) p", "footer.tagline"],
    [".foot-grid > div:nth-child(2) h4", "footer.quickLinks"],
    [".foot-grid > div:nth-child(3) h4", "footer.areas"],
    [".foot-grid > div:nth-child(4) h4", "footer.contact"],
    ["footer li a[href='index.html']", "nav.home"],
    ["footer li a[href='properties.html']", "nav.properties"],
    ["footer li a[href='about.html']", "nav.about"],
    ["footer li a[href='contact.html']", "nav.contact"],
    ["footer a[href='properties.html?area=Andheri']", "area.Andheri"],
    ["footer a[href='properties.html?area=Bandra']", "area.Bandra"],
    ["footer a[href='properties.html?area=Powai']", "area.Powai"],
    ["footer a[href='properties.html?area=Thane']", "area.Thane"],
    [".foot-grid > div:nth-child(4) li:nth-child(1)", "footer.address"],
    [".copy a[href$='login.php']", "footer.adminLogin"],
  ];

  // ---------- Saved language padho ----------
  function getSavedLang() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      return LANGS[saved] ? saved : "en";
    } catch (e) {
      return "en";
    }
  }

  let current = getSavedLang();

  // ---------- t("key") : key ka text us language me do ----------
  function t(key) {
    const dict = TRANSLATIONS[current] || {};
    if (dict[key] !== undefined) return dict[key];
    // Is language me nahi mila to English use karo
    if (TRANSLATIONS.en[key] !== undefined) return TRANSLATIONS.en[key];
    return key;
  }

  // ---------- tOr("key", "fallback") : key ka translation ho to wo, nahi to fallback ----------
  // (database ke badge jaise texts ke liye, jinka translation shayad na ho)
  function tOr(key, fallback) {
    return TRANSLATIONS.en[key] !== undefined ? t(key) : fallback;
  }

  // ---------- price("₹1.45 Cr") : price ke shabd us language me badalna ----------
  function price(label) {
    return String(label)
      .replace(" / month", " " + t("price.perMonth"))
      .replace(" Cr", " " + t("price.cr"))
      .replace(" Lakh", " " + t("price.lakh"));
  }

  // ---------- Element ka text badalna (icon waghera bacha ke) ----------
  function setText(el, text) {
    const textNodes = Array.from(el.childNodes).filter(function (n) {
      return n.nodeType === 3 && n.nodeValue.trim() !== "";
    });

    if (textNodes.length === 0) {
      if (el.children.length === 0) {
        el.textContent = text;
      } else {
        el.appendChild(document.createTextNode(" " + text));
      }
      return;
    }

    // Pehle text node me naya text, baaki text nodes hata do
    const first = textNodes[0];
    const lead = /^\s/.test(first.nodeValue) ? " " : "";
    first.nodeValue = lead + text;
    textNodes.slice(1).forEach(function (n) {
      n.remove();
    });
  }

  // ---------- Poore page par language lagana ----------
  function apply() {
    document.documentElement.lang = current;

    // a) data-i18n wale elements
    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      setText(el, t(el.dataset.i18n));
    });
    document.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
      el.setAttribute("placeholder", t(el.dataset.i18nPlaceholder));
    });
    document.querySelectorAll("[data-i18n-title]").forEach(function (el) {
      el.setAttribute("title", t(el.dataset.i18nTitle));
    });

    // b) Selector wale elements (navbar, footer)
    SELECTOR_MAP.forEach(function (pair) {
      document.querySelectorAll(pair[0]).forEach(function (el) {
        setText(el, t(pair[1]));
      });
    });

    // Dropdown me sahi language dikhao
    const select = document.getElementById("langSelect");
    if (select) select.value = current;

    // Baaki scripts ko batao ki language badli hai
    // (jaise cards banane wali JS apne texts dobara bana sake)
    document.dispatchEvent(new CustomEvent("languagechange", { detail: { lang: current } }));
  }

  // ---------- Language badalna ----------
  function setLang(code) {
    if (!LANGS[code]) return;
    current = code;
    try {
      localStorage.setItem(STORAGE_KEY, code);
    } catch (e) {
      // storage na chale to bhi language badal jayegi
    }
    apply();
  }

  // ---------- Navbar me dropdown banana ----------
  function buildSwitcher() {
    const right = document.querySelector(".nav-right");
    if (!right || document.getElementById("langSelect")) return;

    const select = document.createElement("select");
    select.id = "langSelect";
    select.className = "lang-select";
    select.setAttribute("aria-label", "Language");

    Object.keys(LANGS).forEach(function (code) {
      const option = document.createElement("option");
      option.value = code;
      option.textContent = LANGS[code];
      select.appendChild(option);
    });

    select.addEventListener("change", function () {
      setLang(select.value);
    });

    right.insertBefore(select, right.firstChild);
  }

  // Dusre scripts ke liye: window.i18n.t("key")
  window.i18n = {
    t: t,
    tOr: tOr,
    price: price,
    setLang: setLang,
    get lang() {
      return current;
    },
  };

  buildSwitcher();
  apply();
})();