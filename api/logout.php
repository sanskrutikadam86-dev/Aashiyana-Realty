<?php
// =========================================================
// api/logout.php - Visitor ko logout karna
// =========================================================

require __DIR__ . "/_session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonOut(["success" => false, "errors" => ["Sirf POST request allowed hai."]], 405);
}

$_SESSION = [];

// Browser se session ka cookie bhi hatao
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), "", time() - 3600, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

jsonOut(["success" => true]);