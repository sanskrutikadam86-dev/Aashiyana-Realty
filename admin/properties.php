<?php
// =========================================================
// admin/properties.php
// Kaam: saari properties ki list dikhana, delete karna,
//       aur "featured" on/off karna.
// =========================================================

require_once __DIR__ . "/auth.php";            // login check
require_once __DIR__ . "/../config/db.php";    // database

// CSRF token (delete jaise buttons ko fake requests se bachane ke liye)
if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

// ---------- 1. Button dabane par kya karna hai (POST) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $msg    = "error";
    $token  = $_POST["csrf"] ?? "";
    $action = $_POST["action"] ?? "";
    $id     = (int) ($_POST["id"] ?? 0);

    if (hash_equals($_SESSION["csrf"], $token) && $id > 0) {

        if ($action === "delete") {
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "deleted";

        } elseif ($action === "toggle_featured") {
            // 1 - is_featured : 1 ho to 0 ban jata hai, 0 ho to 1
            $stmt = $pdo->prepare("UPDATE properties SET is_featured = 1 - is_featured WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "featured";
        }
    }

    // Redirect karo taaki page refresh par dobara delete na ho
    header("Location: properties.php?msg=" . $msg);
    exit;
}

// ---------- 2. Upar dikhane wale messages ----------
$messages = [
    "deleted"  => ["success", "Property delete ho gayi."],
    "featured" => ["success", "Featured status badal diya."],
        "added"    => ["success", "Nayi property add ho gayi."],
    "updated"  => ["success", "Property update ho gayi."],
    "error"    => ["error",   "Kuch gadbad ho gayi. Dobara try karo."],
];
$flash = $messages[$_GET["msg"] ?? ""] ?? null;

// ---------- 3. Price ko sundar likhna (14500000 -> Rs 1.45 Cr) ----------
function priceLabel($price, $type) {
    $rs = "\u{20B9}";
    if ($type === "buy") {
        if ($price >= 10000000) {
            return $rs . rtrim(rtrim(number_format($price / 10000000, 2), "0"), ".") . " Cr";
        }
        if ($price >= 100000) {
            return $rs . rtrim(rtrim(number_format($price / 100000, 2), "0"), ".") . " Lakh";
        }
        return $rs . number_format($price);
    }
    if ($price >= 100000) {
        return $rs . rtrim(rtrim(number_format($price / 100000, 2), "0"), ".") . " Lakh / mo";
    }
    return $rs . number_format($price) . " / mo";
}

// Text ko safe banane ka chhota shortcut
function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

// ---------- 4. Properties database se lana ----------
$props = $pdo->query(
    "SELECT id, title, type, area, price, image, is_featured
     FROM properties ORDER BY id DESC"
)->fetchAll();

// ---------- 5. Page dikhana ----------
$pageTitle  = "Properties";
$activePage = "properties";
require __DIR__ . "/header.php";
?>

<div class="page-head">
  <h1 class="page-title">Properties <small>(<?php echo count($props); ?>)</small></h1>
  <a href="property_form.php" class="btn btn-small">+ Add Property</a>
</div>

<?php if ($flash): ?>
  <div class="alert <?php echo $flash[0]; ?>"><?php echo e($flash[1]); ?></div>
<?php endif; ?>

<div class="card">
  <?php if (!$props): ?>
    <p class="muted">Abhi koi property nahi hai. Upar se naya add karo.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Photo</th>
            <th>Title</th>
            <th>Type</th>
            <th>Area</th>
            <th>Price</th>
            <th>Featured</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($props as $p): ?>
            <tr>
              <td><img class="thumb" src="../public/images/<?php echo e($p["image"]); ?>" alt=""></td>
              <td><strong><?php echo e($p["title"]); ?></strong></td>
              <td><span class="type-tag"><?php echo e(ucfirst($p["type"])); ?></span></td>
              <td><?php echo e($p["area"]); ?></td>
              <td><?php echo e(priceLabel((int) $p["price"], $p["type"])); ?></td>

              <!-- Featured on/off -->
              <td>
                <form method="post" class="inline-form">
                  <input type="hidden" name="csrf" value="<?php echo e($_SESSION["csrf"]); ?>">
                  <input type="hidden" name="action" value="toggle_featured">
                  <input type="hidden" name="id" value="<?php echo (int) $p["id"]; ?>">
                  <button type="submit" class="star-btn <?php echo $p["is_featured"] ? "on" : ""; ?>"
                          title="Home page par dikhana hai ya nahi">&#9733;</button>
                </form>
              </td>

              <!-- Edit aur Delete -->
              <td class="actions">
                <a href="property_form.php?id=<?php echo (int) $p["id"]; ?>" class="btn btn-small btn-teal">Edit</a>
                <form method="post" class="inline-form"
                      onsubmit="return confirm('Pakka delete karna hai? Ye wapas nahi aayegi.');">
                  <input type="hidden" name="csrf" value="<?php echo e($_SESSION["csrf"]); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int) $p["id"]; ?>">
                  <button type="submit" class="btn btn-small btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/footer.php"; ?>