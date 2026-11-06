<?php
// =========================================================
// api/_session.php - Visitor login ke APIs ka shared helper
// (Naam ke aage _ hai kyunki ye seedha browser me kholne ke liye nahi hai)
// =========================================================

require_once __DIR__ . "/../config/db.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

define("SESSION_LIFETIME", 60 * 60 * 24 * 30);   // login 30 din yaad rahega

// Visitors ki session files apne alag folder me (admin ki sessions se alag)
$sessionDir = __DIR__ . "/../storage/sessions";
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0700, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}

ini_set("session.gc_maxlifetime", (string) SESSION_LIFETIME);
session_name("aashiyana_user");
session_set_cookie_params([
    "lifetime" => SESSION_LIFETIME,
    "path"     => "/",
    "httponly" => true,     // JavaScript is cookie ko padh nahi sakti
    "samesite" => "Lax",
]);
session_start();

// ---------- Chhote helpers ----------
function jsonOut($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJson() {
    $data = json_decode(file_get_contents("php://input"), true);
    return is_array($data) ? $data : [];
}

function clean($value, $max) {
    return mb_substr(trim((string) $value), 0, $max);
}

// Mobile number: sirf digits rakho, +91 ya 0 se shuru ho to hata do
function cleanPhone($value) {
    $digits = preg_replace('/[^0-9]/', '', (string) $value);
    if (strlen($digits) === 12 && substr($digits, 0, 2) === "91") {
        $digits = substr($digits, 2);
    }
    if (strlen($digits) === 11 && $digits[0] === "0") {
        $digits = substr($digits, 1);
    }
    return $digits;
}

// Bharat ke mobile number 6, 7, 8 ya 9 se shuru hote hain
function validPhone($phone) {
    return (bool) preg_match('/^[6-9][0-9]{9}$/', $phone);
}