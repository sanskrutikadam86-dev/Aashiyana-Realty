<?php
// =========================================================
// api/sell.php
// Kaam: "Sell / Rent Your Property" form ka data lena,
//       check karna, photos save karna, aur database ke
//       'sell_requests' table me daalna.
// Sirf POST leti hai (form bhejne wali request).
// =========================================================

header("Content-Type: application/json; charset=utf-8");

// Galti hone par jawab bhejne ka chhota function
function fail($code, $errors) {
    http_response_code($code);
    echo json_encode(["success" => false, "errors" => (array) $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 1. Sirf POST allowed ----------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    fail(405, "Sirf POST request allowed hai.");
}

require __DIR__ . "/../config/db.php";

// Text ko saaf karna: faltu space hatao aur lambai limit karo
function clean($value, $max) {
    return mb_substr(trim((string) $value), 0, $max);
}

// ---------- 2. Spam jaal (honeypot) ----------
// Form me ek chhupa hua "website" box hai. Insaan use nahi bharta,
// par spam bots bhar dete hain. Bhara mila to chup-chaap success dikha do.
if (!empty($_POST["website"])) {
    echo json_encode(["success" => true]);
    exit;
}

// ---------- 3. Form ki values padhna ----------
$ownerName    = clean($_POST["owner_name"] ?? "", 100);
$phone        = clean($_POST["phone"] ?? "", 15);
$email        = clean($_POST["email"] ?? "", 120);
$listingType  = $_POST["listing_type"] ?? "";
$propertyType = $_POST["property_type"] ?? "";
$area         = clean($_POST["area"] ?? "", 60);
$address      = clean($_POST["address"] ?? "", 200);
$bhk          = (int) ($_POST["bhk"] ?? 0);
$bathrooms    = (int) ($_POST["bathrooms"] ?? 0);
$sqft         = (int) ($_POST["sqft"] ?? 0);
$price        = (int) preg_replace('/[^0-9]/', '', $_POST["expected_price"] ?? "");
$description  = clean($_POST["description"] ?? "", 1500);
$consent      = !empty($_POST["consent"]);

$areas = ["Andheri", "Bandra", "Powai", "Goregaon", "Borivali",
          "Mulund", "Worli", "Lower Parel", "Thane", "Navi Mumbai", "Other"];

// ---------- 4. Validation (data sahi hai ya nahi) ----------
// Ye server par bhi zaroori hai, kyunki browser ke check koi bhi bypass kar sakta hai.
$errors = [];

if ($ownerName === "")                                   $errors[] = "Please enter your name.";
if (!preg_match('/^[0-9]{10}$/', $phone))                $errors[] = "Please enter a 10 digit mobile number.";
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email address is not valid.";
if (!in_array($listingType, ["sell", "rent"], true))     $errors[] = "Please choose Sell or Rent.";
if (!in_array($propertyType, ["flat", "house", "plot", "commercial"], true)) $errors[] = "Please choose the property type.";
if (!in_array($area, $areas, true))                      $errors[] = "Please choose an area.";
if ($bhk < 0 || $bhk > 10)                               $errors[] = "BHK must be between 0 and 10.";
if ($bathrooms < 0 || $bathrooms > 10)                   $errors[] = "Bathrooms must be between 0 and 10.";
if ($sqft < 1 || $sqft > 1000000)                        $errors[] = "Please enter the area in sq.ft.";
if ($price < 1)                                          $errors[] = "Please enter the expected price.";
if (!$consent)                                           $errors[] = "Please agree to be contacted by us.";

// ---------- 5. Photos check karna (maximum 3, har ek 2 MB tak) ----------
$uploads = [];
if (!empty($_FILES["photos"]) && is_array($_FILES["photos"]["name"])) {
    $total = count($_FILES["photos"]["name"]);
    for ($i = 0; $i < $total; $i++) {
        if ($_FILES["photos"]["error"][$i] === UPLOAD_ERR_NO_FILE) {
            continue; // is box me koi file nahi chuni
        }
        $uploads[] = [
            "tmp"   => $_FILES["photos"]["tmp_name"][$i],
            "size"  => $_FILES["photos"]["size"][$i],
            "error" => $_FILES["photos"]["error"][$i],
        ];
    }
}

if (count($uploads) > 3) {
    $errors[] = "You can upload a maximum of 3 photos.";
} else {
    $allowedTypes = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    foreach ($uploads as $index => $up) {
        if ($up["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "A photo could not be uploaded. Please try again.";
            break;
        }
        if ($up["size"] > 2 * 1024 * 1024) {
            $errors[] = "Each photo must be smaller than 2 MB.";
            break;
        }
        // getimagesize asli image hone ki jaanch karta hai (sirf naam nahi dekhta)
        $info = @getimagesize($up["tmp"]);
        if (!$info || !isset($allowedTypes[$info["mime"]])) {
            $errors[] = "Only JPG, PNG or WebP photos are allowed.";
            break;
        }
        $uploads[$index]["ext"] = $allowedTypes[$info["mime"]];
    }
}

if ($errors) {
    fail(422, $errors);
}

// ---------- 6. Rate limit: ek IP se ghante me 5 se zyada requests nahi ----------
$ip = $_SERVER["REMOTE_ADDR"] ?? "";

try {
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM sell_requests
         WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)"
    );
    $check->execute([$ip]);
    if ((int) $check->fetchColumn() >= 5) {
        fail(429, "Too many requests. Please try again after some time.");
    }

    // ---------- 7. Photos save karna ----------
    $savedFiles = [];
    if ($uploads) {
        $dir = __DIR__ . "/../public/images/sell";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach ($uploads as $up) {
            // Random naam taaki do photos ka naam same na ho
            $name = "sell_" . bin2hex(random_bytes(6)) . "." . $up["ext"];
            if (move_uploaded_file($up["tmp"], $dir . "/" . $name)) {
                $savedFiles[] = $name;
            }
        }
    }

    // ---------- 8. Database me save karna ----------
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO sell_requests
             (owner_name, phone, email, listing_type, property_type, area, address,
              bhk, bathrooms, sqft, expected_price, description, photos, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $ownerName, $phone, $email, $listingType, $propertyType, $area, $address,
            $bhk, $bathrooms, $sqft, $price, $description, implode(",", $savedFiles), $ip,
        ]);
    } catch (Exception $e) {
        // Database me save nahi hua to upload ki hui photos bhi hata do
        foreach ($savedFiles as $file) {
            @unlink($dir . "/" . $file);
        }
        throw $e;
    }

    echo json_encode([
        "success" => true,
        "id"      => (int) $pdo->lastInsertId(),
        "message" => "Your request has been received.",
    ]);

} catch (Exception $e) {
    fail(500, "Server error. Please try again later.");
}