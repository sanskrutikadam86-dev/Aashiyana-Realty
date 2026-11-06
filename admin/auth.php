<?php
// =========================================================
// admin/auth.php - Pehredar (guard) file
// Jis bhi admin page ko sirf login wale dekh sakein,
// uske sabse upar ye line likho:
//     require __DIR__ . "/auth.php";
// =========================================================

session_start();

// Login nahi hai to login page par bhej do
if (empty($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}