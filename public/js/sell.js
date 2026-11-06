// =========================================================
// sell.js - "Sell / Rent Your Property" page ka JavaScript
// Kaam: form ko chalana (price ka preview, photo preview,
//       jaanch) aur data PHP API ko bhejna.
// =========================================================

const API_URL = "../api/sell.php";
const WHATSAPP_NUMBER = "918104591780"; // 91 + 10 digit number

const form = document.getElementById("sellForm");
const card = document.getElementById("sellCard");
const statusBox = document.getElementById("formStatus");
const submitBtn = document.getElementById("sellBtn");

const propType = document.getElementById("propType");
const priceInput = document.getElementById("price");
const priceLabel = document.getElementById("priceLabel");
const priceHint = document.getElementById("priceHint");
const photosInput = document.getElementById("photos");
const previews = document.getElementById("previews");

// ---------- Chhote helpers ----------
function listingType() {
  return form.querySelector("input[name='listing_type']:checked").value;
}

function trim2(x) {
  return x.toFixed(2).replace(/\.?0+$/, "");
}

// Price ko sundar likhna: 9500000 -> Rs 95 Lakh
function prettyPrice(n, isRent) {
  const rs = "\u20B9";
  if (n >= 10000000) return rs + trim2(n / 10000000) + " Cr" + (isRent ? " / month" : "");
  if (n >= 100000) return rs + trim2(n / 100000) + " Lakh" + (isRent ? " / month" : "");
  return rs + n.toLocaleString("en-IN") + (isRent ? " / month" : "");
}

function showStatus(text, isError) {
  statusBox.textContent = text;
  statusBox.className = "form-status " + (isError ? "error" : "ok");
}

// ---------- Price likhte hi niche sundar form dikhao ----------
function updatePrice() {
  const isRent = listingType() === "rent";
  priceLabel.textContent = isRent ? "Expected monthly rent (\u20B9)" : "Expected price (\u20B9)";

  const n = parseInt(priceInput.value.replace(/[^0-9]/g, ""), 10);
  priceHint.textContent = n ? "= " + prettyPrice(n, isRent) : "";
}

// ---------- Plot ya Office/Shop ho to BHK aur bathrooms chhupa do ----------
function updateFields() {
  const noRooms = propType.value === "plot" || propType.value === "commercial";
  document.querySelectorAll(".res-only").forEach(function (el) {
    el.style.display = noRooms ? "none" : "";
  });
}

// ---------- Photos ka preview aur jaanch ----------
photosInput.addEventListener("change", function () {
  previews.innerHTML = "";
  const files = Array.from(photosInput.files);

  if (files.length > 3) {
    showStatus("You can upload a maximum of 3 photos.", true);
    photosInput.value = "";
    return;
  }

  for (const file of files) {
    if (file.size > 2 * 1024 * 1024) {
      showStatus("Each photo must be smaller than 2 MB.", true);
      photosInput.value = "";
      previews.innerHTML = "";
      return;
    }
    const img = document.createElement("img");
    img.src = URL.createObjectURL(file); // computer par hi dikhane ke liye, upload nahi hota
    previews.appendChild(img);
  }
  statusBox.textContent = "";
});

form.querySelectorAll("input[name='listing_type']").forEach(function (r) {
  r.addEventListener("change", updatePrice);
});
priceInput.addEventListener("input", updatePrice);
propType.addEventListener("change", updateFields);

// ---------- Form bhejna ----------
form.addEventListener("submit", async function (e) {
  e.preventDefault(); // page reload hone se roko

  // Pehle browser me check
  if (form.owner_name.value.trim() === "") return showStatus("Please enter your name.", true);
  if (!/^[0-9]{10}$/.test(form.phone.value.trim())) {
    return showStatus("Please enter a 10 digit mobile number.", true);
  }
  if (!parseInt(form.sqft.value, 10)) return showStatus("Please enter the area in sq.ft.", true);
  if (!parseInt(priceInput.value.replace(/[^0-9]/g, ""), 10)) {
    return showStatus("Please enter the expected price.", true);
  }
  if (!form.consent.checked) return showStatus("Please agree to be contacted by us.", true);

  // Plot ya Office/Shop me BHK aur bathrooms ki value 0 bhejo
  const formData = new FormData(form);
  if (propType.value === "plot" || propType.value === "commercial") {
    formData.set("bhk", "0");
    formData.set("bathrooms", "0");
  }

  submitBtn.disabled = true;
  submitBtn.textContent = "Submitting...";
  statusBox.textContent = "";

  try {
    // FormData bhejte waqt Content-Type khud nahi likhte, browser likh deta hai
    const response = await fetch(API_URL, { method: "POST", body: formData });
    const result = await response.json();

    if (result.success) {
      showSuccess();
      return;
    }
    // Server ne galti batayi: pehli galti dikhao
    showStatus((result.errors && result.errors[0]) || "Something went wrong. Please try again.", true);
  } catch (err) {
    showStatus("Could not send your request. Please check that XAMPP is running and try again.", true);
  }

  submitBtn.disabled = false;
  submitBtn.textContent = "Submit Property";
});

// ---------- Success hone par form ki jagah dhanyavaad ----------
function showSuccess() {
  const waText = "Hello Aashiyana Realty, I just submitted my property on your website.";
  const waLink = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(waText);

  card.innerHTML =
    '<div class="sell-success">' +
      '<i class="bi bi-check-circle-fill"></i>' +
      "<h3>Thank you!</h3>" +
      "<p>We have received your property details. Our team will call you shortly to discuss the next steps.</p>" +
      '<a href="' + waLink + '" target="_blank" rel="noopener" class="btn btn-teal">' +
        '<i class="bi bi-whatsapp"></i> Message us on WhatsApp</a> ' +
      '<a href="sell.html" class="btn btn-coral">Submit another property</a>' +
    "</div>";
  card.scrollIntoView({ behavior: "smooth", block: "center" });
}

// ---------- Page khulte hi ----------
updatePrice();
updateFields();