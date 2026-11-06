// =========================================================
// properties.js - Properties page ka JavaScript
// Kaam: database (PHP API) se properties lana, cards banana,
//       filters aur wishlist (heart).
// Card par "See details" dabane se property ka poora page khulta hai.
// =========================================================

// PHP API ka address
const API_URL = "../api/properties.php";

// ---------- Page ke elements pakdo ----------
const grid = document.getElementById("propGrid");
const fType = document.getElementById("fType");
const fArea = document.getElementById("fArea");
const fBhk = document.getElementById("fBhk");
const fBudget = document.getElementById("fBudget");
const savedBtn = document.getElementById("savedBtn");
const savedCount = document.getElementById("savedCount");
const resetBtn = document.getElementById("resetBtn");
const resultCount = document.getElementById("resultCount");
const noResults = document.getElementById("noResults");

let showSavedOnly = false;
let fetched = []; // API se aayi hui properties yahan rakhte hain

// ---------- Saved (wishlist) browser me yaad rakhna ----------
let saved = [];
try {
  saved = JSON.parse(localStorage.getItem("savedProps")) || [];
} catch (e) {
  saved = [];
}

function saveToStorage() {
  try {
    localStorage.setItem("savedProps", JSON.stringify(saved));
  } catch (e) {
    // storage na chale to bhi site kaam karti rahe
  }
}

function updateSavedCount() {
  savedCount.textContent = saved.length;
}

// ---------- fetch ko timeout dena ----------
// Server (ya MySQL) atka ho to page hamesha "loading..." par na rukhe,
// 10 second baad saaf message dikhe.
async function fetchWithTimeout(url, ms) {
  const controller = new AbortController();
  const timer = setTimeout(function () {
    controller.abort();
  }, ms);
  try {
    return await fetch(url, { signal: controller.signal });
  } finally {
    clearTimeout(timer);
  }
}

// ---------- Text ko safe banana ----------
// Database ka text seedha HTML me daalna khatarnaak ho sakta hai,
// isliye <, >, & jaise symbols ko badal dete hain.
function esc(text) {
  return String(text == null ? "" : text).replace(/[&<>"']/g, function (ch) {
    return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
  });
}

// ---------- Ek property ka card banana ----------
function cardHTML(p) {
  const isSaved = saved.indexOf(String(p.id)) !== -1;

  // Detail page ka link: property.html?id=5
  const link = "property.html?id=" + esc(p.id);

  // Price me "/ month" ho to chhota dikhao
  const price = esc(p.price_label).replace(" / month", " <small>/ month</small>");

  // Commercial me BHK nahi hota, Office dikhao
  const specs =
    p.type === "commercial"
      ? '<span><i class="bi bi-building"></i>Office</span>' +
        '<span><i class="bi bi-droplet"></i>' + esc(p.bathrooms) + " Washrooms</span>"
      : '<span><i class="bi bi-door-open"></i>' + esc(p.bhk) + " BHK</span>" +
        '<span><i class="bi bi-droplet"></i>' + esc(p.bathrooms) + " Bath</span>";

  const badge = p.badge ? '<span class="badge">' + esc(p.badge) + "</span>" : "";

  return (
    '<article class="prop-card reveal show" data-id="' + esc(p.id) + '">' +
      '<div class="prop-img">' +
        badge +
        '<button class="heart" aria-label="Save"><i class="bi ' +
          (isSaved ? "bi-heart-fill" : "bi-heart") + '"></i></button>' +
        '<a href="' + link + '">' +
          '<img src="images/' + esc(p.image) + '" alt="' + esc(p.title) + '" />' +
        "</a>" +
      "</div>" +
      '<div class="prop-body">' +
        '<div class="price">' + price + "</div>" +
        '<h3><a href="' + link + '">' + esc(p.title) + "</a></h3>" +
        '<p class="loc"><i class="bi bi-geo-alt"></i> ' + esc(p.address) + "</p>" +
        '<div class="specs">' +
          specs +
          '<span><i class="bi bi-aspect-ratio"></i>' + esc(p.sqft) + " sq.ft</span>" +
        "</div>" +
        '<a href="' + link + '" class="btn btn-teal details-btn">See details</a>' +
      "</div>" +
    "</article>"
  );
}

// ---------- Cards screen par dikhana ----------
function renderCurrent() {
  let list = fetched;

  // "Saved" button dabा ho to sirf saved properties
  if (showSavedOnly) {
    list = list.filter(function (p) {
      return saved.indexOf(String(p.id)) !== -1;
    });
  }

  grid.innerHTML = list.map(cardHTML).join("");
  resultCount.textContent = list.length;
  noResults.style.display = list.length === 0 ? "block" : "none";
}

// ---------- Database se properties mangwana ----------
async function loadProperties() {
  // Filters ko URL ke parameters me badlo
  const params = new URLSearchParams();
  if (fType.value !== "all") params.set("type", fType.value);
  if (fArea.value !== "all") params.set("area", fArea.value);
  if (fBhk.value !== "all") params.set("bhk", fBhk.value);
  if (fBudget.value !== "all") params.set("budget", fBudget.value);

  noResults.style.display = "none";
  grid.innerHTML =
    '<p style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px 0">' +
    "Properties load ho rahi hain...</p>";

  try {
    const response = await fetchWithTimeout(API_URL + "?" + params.toString(), 10000);
    const data = await response.json();

    if (!data.success) throw new Error(data.error || "API error");

    fetched = data.properties;
    renderCurrent();
  } catch (err) {
    fetched = [];
    resultCount.textContent = 0;
    const slow = err && err.name === "AbortError";
    grid.innerHTML =
      '<p style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px 0">' +
      (slow
        ? "Server bahut der se jawab nahi de raha (MySQL atka hua ho sakta hai). " +
          "XAMPP me MySQL ko Stop karke dobara Start karo, fir page refresh karo."
        : "Properties load nahi ho payi. Check karo ki XAMPP me Apache aur MySQL chal rahe hain " +
          "aur site localhost se khuli hai.") +
      "</p>";
  }
}

// ---------- Home page ke search se aaye filters padhna ----------
// Jaise: properties.html?type=buy&area=Andheri&bhk=2
function setIfValid(select, value) {
  const exists = Array.from(select.options).some(function (opt) {
    return opt.value === value;
  });
  if (value && exists) select.value = value;
}

const urlParams = new URLSearchParams(window.location.search);
setIfValid(fType, urlParams.get("type"));
setIfValid(fArea, urlParams.get("area"));
setIfValid(fBhk, urlParams.get("bhk"));
setIfValid(fBudget, urlParams.get("budget"));

// ---------- Filters badalte hi dobara database se mangwao ----------
[fType, fArea, fBhk, fBudget].forEach(function (el) {
  el.addEventListener("change", loadProperties);
});

resetBtn.addEventListener("click", function () {
  fType.value = "all";
  fArea.value = "all";
  fBhk.value = "all";
  fBudget.value = "all";
  showSavedOnly = false;
  savedBtn.classList.remove("active");
  loadProperties();
});

// "Saved" button: sirf pasand ki hui properties (naya data mangwane ki zaroorat nahi)
savedBtn.addEventListener("click", function () {
  showSavedOnly = !showSavedOnly;
  savedBtn.classList.toggle("active", showSavedOnly);
  renderCurrent();
});

// ---------- Heart (wishlist) button ----------
// Cards baad me ban rahe hain, isliye click grid par sunte hain
// (isko "event delegation" kehte hain).
grid.addEventListener("click", function (e) {
  const heart = e.target.closest(".heart");
  if (heart) {
    toggleSaved(heart);
  }
});

function toggleSaved(heart) {
  const id = heart.closest(".prop-card").dataset.id;
  const index = saved.indexOf(id);

  if (index === -1) {
    saved.push(id); // save karo
  } else {
    saved.splice(index, 1); // hata do
  }
  saveToStorage();
  updateSavedCount();

  // Saved-only mode me ho to list dobara banao, warna sirf heart badlo
  if (showSavedOnly) {
    renderCurrent();
    return;
  }
  heart.querySelector("i").className =
    saved.indexOf(id) !== -1 ? "bi bi-heart-fill" : "bi bi-heart";

  // pop animation
  heart.classList.remove("saved");
  void heart.offsetWidth;
  heart.classList.add("saved");
}

// ---------- Page khulte hi ----------
updateSavedCount();
loadProperties();