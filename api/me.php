<?php
// =========================================================
// api/me.php
// Kaam: batana ki abhi koi visitor login hai ya nahi.
// (Har page par navbar ka button isi se tay hota hai.)
// =========================================================

require __DIR__ . "/_session.php";

if (empty($_SESSION["user_id"])) {
    jsonOut(["success" => true, "logged_in" => false]);
}

try {
    $stmt = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
    $stmt->execute([(int) $_SESSION["user_id"]]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    jsonOut(["success" => false, "logged_in" => false], 500);
}

// Admin ne user delete kar diya ho to login bhi hata do
if (!$user) {
    $_SESSION = [];
    jsonOut(["success" => true, "logged_in" => false]);
}

jsonOut(["success" => true, "logged_in" => true, "user" => $user]);