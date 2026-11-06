<?php
// =========================================================
// admin/logout.php - Logout
// Session ka saara data hata do aur login page par bhej do.
// =========================================================

session_start();
$_SESSION = [];     // session ka data khali karo
session_destroy();  // session khatam karo

header("Location: login.php");
exit;