// =========================================================
// property.js - Property detail page ka JavaScript
// Kaam: URL se property ka id padhna (property.html?id=5),
//       database se uski details lana, page banana,
//       enquiry form bhejna, share karna, photo zoom karna.
// =========================================================

// Apna number yahan badlo
const WHATSAPP_NUMBER = "918104591780"; // 91 + 10 digit number
const PHONE_LINK = "+918104591780";

const API_URL = "../api/property.php";
const ENQUIRY_URL = "../api/enquiry.php";

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

// Text ko safe banana (database ka text seedha HTML me nahi daalte)
function esc(text) {
  return String(text == null ? "" : text).replace(/[&<>"']/g, function (ch) {
    return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
  });
}

// ---------- Page ke elements aur state ----------
const root = document.getElementById("detail");
const bannerTitle = document.getElementById("detailTitle");

// URL se id padho: property.html?id=5
const propId = parseInt(new URLSearchParams(window.location.search).get("id"), 10);

let data = null; // API se aaya hua {property, similar}

// Price me "/ month" chhota dikhana
function priceHtml(label) {
  return esc(label).replace(" / month", " <small>/ month</small>");
}

// Type ka naam: buy -> For Sale
function typeLabel(type) {
  if (type === "rent") return "For Rent";
  if (type === "commercial") return "Commercial";
  return "For Sale";
}

// Date likhna: 24 October 2026
function fmtDate(text) {
  const d = new Date(String(text).replace(" ", "T"));
  if (isNaN(d)) return "";
  return d.toLocaleDateString("en-IN", { day: "numeric", month: "long", year: "numeric" });
}

// ---------- "Similar Properties" ka ek card ----------
function cardHTML(s) {
  const link = "property.html?id=" + esc(s.id);
  const badge = s.badge ? '<span class="badge">' + esc(s.badge) + "</span>" : "";

  const specs =
    s.type === "commercial"
      ? '<span><i class="bi bi-building"></i>Office</span>' +
        '<span><i class="bi bi-droplet"></i>' + esc(s.bathrooms) + " Washrooms</span>"
      : '<span><i class="bi bi-door-open"></i>' + esc(s.bhk) + " BHK</span>" +
        '<span><i class="bi bi-droplet"></i>' + esc(s.bathrooms) + " Bath</span>";

  return (
    '<article class="prop-card reveal show">' +
      '<div class="prop-img">' + badge +
        '<a href="' + link + '"><img src="images/' + esc(s.image) + '" alt="' + esc(s.title) + '" /></a>' +
      "</div>" +
      '<div class="prop-body">' +
        '<div class="price">' + priceHtml(s.price_label) + "</div>" +
        '<h3><a href="' + link + '">' + esc(s.title) + "</a></h3>" +
        '<p class="loc"><i class="bi bi-geo-alt"></i> ' + esc(s.address) + "</p>" +
        '<div class="specs">' + specs +
          '<span><i class="bi bi-aspect-ratio"></i>' + esc(s.sqft) + " sq.ft</span>" +
        "</div>" +
        '<a href="' + link + '" class="btn btn-teal details-btn">See details</a>' +
      "</div>" +
    "</article>"
  );
}

// ---------- Loading / Not found / Error message ----------
function showMessage(title, text, withButton) {
  bannerTitle.textContent = "Property Details";
  root.innerHTML =
    '<div class="detail-msg"><h2>' + esc(title) + "</h2>" +
    (text ? "<p>" + esc(text) + "</p>" : "") +
    (withButton ? '<a href="properties.html" class="btn btn-coral">Back to all properties</a>' : "") +
    "</div>";
}

// ---------- Poora page banana ----------
function render() {
  const p = data.property;
  const isCommercial = p.type === "commercial";

  document.title = p.title + " | Aashiyana Realty";
  bannerTitle.textContent = p.title;

  // ----- Key details (chhote boxes) -----
  const facts = [];
  facts.push(["bi-tag", "Type", typeLabel(p.type)]);
  if (!isCommercial) {
    facts.push(["bi-door-open", "BHK", p.bhk + " BHK"]);
  }
  facts.push(["bi-droplet", isCommercial ? "Washrooms" : "Bathrooms", p.bathrooms]);
  facts.push(["bi-aspect-ratio", "Area", p.sqft + " sq.ft"]);
  facts.push(["bi-geo-alt", "Location", p.area]);
  if (p.type === "buy" && Number(p.sqft) > 0) {
    const perSqft = Math.round(Number(p.price) / Number(p.sqft)).toLocaleString("en-IN");
    facts.push(["bi-cash-coin", "Price per sq.ft", "\u20B9" + perSqft]);
  }
  const listed = fmtDate(p.created_at);
  if (listed) facts.push(["bi-calendar3", "Listed on", listed]);

  const factsHtml = facts
    .map(function (f) {
      return (
        '<div class="fact"><i class="bi ' + f[0] + '"></i>' +
        "<div><span>" + esc(f[1]) + "</span><strong>" + esc(f[2]) + "</strong></div></div>"
      );
    })
    .join("");

  // ----- Description (nayi line ko line break banana) -----
  const description = p.description
    ? esc(p.description).replace(/\n/g, "<br>")
    : "More details are available on request. Please send an enquiry.";

  const badge = p.badge ? '<span class="badge">' + esc(p.badge) + "</span>" : "";

  // ----- Map (address se Google Maps ka embed) -----
  const mapSrc =
    "https://www.google.com/maps?q=" +
    encodeURIComponent(p.address + ", Maharashtra, India") +
    "&output=embed";

  // ----- WhatsApp link (property ki details ke saath) -----
  const waText =
    "Hello Aashiyana Realty, I am interested in this property:\n" +
    p.title + "\n" + p.price_label + "\n" + window.location.href;
  const waLink = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(waText);

  // ----- Milti-julti properties -----
  const similarHtml =
    data.similar.length > 0
      ? '<section class="similar"><h2 class="detail-h">Similar Properties</h2>' +
        '<div class="props-grid">' + data.similar.map(cardHTML).join("") + "</div></section>"
      : "";

  // ----- EMI calculator (sirf "For Sale" properties ke liye) -----
  const emiHtml =
    p.type === "buy" && window.EMI ? window.EMI.html({ price: Number(p.price) }) : "";

  // ----- Sab kuch jodkar page banao -----
  root.innerHTML =
    '<div class="detail-grid">' +

      // ===== Left: photo, details, description, map =====
      '<div class="detail-main">' +
        '<div class="gallery">' + badge +
          '<img id="mainImg" src="images/' + esc(p.image) + '" alt="' + esc(p.title) + '" />' +
          '<span class="zoom-hint"><i class="bi bi-arrows-fullscreen"></i></span>' +
        "</div>" +

        '<h2 class="detail-name">' + esc(p.title) + "</h2>" +
        '<p class="detail-loc"><i class="bi bi-geo-alt-fill"></i> ' + esc(p.address) + "</p>" +

        '<h3 class="detail-h">Key Details</h3>' +
        '<div class="facts">' + factsHtml + "</div>" +

        '<h3 class="detail-h">About this property</h3>' +
        '<p class="detail-desc">' + description + "</p>" +

        emiHtml +

        '<h3 class="detail-h">Location on map</h3>' +
        '<div class="map-box"><iframe src="' + mapSrc + '" loading="lazy" ' +
          'referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe></div>' +
      "</div>" +

      // ===== Right: price + enquiry form =====
      '<aside class="detail-side">' +
        '<div class="side-card">' +
          '<div class="big-price">' + priceHtml(p.price_label) + "</div>" +
          '<div class="side-type">' + esc(typeLabel(p.type)) + "</div>" +

          '<h3 class="side-title">Interested in this property?</h3>' +
          '<form id="enqForm" novalidate>' +
            '<input type="text" id="enqName" maxlength="100" placeholder="Your Name" />' +
            '<input type="tel" id="enqPhone" maxlength="10" inputmode="numeric" placeholder="Mobile Number (10 digits)" />' +
            '<textarea id="enqMsg" rows="3" placeholder="Message (optional)"></textarea>' +
            '<button type="submit" class="btn btn-coral" id="enqBtn">Send Enquiry</button>' +
            '<div class="enq-status" id="enqStatus"></div>' +
          "</form>" +

          '<div class="side-actions">' +
            '<a class="act" href="tel:' + PHONE_LINK + '"><i class="bi bi-telephone"></i> Call Now</a>' +
            '<a class="act wa" href="' + esc(waLink) + '" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>' +
            '<button type="button" class="act" data-act="share"><i class="bi bi-share"></i> Share</button>' +
          "</div>" +
        "</div>" +
      "</aside>" +

    "</div>" + similarHtml;

  // EMI calculator ke sliders chalu karo
  if (window.EMI) window.EMI.init(root.querySelector(".emi"));
}

// ---------- Chhota message (jaise "Link copied!") ----------
function toast(text) {
  const el = document.createElement("div");
  el.className = "toast";
  el.textContent = text;
  document.body.appendChild(el);
  setTimeout(function () {
    el.remove();
  }, 2200);
}

// ---------- Share karna ----------
async function share() {
  const url = window.location.href;
  const title = data.property.title;

  // Phone par share ka apna menu khulega
  if (navigator.share) {
    try {
      await navigator.share({ title: title, url: url });
    } catch (e) {
      // user ne cancel kar diya
    }
    return;
  }

  // Computer par link copy ho jayega
  try {
    await navigator.clipboard.writeText(url);
    toast("Link copied!");
  } catch (e) {
    toast(url);
  }
}

// ---------- Photo zoom (lightbox) ----------
function openLightbox(src) {
  const box = document.createElement("div");
  box.className = "lightbox";
  box.innerHTML = '<img src="' + esc(src) + '" alt="" />';

  function close() {
    box.remove();
    document.removeEventListener("keydown", onKey);
  }
  function onKey(e) {
    if (e.key === "Escape") close();
  }
  box.addEventListener("click", close);
  document.addEventListener("keydown", onKey);
  document.body.appendChild(box);
}

// ---------- Enquiry form bhejna ----------
async function sendEnquiry() {
  const p = data.property;
  const status = document.getElementById("enqStatus");
  const btn = document.getElementById("enqBtn");

  const name = document.getElementById("enqName").value.trim();
  const phone = document.getElementById("enqPhone").value.trim();
  const message = document.getElementById("enqMsg").value.trim();

  function showStatus(text, isError) {
    status.textContent = text;
    status.className = "enq-status " + (isError ? "error" : "ok");
  }

  // Pehle browser me check
  if (name === "") return showStatus("Please enter your name.", true);
  if (!/^[0-9]{10}$/.test(phone)) return showStatus("Please enter a 10 digit mobile number.", true);

  const requirement =
    p.type === "rent" ? "Rent a Flat" :
    p.type === "commercial" ? "Commercial Space" : "Buy a Flat";

  const body = {
    name: name,
    phone: phone,
    requirement: requirement,
    budget: p.price_label,
    area: p.area,
    message: (message ? message + "\n\n" : "") + "Property: " + p.title + " (ID " + p.id + ")",
    property_id: p.id,
  };

  btn.disabled = true;
  btn.textContent = "Sending...";
  status.textContent = "";

  try {
    const response = await fetch(ENQUIRY_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const result = await response.json();

    if (result.success) {
      showStatus("Thank you! Your enquiry is saved. We will call you soon.", false);
      document.getElementById("enqName").value = "";
      document.getElementById("enqPhone").value = "";
      document.getElementById("enqMsg").value = "";
    } else {
      showStatus("Could not send the enquiry. Please try again.", true);
    }
  } catch (err) {
    showStatus("Could not send the enquiry. Please try again.", true);
  }

  btn.disabled = false;
  btn.textContent = "Send Enquiry";
}

// ---------- Clicks aur form ----------
// Page baad me banta hai, isliye events root par sunte hain (event delegation)
root.addEventListener("submit", function (e) {
  if (e.target.id === "enqForm") {
    e.preventDefault();
    sendEnquiry();
  }
});

root.addEventListener("click", function (e) {
  if (e.target.closest("#mainImg") || e.target.closest(".zoom-hint")) {
    openLightbox(document.getElementById("mainImg").src);
    return;
  }
  if (e.target.closest("[data-act='share']")) {
    share();
  }
});

// ---------- Database se property mangwana ----------
async function load() {
  if (!propId || propId < 1) {
    showMessage("Property not found", "This property may have been removed or the link is wrong.", true);
    return;
  }

  showMessage("Loading property...", "", false);

  try {
    const response = await fetchWithTimeout(API_URL + "?id=" + propId, 10000);
    const json = await response.json();

    if (json.success) {
      data = json;
      render();
    } else if (response.status === 404) {
      showMessage("Property not found", "This property may have been removed or the link is wrong.", true);
    } else {
      showMessage("Could not load the property", "Please check that XAMPP (Apache and MySQL) is running.", true);
    }
  } catch (err) {
    const slow = err && err.name === "AbortError";
    showMessage(
      "Could not load the property",
      slow
        ? "The server is taking too long to respond. Restart MySQL in XAMPP and refresh this page."
        : "Please check that XAMPP (Apache and MySQL) is running.",
      true
    );
  }
}

load();