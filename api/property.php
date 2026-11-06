<?php
// =========================================================
// api/property.php
// Kaam: EK property ki poori details dena (id se),
//       aur usi jaisi 3 aur properties ("Similar Properties").
//
// Use: api/property.php?id=5
// =========================================================

header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config/db.php";

// ---------- Price ko sundar likhna: 14500000 -> Rs 1.45 Cr ----------
// (Ye wahi function hai jo api/properties.php me hai)
function formatPrice($price, $type) {
    $rs = "\u{20B9}";

    if ($type === "buy") {
        if ($price >= 10000000) {
            $v = rtrim(rtrim(number_format($price / 10000000, 2), "0"), ".");
            return $rs . $v . " Cr";
        }
        if ($price >= 100000) {
            $v = rtrim(rtrim(number_format($price / 100000, 2), "0"), ".");
            return $rs . $v . " Lakh";
        }
        return $rs . number_format($price);
    }

    if ($price >= 100000) {
        $v = rtrim(rtrim(number_format($price / 100000, 2), "0"), ".");
        return $rs . $v . " Lakh / month";
    }
    return $rs . number_format($price) . " / month";
}

// ---------- 1. id check karo ----------
$id = (int) ($_GET["id"] ?? 0);

if ($id < 1) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Property id sahi nahi hai."]);
    exit;
}

try {
    // ---------- 2. Property dhoondho ----------
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    $property = $stmt->fetch();

    if (!$property) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Property nahi mili."]);
        exit;
    }

    $property["price_label"] = formatPrice((int) $property["price"], $property["type"]);

    // ---------- 3. Milti-julti properties ----------
    // Isi area ki ya isi type ki, jo ye khud na ho.
    // Same area wali pehle aayengi.
    $stmt = $pdo->prepare(
        "SELECT * FROM properties
         WHERE id <> ? AND (area = ? OR type = ?)
         ORDER BY (area = ?) DESC, id DESC
         LIMIT 3"
    );
    $stmt->execute([$id, $property["area"], $property["type"], $property["area"]]);
    $similar = $stmt->fetchAll();

    foreach ($similar as &$s) {
        $s["price_label"] = formatPrice((int) $s["price"], $s["type"]);
    }
    unset($s);

    echo json_encode([
        "success"  => true,
        "property" => $property,
        "similar"  => $similar,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Server error: " . $e->getMessage()]);
}