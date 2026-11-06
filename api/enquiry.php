<?php
// =========================================================
// api/enquiry.php
// Kaam: contact form ka data lena, check karna, aur
//       database ke 'enquiries' table me save karna.
// Ye sirf POST request leti hai (form bhejne wali request).
// =========================================================

header("Content-Type: application/json; charset=utf-8");

// ---------- 1. Sirf POST allowed ----------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error"   => "Sirf POST request allowed hai.",
    ]);
    exit;
}

require __DIR__ . "/../config/db.php";

// ---------- 2. Data padhna (JavaScript JSON bhejegi) ----------
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    $input = $_POST; // agar normal form se aaye to ye use hoga
}

// Text ko saaf karna: faltu space hatao aur lambai limit karo
function clean($value, $max) {
    return mb_substr(trim((string) $value), 0, $max);
}

$name        = clean($input["name"]        ?? "", 100);
$phone       = clean($input["phone"]       ?? "", 15);
$email       = clean($input["email"]       ?? "", 120);
$requirement = clean($input["requirement"] ?? "", 60);
$budget      = clean($input["budget"]      ?? "", 60);
$area        = clean($input["area"]        ?? "", 80);
$message     = clean($input["message"]     ?? "", 1000);
$propertyId  = !empty($input["property_id"]) ? (int) $input["property_id"] : null;

// ---------- 3. Validation (data sahi hai ya nahi) ----------
// Ye server par bhi zaroori hai, kyunki browser ke check koi bhi bypass kar sakta hai.
$errors = [];

if ($name === "") {
    $errors[] = "Naam zaroori hai.";
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = "10 digit ka mobile number daalein.";
}
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email sahi nahi hai.";
}

if ($errors) {
    http_response_code(422);
    echo json_encode(["success" => false, "errors" => $errors]);
    exit;
}

// ---------- 4. Database me save karna ----------
try {
    $stmt = $pdo->prepare(
        "INSERT INTO enquiries
         (name, phone, email, requirement, budget, area, message, property_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $name, $phone, $email, $requirement,
        $budget, $area, $message, $propertyId,
    ]);

    echo json_encode([
        "success" => true,
        "id"      => (int) $pdo->lastInsertId(),
        "message" => "Enquiry save ho gayi.",
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => "Server error: " . $e->getMessage(),
    ]);
}