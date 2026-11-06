// =========================================================
// contact.js - Contact page ka JavaScript
// Kaam: form ka data PHP API ko bhejna (database me save hoga),
//       fir WhatsApp par bhejne ka button dikhana.
// =========================================================

// Apna WhatsApp number yahan badlo: 91 + 10 digit number
const WHATSAPP_NUMBER = "919800000000";

// PHP API ka address (public folder se ek folder upar api me)
const API_URL = "../api/enquiry.php";

const form = document.getElementById("enquiryForm");
const formMsg = document.getElementById("formMsg");
const submitBtn = form.querySelector("button[type=submit]");

// Error dikhane ka function (text safe tarike se daalte hain)
function showError(text) {
  formMsg.style.display = "block";
  formMsg.style.background = "rgba(255, 107, 87, 0.12)";
  formMsg.style.color = "#e9523d";
  formMsg.textContent = text;
}

// Success dikhane ka function (WhatsApp button ke saath)
function showSuccess(whatsappLink) {
  formMsg.style.display = "block";
  formMsg.style.background = "";
  formMsg.style.color = "";
  formMsg.innerHTML =
    '<i class="bi bi-check-circle-fill"></i> Enquiry save ho gayi! Hum jaldi aapko call karenge.' +
    '<br><a href="' + whatsappLink + '" target="_blank" rel="noopener" ' +
    'class="btn btn-teal" style="margin-top:12px">' +
    '<i class="bi bi-whatsapp"></i> WhatsApp par bhi bhejein</a>';
}

form.addEventListener("submit", async function (e) {
  e.preventDefault(); // page reload hone se roko

  // Form ki values padho
  const data = {
    name: document.getElementById("fName").value.trim(),
    phone: document.getElementById("fPhone").value.trim(),
    email: document.getElementById("fEmail").value.trim(),
    requirement: document.getElementById("fType").value,
    budget: document.getElementById("fBudget").value,
    area: document.getElementById("fArea").value.trim(),
    message: document.getElementById("fMessage").value.trim(),
  };

  // Button band karo taaki do baar na dab jaye
  const oldLabel = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.textContent = "Bhej rahe hain...";

  try {
    // PHP API ko data bhejo (POST request)
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    const result = await response.json();

    if (result.success) {
      // WhatsApp ka message banao
      const text =
        "Hello Aashiyana Realty,\n" +
        "Name: " + data.name + "\n" +
        "Mobile: " + data.phone + "\n" +
        (data.email ? "Email: " + data.email + "\n" : "") +
        "Requirement: " + data.requirement + "\n" +
        "Budget: " + data.budget + "\n" +
        (data.area ? "Preferred Area: " + data.area + "\n" : "") +
        (data.message ? "Message: " + data.message : "");

      const link =
        "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(text);

      showSuccess(link);
      form.reset();
    } else {
      // Server ne galti batayi (jaise mobile number galat)
      const msg = result.errors ? result.errors.join(" ") : result.error;
      showError(msg || "Kuch gadbad ho gayi. Dobara try karo.");
    }
  } catch (err) {
    showError(
      "Server se connect nahi ho paya. Check karo ki XAMPP me Apache chal raha hai aur site localhost se khuli hai."
    );
  }

  // Button wapas chalu karo
  submitBtn.disabled = false;
  submitBtn.innerHTML = oldLabel;
});