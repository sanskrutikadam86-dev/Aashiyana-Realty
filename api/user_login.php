<?php
// =========================================================
// api/user_login.php
// Kaam: popup ka form lena, check karna, details database me
//       save karna (users table), aur visitor ko "login" karna.
//
// Agar ye mobile number pehle se hai to naya account nahi banta,
// purana hi use hota hai (naam purana hi rehta hai).
// =========================================================

require __DIR__ . "/_session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonOut(["success" => false, "errors" => ["Sirf POST request allowed hai."]], 405);
}

$input = readJson();

// ---------- Spam jaal (honeypot) ----------
// Chhupa hua "website" box insaan nahi bharta, bots bhar dete hain.
if (!empty($input["website"])) {
    jsonOut(["success" => true]);
}

// ---------- Form ki values ----------
$name    = clean($input["name"] ?? "", 100);
$phone   = cleanPhone($input["phone"] ?? "");
$email   = clean($input["email"] ?? "", 120);
$looking = clean($input["looking_for"] ?? "", 20);
$consent = !empty($input["consent"]);
$source  = preg_replace('/[^a-zA-Z0-9_.-]/', '', clean($input["source"] ?? "", 60));

// ---------- Validation (server par bhi zaroori hai) ----------
$errors = [];

if (mb_strlen($name) < 2) {
    $errors[] = "Please enter your name.";
}
if (!validPhone($phone)) {
    $errors[] = "Please enter a valid 10 digit mobile number.";
}
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email address is not valid.";
}
if (!in_array($looking, ["", "buy", "rent", "sell", "browsing"], true)) {
    $looking = "";
}
if (!$consent) {
    $errors[] = "Please agree to be contacted by us.";
}

if ($errors) {
    jsonOut(["success" => false, "errors" => $errors], 422);
}

$ip = $_SERVER["REMOTE_ADDR"] ?? "";

try {
    // ---------- Ye number pehle se hai? ----------
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user) {
        // Purana visitor: visits badhao, khali details bhar do
        $upd = $pdo->prepare(
            "UPDATE users
             SET last_login = NOW(),
                 visits = visits + 1,
                 email = IF(email = '', ?, email),
                 looking_for = IF(? <> '', ?, looking_for)
             WHERE id = ?"
        );
        $upd->execute([$email, $looking, $looking, $user["id"]]);

    } else {
        // Naya visitor: pehle rate limit (ek IP se ghante me 10 se zyada naye account nahi)
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM users WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)"
        );
        $chk->execute([$ip]);
        if ((int) $chk->fetchColumn() >= 10) {
            jsonOut(["success" => false, "errors" => ["Too many sign-ups. Please try again later."]], 429);
        }

        $ins = $pdo->prepare(
            "INSERT INTO users (name, phone, email, looking_for, source_page, ip, last_login)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE last_login = NOW(), visits = visits + 1"
        );
        $ins->execute([$name, $phone, $email, $looking, $source, $ip]);

        // Database me jo naam save hai wahi wapas lo
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
    }

    // ---------- Login karo (session me yaad rakho) ----------
    session_regenerate_id(true);
    $_SESSION["user_id"]    = (int) $user["id"];
    $_SESSION["user_name"]  = $user["name"];
    $_SESSION["user_phone"] = $user["phone"];

    jsonOut([
        "success" => true,
        "user"    => ["name" => $user["name"], "phone" => $user["phone"]],
    ]);

} catch (Exception $e) {
    jsonOut(["success" => false, "errors" => ["Server error. Please try again later."]], 500);
}