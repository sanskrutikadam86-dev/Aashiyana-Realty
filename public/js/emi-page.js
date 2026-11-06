// =========================================================
// emi-page.js - emi.html ka JavaScript
// Kaam: calculator ko page par lagana.
// (Asli hisaab emi.js me hota hai)
// =========================================================

const box = document.getElementById("emiPage");

// editablePrice: user khud price likhega, wide: controls aur result side-by-side
box.innerHTML = window.EMI.html({ editablePrice: true, wide: true });
window.EMI.init(box.querySelector(".emi"));