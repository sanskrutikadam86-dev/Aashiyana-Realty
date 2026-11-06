// =========================================================
// emi.js - EMI calculator (do jagah use hota hai)
//   1. property.html : property ki price pehle se bhari hoti hai
//   2. emi.html      : user khud price likhta hai
//
// Isme 2 kaam ke function hain:
//   EMI.html({...})   -> calculator ka HTML banata hai
//   EMI.init(box)     -> sliders ko chalu karta hai (EMI nikalta hai)
// =========================================================

(function () {
  // Slider ki values yahan yaad rehti hain
  const state = { price: 15000000, down: 20, rate: 8.5, years: 20 };

  // ---------- Chhote helpers ----------
  function esc(text) {
    return String(text == null ? "" : text).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }

  function trim(x) {
    return x.toFixed(2).replace(/\.?0+$/, "");
  }

  // Bada amount chhota likhna: 14500000 -> Rs 1.45 Cr
  function short(n) {
    const rs = "\u20B9";
    if (n >= 10000000) return rs + trim(n / 10000000) + " Cr";
    if (n >= 100000) return rs + trim(n / 100000) + " Lakh";
    return rs + Math.round(n).toLocaleString("en-IN");
  }

  // Poora amount: 78421.6 -> Rs 78,422
  function full(n) {
    return "\u20B9" + Math.round(n).toLocaleString("en-IN");
  }

  // ---------- EMI ka hisaab ----------
  // EMI = P x r x (1+r)^n / ((1+r)^n - 1)
  //   P = loan ki rakam
  //   r = mahine ka byaj (saal ka byaj / 12 / 100)
  //   n = kitne mahine
  function calc(price, downPercent, annualRate, years) {
    const loan = price * (1 - downPercent / 100);
    const n = years * 12;
    const r = annualRate / 12 / 100;

    let emi = 0;
    if (loan > 0 && n > 0) {
      if (r === 0) {
        emi = loan / n; // byaj 0 ho to seedha baant do
      } else {
        const f = Math.pow(1 + r, n);
        emi = (loan * r * f) / (f - 1);
      }
    }
    const total = emi * n;
    return { loan: loan, emi: emi, total: total, interest: total - loan };
  }

  // ---------- Calculator ka HTML banana ----------
  // opts.price         : property ki price (detail page ke liye)
  // opts.editablePrice : true ho to user khud price likh sakta hai (emi.html)
  // opts.wide          : true ho to controls aur result side-by-side
  function html(opts) {
    opts = opts || {};
    const editable = !!opts.editablePrice;
    if (!editable && opts.price) state.price = Number(opts.price);

    const priceBlock = editable
      ? '<div class="emi-field">' +
          '<label for="emiPrice">Property price (\u20B9)</label>' +
          '<input type="number" id="emiPrice" min="100000" step="100000" value="' + esc(state.price) + '" />' +
          '<div class="emi-hint" id="emiPriceHint"></div>' +
        "</div>"
      : '<div class="emi-field">' +
          "<label>Property price</label>" +
          '<div class="emi-fixed">' + esc(short(state.price)) + "</div>" +
        "</div>";

    return (
      '<div class="emi' + (opts.wide ? " emi-wide" : "") + '">' +
        '<h3 class="emi-title"><i class="bi bi-calculator"></i> Home Loan EMI Calculator</h3>' +
        '<div class="emi-body">' +

          // ----- Left: sliders -----
          '<div class="emi-controls">' +
            priceBlock +

            '<div class="emi-field">' +
              '<label>Down payment <span class="emi-val" id="emiDownVal"></span></label>' +
              '<input type="range" id="emiDown" min="10" max="90" step="5" value="' + esc(state.down) + '" />' +
            "</div>" +

            '<div class="emi-field">' +
              '<label>Interest rate (per year) <span class="emi-val" id="emiRateVal"></span></label>' +
              '<input type="range" id="emiRate" min="5" max="15" step="0.1" value="' + esc(state.rate) + '" />' +
            "</div>" +

            '<div class="emi-field">' +
              '<label>Loan tenure <span class="emi-val" id="emiYearsVal"></span></label>' +
              '<input type="range" id="emiYears" min="1" max="30" step="1" value="' + esc(state.years) + '" />' +
            "</div>" +
          "</div>" +

          // ----- Right: result -----
          '<div class="emi-result">' +
            '<div class="emi-label">Your monthly EMI</div>' +
            '<div class="emi-big" id="emiResult">-</div>' +

            '<div class="emi-rows">' +
              '<div><span>Loan amount</span><strong id="emiLoan">-</strong></div>' +
              '<div><span>Total interest</span><strong id="emiInt">-</strong></div>' +
              '<div><span>Total payment</span><strong id="emiTotal">-</strong></div>' +
            "</div>" +

            '<div class="emi-chart">' +
              '<div class="donut" id="emiDonut"><div class="donut-hole">' +
                '<strong id="emiYrs">-</strong><span>yrs</span></div></div>' +
              '<ul class="emi-legend">' +
                '<li><i class="dot teal"></i> Principal <b id="emiPPct"></b></li>' +
                '<li><i class="dot coral"></i> Interest <b id="emiIPct"></b></li>' +
              "</ul>" +
            "</div>" +
          "</div>" +

        "</div>" +
        '<p class="emi-note">This is only an estimate. The actual EMI depends on your bank and your eligibility.</p>' +
      "</div>"
    );
  }

  // ---------- Sliders ko chalu karna ----------
  function init(root) {
    if (!root) return;

    const $ = function (sel) {
      return root.querySelector(sel);
    };
    const priceInput = $("#emiPrice"); // sirf emi.html par hota hai
    const down = $("#emiDown");
    const rate = $("#emiRate");
    const years = $("#emiYears");

    function update() {
      // Slider ki values yaad rakho
      state.down = Number(down.value);
      state.rate = Number(rate.value);
      state.years = Number(years.value);
      if (priceInput) state.price = Number(priceInput.value) || 0;

      const price = state.price;

      // Slider ke saath dikhne wali values
      $("#emiDownVal").textContent = state.down + "% (" + short((price * state.down) / 100) + ")";
      $("#emiRateVal").textContent = state.rate.toFixed(1) + "%";
      $("#emiYearsVal").textContent = state.years + " years";
      if (priceInput) {
        $("#emiPriceHint").textContent = price > 0 ? "= " + short(price) : "";
      }

      // Price sahi na ho to result khali
      if (price <= 0) {
        ["#emiResult", "#emiLoan", "#emiInt", "#emiTotal", "#emiYrs"].forEach(function (id) {
          $(id).textContent = "-";
        });
        return;
      }

      const r = calc(price, state.down, state.rate, state.years);

      $("#emiResult").innerHTML = esc(full(r.emi)) + " <small>/ month</small>";
      $("#emiLoan").textContent = short(r.loan);
      $("#emiInt").textContent = short(r.interest);
      $("#emiTotal").textContent = short(r.total);
      $("#emiYrs").textContent = state.years;

      // Donut chart: mooldhan aur byaj ka hissa
      const principalPct = r.total > 0 ? Math.round((r.loan / r.total) * 100) : 100;
      $("#emiDonut").style.background =
        "conic-gradient(#7fe3d9 0 " + principalPct + "%, var(--coral) " + principalPct + "% 100%)";
      $("#emiPPct").textContent = principalPct + "%";
      $("#emiIPct").textContent = 100 - principalPct + "%";
    }

    // Slider hilte hi turant dobara hisaab
    [down, rate, years].forEach(function (el) {
      el.addEventListener("input", update);
    });
    if (priceInput) priceInput.addEventListener("input", update);

    update();
  }

  // Dusri files ke liye: EMI.html(...) aur EMI.init(...)
  window.EMI = { html: html, init: init };
})();