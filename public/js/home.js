// =========================================================
// home.js - Home page ka JavaScript
// Kaam: Featured properties database se laana, cards banana,
//       aur language badalne par cards dobara banana.
// (Sirf wahi properties jinka is_featured = 1 hai)
// =========================================================

const grid = document.getElementById("featuredGrid");
let featured = []; // API se aayi hui properties

// ---------- Translation ke chhote helpers ----------
// (i18n.js na chale to bhi site na toote, isliye fallback rakha hai)
function t(key) {
  return window.i18n ? window.i18n.t(key) : key;
}
function tOr(key, fallback) {
  return window.i18n ? window.i18n.tOr(key, fallback) : fallback;
}
function price(label) {
  return window.i18n ? window.i18n.price(label) : label;
}

// Text ko safe banana (database ka text seedha HTML me nahi daalte)
function esc(text) {
  return String(text == null ? "" : text).replace(/[&<>"']/g, function (ch) {
    return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
  });
}

// Ek property ka card banana
function cardHTML(p) {
  const badge = p.badge
    ? '<span class="badge">' + esc(tOr("badge." + p.badge, p.badge)) + "</span>"
    : "";

  // Commercial me BHK nahi hota, Office dikhao
  const specs =
    p.type === "commercial"
      ? '<span><i class="bi bi-building"></i>' + esc(t("card.office")) + "</span>" +
        '<span><i class="bi bi-droplet"></i>' + esc(p.bathrooms) + " " + esc(t("card.washrooms")) + "</span>"
      : '<span><i class="bi bi-door-open"></i>' + esc(p.bhk) + " " + esc(t("card.bhk")) + "</span>" +
        '<span><i class="bi bi-droplet"></i>' + esc(p.bathrooms) + " " + esc(t("card.bath")) + "</span>";

  return (
    '<article class="prop-card reveal show">' +
      '<div class="prop-img">' +
        badge +
        '<a href="property.html?id=' + esc(p.id) + '">' +
          '<img src="images/' + esc(p.image) + '" alt="' + esc(p.title) + '" /></a>' +
      "</div>" +
      '<div class="prop-body">' +
        '<div class="price">' + esc(price(p.price_label)) + "</div>" +
        '<h3><a href="property.html?id=' + esc(p.id) + '">' + esc(p.title) + "</a></h3>" +
        '<p class="loc"><i class="bi bi-geo-alt"></i> ' + esc(p.address) + "</p>" +
        '<div class="specs">' +
          specs +
          '<span><i class="bi bi-aspect-ratio"></i>' + esc(p.sqft) + " " + esc(t("card.sqft")) + "</span>" +
        "</div>" +
        '<a href="property.html?id=' + esc(p.id) + '" class="btn btn-teal">' + esc(t("card.details")) + "</a>" +
      "</div>" +
    "</article>"
  );
}

// Cards screen par dikhana
function render() {
  if (!grid || featured.length === 0) return;
  grid.innerHTML = featured.map(cardHTML).join("");
}

async function loadFeatured() {
  // Agar page par featured wali jagah hi nahi hai, to kuch mat karo
  if (!grid) return;

  grid.innerHTML =
    '<p style="grid-column:1/-1;text-align:center;color:var(--muted)">' +
    esc(t("common.loading")) + "</p>";

  try {
    // ?featured=1 matlab sirf featured properties
    const response = await fetch("../api/properties.php?featured=1");
    const data = await response.json();

    if (!data.success) throw new Error(data.error || "API error");

    featured = data.properties;
    if (featured.length === 0) {
      grid.innerHTML = "";
    } else {
      render();
    }
  } catch (err) {
    grid.innerHTML =
      '<p style="grid-column:1/-1;text-align:center;color:var(--muted)">' +
      esc(t("common.loadFail")) + "</p>";
  }
}

// Language badalte hi cards dobara banao (naye labels ke saath)
document.addEventListener("languagechange", function () {
  render();
  // Loading ya error message dikh raha ho to wo bhi badal jaye
  if (grid && featured.length === 0 && grid.querySelector("p")) {
    const p = grid.querySelector("p");
    if (p.textContent.indexOf("...") !== -1) p.textContent = t("common.loading");
  }
});

loadFeatured();