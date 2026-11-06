// =========================================================
// Aashiyana Realty - Main JavaScript
// Har feature ke upar comment hai ki wo kya karta hai.
// =========================================================

// ---------- 1. Navbar: scroll karne par glass effect ----------
const nav = document.getElementById("nav");
function onScroll() {
  if (window.scrollY > 40) {
    nav.classList.add("scrolled");
  } else {
    nav.classList.remove("scrolled");
  }
}
window.addEventListener("scroll", onScroll);
onScroll();

// ---------- 2. Mobile menu (hamburger) ----------
const burger = document.getElementById("burger");
const menu = document.getElementById("menu");
burger.addEventListener("click", function () {
  menu.classList.toggle("open");
});
// Menu ke kisi link par click karte hi menu band ho jaye
menu.querySelectorAll("a").forEach(function (link) {
  link.addEventListener("click", function () {
    menu.classList.remove("open");
  });
});

// ---------- 3. Dark / Light mode ----------
const themeBtn = document.getElementById("themeBtn");
const themeIcon = themeBtn.querySelector("i");

function setTheme(mode) {
  if (mode === "dark") {
    document.body.classList.add("dark");
    themeIcon.className = "bi bi-sun";
  } else {
    document.body.classList.remove("dark");
    themeIcon.className = "bi bi-moon-stars";
  }
  // Browser me yaad rakho, taaki page badalne par bhi wahi mode rahe
  localStorage.setItem("theme", mode);
}
setTheme(localStorage.getItem("theme") || "light");

themeBtn.addEventListener("click", function () {
  setTheme(document.body.classList.contains("dark") ? "light" : "dark");
});

// ---------- 4. Typing effect (hero heading) ----------
const typedEl = document.getElementById("typed");
const words = ["Andheri", "Bandra", "Powai", "Thane", "Navi Mumbai", "Worli"];
let w = 0;
let c = 0;
let deleting = false;

function typeLoop() {
  const word = words[w];
  if (!typedEl) return;
  typedEl.textContent = word.substring(0, c);
  

  if (!deleting && c < word.length) {
    c++;
    setTimeout(typeLoop, 110);
  } else if (!deleting) {
    deleting = true;
    setTimeout(typeLoop, 1400); // poora likh gaya, thoda ruko
  } else if (c > 0) {
    c--;
    setTimeout(typeLoop, 60);
  } else {
    deleting = false;
    w = (w + 1) % words.length;
    setTimeout(typeLoop, 300);
  }
}
typeLoop();

// ---------- 5. Search tabs (Buy / Rent / Commercial) ----------
let searchType = "buy";
const tabs = document.querySelectorAll(".tab");
tabs.forEach(function (tab) {
  tab.addEventListener("click", function () {
    tabs.forEach(function (t) {
      t.classList.remove("active");
    });
    tab.classList.add("active");
    searchType = tab.dataset.type;
  });
});

// Search button: properties page par filters ke saath bhejo
const searchBtn = document.getElementById("searchBtn");
if (searchBtn) searchBtn.addEventListener("click", function () {
  const area = document.getElementById("sArea").value;
  const bhk = document.getElementById("sBhk").value;
  const budget = document.getElementById("sBudget").value;

  const params = new URLSearchParams();
  params.set("type", searchType);
  if (area !== "all") params.set("area", area);
  if (bhk !== "all") params.set("bhk", bhk);
  if (budget !== "all") params.set("budget", budget);

  window.location.href = "properties.html?" + params.toString();
});

// ---------- 6. Scroll reveal (screen par aate hi fade-in) ----------
const revealObserver = new IntersectionObserver(
  function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add("show");
        revealObserver.unobserve(entry.target); // ek hi baar chale
      }
    });
  },
  { threshold: 0.15 }
);
document.querySelectorAll(".reveal").forEach(function (el) {
  revealObserver.observe(el);
});

// ---------- 7. Number counter (0 se upar chadhna) ----------
function animateCounter(el) {
  const target = parseInt(el.dataset.count, 10);
  const duration = 1800; // milliseconds
  const start = performance.now();

  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    el.textContent = Math.floor(progress * target);
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

const counterObserver = new IntersectionObserver(
  function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        counterObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.6 }
);
document.querySelectorAll("[data-count]").forEach(function (el) {
  counterObserver.observe(el);
});

// ---------- 8. Footer me current year ----------
const yearEl = document.getElementById("year");
if (yearEl) yearEl.textContent = new Date().getFullYear();
// ---------- 9. Cursor follower (mouse ke peeche chalta ghar ka icon) ----------
// Asli arrow cursor waisa hi rehta hai, uske peeche ek ghar ka icon
// halki deri se chalta hai. Link ya button par aate hi icon bada ho jata hai.
(function () {
  // Touch screen (phone/tablet) par mouse hota hi nahi, aur jinhone
  // "reduce motion" on rakha hai unke liye ye effect band rehta hai.
  const noHover = window.matchMedia("(hover: none), (pointer: coarse)").matches;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (noHover || reduceMotion) return;
 
  // Icon banao aur page me jod do
  // (icon badalna ho to neeche "bi-house-door-fill" ki jagah dusra naam likho,
  //  jaise bi-house-heart-fill, bi-building, bi-key-fill)
  const el = document.createElement("div");
  el.className = "cursor-follow";
  el.innerHTML = '<i class="bi bi-house-door-fill"></i>';
  document.body.appendChild(el);
 
  const HALF = 17;     // icon ke box ka aadha size (CSS me 34px hai)
  const SPEED = 0.15;  // kam = zyada deri se peeche chalega, zyada = mouse ke nazdeek
 
  let mouseX = 0, mouseY = 0;  // mouse abhi kahan hai
  let x = 0, y = 0;            // icon abhi kahan hai
  let started = false;
 
  // Mouse hilne par uski jagah yaad rakho
  document.addEventListener("mousemove", function (e) {
    mouseX = e.clientX;
    mouseY = e.clientY;
    if (!started) {
      x = mouseX;   // pehli baar: seedha mouse ke paas dikhao
      y = mouseY;
      started = true;
    }
    el.classList.add("visible");
  });
 
  // Mouse browser window se bahar gaya to icon chhupa do
  document.addEventListener("mouseout", function (e) {
    if (!e.relatedTarget) el.classList.remove("visible");
  });
 
  // Click karte waqt icon thoda chhota
  document.addEventListener("mousedown", function () {
    el.classList.add("is-down");
  });
  document.addEventListener("mouseup", function () {
    el.classList.remove("is-down");
  });
 
  // Link, button, card jaisi cheezon par aate hi icon bada aur rang badle
  document.addEventListener("mouseover", function (e) {
    const onClickable = e.target.closest(
      "a, button, select, input, textarea, summary, .prop-card, .area-card"
    );
    el.classList.toggle("is-active", !!onClickable);
  });
 
  // Har frame par icon ko mouse ki taraf thoda sa khiskao (smooth peecha karna)
  function animate() {
    x += (mouseX - x) * SPEED;
    y += (mouseY - y) * SPEED;
    el.style.transform = "translate3d(" + (x - HALF) + "px," + (y - HALF) + "px,0)";
    requestAnimationFrame(animate);
  }
  animate();
})();
// ---------- 10. Login popup + navbar ka Login button ----------
// Nayi visitor ke aane ke 3.5 second baad ek popup khulta hai jisme
// naam, mobile number waghera maange jate hain. Details database me save hoti hain.
// Login ho jaye to navbar me "Hi, Naam" dikhta hai aur popup dobara nahi aata.
(function () {
  const SHOW_DELAY = 3500;   // popup kitni der baad aaye (3500 = 3.5 second)
  const SNOOZE_DAYS = 2;     // "Maybe later" dabane par itne din tak popup nahi aayega
  const SNOOZE_KEY = "lpSnoozeUntil";
  const API = "../api/";

  let user = null;       // abhi login hai to uski details
  let overlay = null;    // popup ka dabba
  let navBtn = null;     // navbar ka Login button
  let navWrap = null;

  // ---------- Chhote helpers ----------
  function esc(text) {
    return String(text == null ? "" : text).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }

  // "Maybe later" ke baad kuch din popup nahi dikhana (browser me yaad rakhte hain)
  function isSnoozed() {
    try {
      return Number(localStorage.getItem(SNOOZE_KEY)) > Date.now();
    } catch (e) {
      return false;
    }
  }
  function snooze() {
    try {
      localStorage.setItem(SNOOZE_KEY, String(Date.now() + SNOOZE_DAYS * 24 * 60 * 60 * 1000));
    } catch (e) {}
  }
  function clearSnooze() {
    try {
      localStorage.removeItem(SNOOZE_KEY);
    } catch (e) {}
  }

  // ---------- Navbar ka button ----------
  function buildNav() {
    const right = document.querySelector(".nav-right");
    if (!right) return;

    navWrap = document.createElement("div");
    navWrap.className = "nav-user";

    navBtn = document.createElement("button");
    navBtn.type = "button";
    navBtn.className = "nav-login";
    navBtn.innerHTML = '<i class="bi bi-person-circle"></i> <span>Login</span>';
    navWrap.appendChild(navBtn);

    right.insertBefore(navWrap, right.firstChild); // theme button se pehle

    navBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      if (user) {
        toggleMenu();
      } else {
        openPopup();
      }
    });
    document.addEventListener("click", closeMenu); // bahar click karne par menu band
  }

  function updateNav() {
    if (!navBtn) return;
    navBtn.querySelector("span").textContent = user ? "Hi, " + user.name.split(" ")[0] : "Login";
    closeMenu();
  }

  // Login ke baad button dabane par chhota menu (naam, number, Logout)
  function toggleMenu() {
    if (navWrap.querySelector(".nav-menu")) {
      closeMenu();
      return;
    }
    const menu = document.createElement("div");
    menu.className = "nav-menu";
    menu.innerHTML =
      '<div class="nav-menu-info"><strong>' + esc(user.name) + "</strong>" +
      "<span>+91 " + esc(user.phone) + "</span></div>" +
      '<button type="button" class="nav-logout"><i class="bi bi-box-arrow-right"></i> Logout</button>';
    menu.addEventListener("click", function (e) {
      e.stopPropagation();
    });
    menu.querySelector(".nav-logout").addEventListener("click", logout);
    navWrap.appendChild(menu);
  }

  function closeMenu() {
    if (!navWrap) return;
    const menu = navWrap.querySelector(".nav-menu");
    if (menu) menu.remove();
  }

  async function logout() {
    try {
      await fetch(API + "logout.php", { method: "POST" });
    } catch (e) {}
    user = null;
    clearSnooze();
    updateNav();
  }

  // ---------- Popup ----------
  function buildPopup() {
    overlay = document.createElement("div");
    overlay.className = "lp-overlay";
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-labelledby", "lpTitle");
    overlay.innerHTML =
      '<div class="lp-box">' +
        '<button type="button" class="lp-close" aria-label="Close">&times;</button>' +
        '<div class="lp-body" id="lpBody">' +
          '<div class="lp-head">' +
            '<div class="lp-icon"><i class="bi bi-house-heart-fill"></i></div>' +
            '<h3 id="lpTitle">Welcome to Aashiyana!</h3>' +
            "<p>Login to save your favourite homes and get the best deals in Mumbai.</p>" +
          "</div>" +
          '<form id="lpForm" novalidate>' +
            // Spam jaal: insaan ko ye dikhta nahi
            '<input type="text" name="website" class="lp-hp" tabindex="-1" autocomplete="off" />' +
            '<input type="text" id="lpName" placeholder="Your name" maxlength="100" autocomplete="name" />' +
            '<div class="lp-phone"><span>+91</span>' +
              '<input type="tel" id="lpPhone" placeholder="Mobile number" maxlength="10" inputmode="numeric" autocomplete="tel-national" />' +
            "</div>" +
            '<input type="email" id="lpEmail" placeholder="Email (optional)" maxlength="120" autocomplete="email" />' +
            '<select id="lpLooking">' +
              '<option value="">I am looking to... (optional)</option>' +
              '<option value="buy">Buy a property</option>' +
              '<option value="rent">Rent a property</option>' +
              '<option value="sell">Sell / rent out my property</option>' +
              '<option value="browsing">Just browsing</option>' +
            "</select>" +
            '<label class="lp-consent"><input type="checkbox" id="lpConsent" /> ' +
              "I agree that Aashiyana Realty can contact me.</label>" +
            '<button type="submit" class="btn btn-coral lp-submit" id="lpSubmit">Login</button>' +
            '<div class="lp-status" id="lpStatus"></div>' +
          "</form>" +
          '<button type="button" class="lp-later">Maybe later</button>' +
        "</div>" +
      "</div>";
    document.body.appendChild(overlay);

    // Band karne ke raaste: X, "Maybe later", bahar click
    overlay.querySelector(".lp-close").addEventListener("click", function () {
      closePopup(true);
    });
    overlay.querySelector(".lp-later").addEventListener("click", function () {
      closePopup(true);
    });
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) closePopup(true);
    });

    overlay.querySelector("#lpForm").addEventListener("submit", submitForm);
  }

  function onKey(e) {
    if (e.key === "Escape") closePopup(true);
  }

  function openPopup() {
    if (overlay) return; // pehle se khula hai
    buildPopup();
    document.body.style.overflow = "hidden"; // peeche ka page scroll na ho
    document.addEventListener("keydown", onKey);
    // Thoda ruk ke "show" lagao taaki animation chale
    requestAnimationFrame(function () {
      overlay.classList.add("show");
      const first = overlay.querySelector("#lpName");
      if (first) first.focus();
    });
  }

  // snoozeIt = true ho to kuch din popup dobara nahi aayega
  function closePopup(snoozeIt) {
    if (!overlay) return;
    if (snoozeIt) snooze();
    const box = overlay;
    overlay = null;
    box.classList.remove("show");
    document.body.style.overflow = "";
    document.removeEventListener("keydown", onKey);
    setTimeout(function () {
      box.remove();
    }, 250);
  }

  // ---------- Form bhejna ----------
  async function submitForm(e) {
    e.preventDefault();

    const status = overlay.querySelector("#lpStatus");
    const btn = overlay.querySelector("#lpSubmit");

    function showError(text) {
      status.textContent = text;
    }

    const name = overlay.querySelector("#lpName").value.trim();
    const phone = overlay.querySelector("#lpPhone").value.trim();
    const email = overlay.querySelector("#lpEmail").value.trim();
    const looking = overlay.querySelector("#lpLooking").value;
    const consent = overlay.querySelector("#lpConsent").checked;
    const honeypot = overlay.querySelector("input[name='website']").value;

    // Pehle browser me check
    if (name.length < 2) return showError("Please enter your name.");
    if (!/^[6-9][0-9]{9}$/.test(phone)) return showError("Please enter a valid 10 digit mobile number.");
    if (email !== "" && !/^\S+@\S+\.\S+$/.test(email)) return showError("Email address is not valid.");
    if (!consent) return showError("Please agree to be contacted by us.");

    btn.disabled = true;
    btn.textContent = "Please wait...";
    status.textContent = "";

    try {
      const response = await fetch(API + "user_login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: name,
          phone: phone,
          email: email,
          looking_for: looking,
          consent: consent,
          website: honeypot,
          source: window.location.pathname.split("/").pop() || "index.html",
        }),
      });
      const result = await response.json();

      if (result.success && result.user) {
        user = result.user;
        clearSnooze();
        updateNav();
        showWelcome();
        return;
      }
      showError((result.errors && result.errors[0]) || "Something went wrong. Please try again.");
    } catch (err) {
      showError("Could not connect to the server. Please try again.");
    }

    btn.disabled = false;
    btn.textContent = "Login";
  }

  // Login ho gaya: "Welcome, Naam!" dikhao aur thodi der me popup band
  function showWelcome() {
    const body = overlay.querySelector("#lpBody");
    body.innerHTML =
      '<div class="lp-welcome">' +
        '<i class="bi bi-check-circle-fill"></i>' +
        "<h3>Welcome, " + esc(user.name.split(" ")[0]) + "!</h3>" +
        "<p>You are logged in. Happy home hunting!</p>" +
      "</div>";
    setTimeout(function () {
      closePopup(false);
    }, 1800);
  }

  // ---------- Page khulte hi ----------
  buildNav();

  // Pehle server se pucho: ye visitor pehle se login hai ya nahi?
  fetch(API + "me.php")
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      if (data.logged_in) {
        user = data.user;
        updateNav();
        return;
      }
      // Login nahi hai (aur popup snooze me bhi nahi) to 3.5 second baad popup kholo
      if (data.success && !isSnoozed()) {
        setTimeout(function () {
          if (!user) openPopup();
        }, SHOW_DELAY);
      }
    })
    .catch(function () {
      // Server band ho to popup mat dikhao (details save hi nahi hongi)
    });
})();
 