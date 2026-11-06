<?php
// =========================================================
// api/properties.php
// Kaam: database se properties nikalna aur JSON me dena.
//
// Filters (sab optional, URL me ? ke baad likhte hain):
//   ?type=buy|rent|commercial
//   ?area=Andheri
//   ?bhk=2
//   ?budget=100-200      (Lakh me: 100-200 = Rs 1 Cr se 2 Cr)
//   ?featured=1          (sirf home page wali properties)
// =========================================================

header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config/db.php";

// ---------- Price ko sundar likhna: 14500000 -> Rs 1.45 Cr ----------
function formatPrice($price, $type) {
    $rs = "\u{20B9}"; // rupee symbol

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

    // rent aur commercial: mahine ka kiraya
    if ($price >= 100000) {
        $v = rtrim(rtrim(number_format($price / 100000, 2), "0"), ".");
        return $rs . $v . " Lakh / month";
    }
    return $rs . number_format($price) . " / month";
}

try {
    // ---------- Filters se SQL ka WHERE hissa banana ----------
    $where  = [];   // conditions ki list
    $params = [];   // un conditions ki values

    if (!empty($_GET["type"]) && $_GET["type"] !== "all") {
        $where[]  = "type = ?";
        $params[] = $_GET["type"];
    }

    if (!empty($_GET["area"]) && $_GET["area"] !== "all") {
        $where[]  = "area = ?";
        $params[] = $_GET["area"];
    }

    if (!empty($_GET["bhk"]) && $_GET["bhk"] !== "all") {
        $where[]  = "bhk = ?";
        $params[] = (int) $_GET["bhk"];
    }

    // Budget sirf Buy properties par lagta hai (Lakh me aata hai)
    if (!empty($_GET["budget"]) && $_GET["budget"] !== "all") {
        $range = explode("-", $_GET["budget"]);
        if (count($range) === 2) {
            $where[]  = "(type = 'buy' AND price >= ? AND price < ?)";
            $params[] = (int) $range[0] * 100000;
            $params[] = (int) $range[1] * 100000;
        }
    }

    if (!empty($_GET["featured"])) {
        $where[] = "is_featured = 1";
    }

    // ---------- Poori query banana ----------
    $sql = "SELECT * FROM properties";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY id ASC";

    // prepare + execute = safe tarika (SQL injection se bachata hai)
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Har property me sundar price_label jod do
    foreach ($rows as &$row) {
        $row["price_label"] = formatPrice((int) $row["price"], $row["type"]);
    }
    unset($row);

    echo json_encode([
        "success"    => true,
        "count"      => count($rows),
        "properties" => $rows,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => "Server error: " . $e->getMessage(),
    ]);
}