<?php
// =========================================================
// admin/property_form.php
// Kaam: nayi property add karna AUR purani property edit karna.
//   property_form.php         -> naya add
//   property_form.php?id=5    -> id 5 wali property edit
// Photo upload bhi yahin hota hai.
// =========================================================

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/db.php";

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

// Website ke filters me jo areas hain, wahi yahan allowed hain
$areas = ["Andheri", "Bandra", "Powai", "Goregaon", "Borivali",
          "Mulund", "Worli", "Lower Parel", "Thane", "Navi Mumbai"];
$types = ["buy" => "Buy", "rent" => "Rent", "commercial" => "Commercial"];

// ---------- 1. Add hai ya Edit? ----------
$id     = (int) ($_GET["id"] ?? 0);
$isEdit = $id > 0;

// Form ki shuruaati (khali) values
$p = [
    "title" => "", "type" => "buy", "area" => "Andheri", "address" => "",
    "bhk" => 2, "bathrooms" => 2, "sqft" => "", "price" => "",
    "badge" => "", "image" => "", "description" => "", "is_featured" => 0,
];

// Edit hai to database se purani details lao
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        header("Location: properties.php?msg=error");
        exit;
    }
    $p = array_merge($p, $row);
}

$errors = [];

// ---------- 2. Form submit hua (POST) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")) {
        $errors[] = "Session expire ho gaya. Page refresh karke dobara try karo.";
    } else {
        // Form ki values padho aur saaf karo
        $p["title"]       = mb_substr(trim($_POST["title"] ?? ""), 0, 150);
        $p["type"]        = $_POST["type"] ?? "";
        $p["area"]        = $_POST["area"] ?? "";
        $p["address"]     = mb_substr(trim($_POST["address"] ?? ""), 0, 200);
        $p["bhk"]         = (int) ($_POST["bhk"] ?? 0);
        $p["bathrooms"]   = (int) ($_POST["bathrooms"] ?? 0);
        $p["sqft"]        = (int) ($_POST["sqft"] ?? 0);
        $p["price"]       = (int) preg_replace('/[^0-9]/', '', $_POST["price"] ?? "");
        $p["badge"]       = mb_substr(trim($_POST["badge"] ?? ""), 0, 40);
        $p["description"] = mb_substr(trim($_POST["description"] ?? ""), 0, 2000);
        $p["is_featured"] = isset($_POST["is_featured"]) ? 1 : 0;

        // ----- Validation (check karna ki data sahi hai) -----
        if ($p["title"] === "")                      $errors[] = "Title zaroori hai.";
        if (!isset($types[$p["type"]]))              $errors[] = "Type sahi chuno.";
        if (!in_array($p["area"], $areas, true))     $errors[] = "Area list me se chuno.";
        if ($p["address"] === "")                    $errors[] = "Address zaroori hai.";
        if ($p["bhk"] < 0 || $p["bhk"] > 10)         $errors[] = "BHK 0 se 10 ke beech rakho.";
        if ($p["bathrooms"] < 0 || $p["bathrooms"] > 10) $errors[] = "Bathrooms 0 se 10 ke beech rakho.";
        if ($p["sqft"] < 1)                          $errors[] = "Area (sq.ft) daalo.";
        if ($p["price"] < 1)                         $errors[] = "Price daalo (poore rupaye me).";

        // ----- Photo check -----
        $file      = $_FILES["image"] ?? null;
        $hasUpload = $file && $file["error"] !== UPLOAD_ERR_NO_FILE;
        $ext       = "";

        if ($hasUpload) {
            if ($file["error"] !== UPLOAD_ERR_OK) {
                $errors[] = "Photo upload nahi ho payi. Dobara try karo.";
            } elseif ($file["size"] > 2 * 1024 * 1024) {
                $errors[] = "Photo 2 MB se chhoti honi chahiye.";
            } else {
                // getimagesize asli image hone ki jaanch karta hai (sirf naam nahi dekhta)
                $info    = @getimagesize($file["tmp_name"]);
                $allowed = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
                if (!$info || !isset($allowed[$info["mime"]])) {
                    $errors[] = "Sirf JPG, PNG ya WebP photo allowed hai.";
                } else {
                    $ext = $allowed[$info["mime"]];
                }
            }
        } elseif (!$isEdit) {
            $errors[] = "Property ki photo zaroori hai.";
        }

        // ----- Sab theek hai to photo save karo -----
        $newImage = null;
        if (!$errors && $hasUpload) {
            $dir = __DIR__ . "/../public/images/uploads";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            // Random naam taaki do photos ka naam same na ho
            $name = "prop_" . bin2hex(random_bytes(6)) . "." . $ext;

            if (move_uploaded_file($file["tmp_name"], $dir . "/" . $name)) {
                $newImage = "uploads/" . $name;
            } else {
                $errors[] = "Photo save nahi ho payi.";
            }
        }

        // ----- Database me save karo -----
        if (!$errors) {
            $oldImage = $p["image"];
            $image    = $newImage ?? $oldImage;

            $values = [
                $p["title"], $p["type"], $p["area"], $p["address"],
                $p["bhk"], $p["bathrooms"], $p["sqft"], $p["price"],
                $p["badge"], $image, $p["description"], $p["is_featured"],
            ];

            if ($isEdit) {
                $stmt = $pdo->prepare(
                    "UPDATE properties SET title=?, type=?, area=?, address=?, bhk=?,
                     bathrooms=?, sqft=?, price=?, badge=?, image=?, description=?,
                     is_featured=? WHERE id=?"
                );
                $stmt->execute(array_merge($values, [$id]));

                // Nayi photo lagi hai to purani uploaded photo hata do
                // (sirf wahi jo uploads folder ki hai aur kisi aur property me use nahi ho rahi)
                if ($newImage && strpos($oldImage, "uploads/") === 0) {
                    $use = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE image = ?");
                    $use->execute([$oldImage]);
                    if ((int) $use->fetchColumn() === 0) {
                        @unlink(__DIR__ . "/../public/images/" . $oldImage);
                    }
                }
                header("Location: properties.php?msg=updated");
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO properties
                     (title, type, area, address, bhk, bathrooms, sqft, price,
                      badge, image, description, is_featured)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute($values);
                header("Location: properties.php?msg=added");
            }
            exit;
        }
    }
}

// ---------- 3. Page dikhana ----------
$pageTitle  = $isEdit ? "Edit Property" : "Add Property";
$activePage = "properties";
require __DIR__ . "/header.php";
?>

<div class="page-head">
  <h1 class="page-title"><?php echo $isEdit ? "Edit Property" : "Add New Property"; ?></h1>
  <a href="properties.php" class="back-link">&larr; Wapas list par</a>
</div>

<?php if ($errors): ?>
  <div class="alert error">
    <?php foreach ($errors as $err): ?>
      <div>&bull; <?php echo e($err); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card">
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf" value="<?php echo e($_SESSION["csrf"]); ?>">

    <div class="form-grid">

      <div class="field full">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" maxlength="150"
               placeholder="jaise: 2 BHK Flat, Lokhandwala"
               value="<?php echo e($p["title"]); ?>" required>
      </div>

      <div class="field">
        <label for="type">Type</label>
        <select id="type" name="type">
          <?php foreach ($types as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo $p["type"] === $val ? "selected" : ""; ?>>
              <?php echo $label; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="area">Area</label>
        <select id="area" name="area">
          <?php foreach ($areas as $a): ?>
            <option value="<?php echo e($a); ?>" <?php echo $p["area"] === $a ? "selected" : ""; ?>>
              <?php echo e($a); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field full">
        <label for="address">Address</label>
        <input type="text" id="address" name="address" maxlength="200"
               placeholder="jaise: Andheri West, Mumbai"
               value="<?php echo e($p["address"]); ?>" required>
      </div>

      <div class="field">
        <label for="bhk">BHK <span class="hint">(commercial ke liye 0)</span></label>
        <input type="number" id="bhk" name="bhk" min="0" max="10" value="<?php echo e($p["bhk"]); ?>">
      </div>

      <div class="field">
        <label for="bathrooms">Bathrooms</label>
        <input type="number" id="bathrooms" name="bathrooms" min="0" max="10" value="<?php echo e($p["bathrooms"]); ?>">
      </div>

      <div class="field">
        <label for="sqft">Area (sq.ft)</label>
        <input type="number" id="sqft" name="sqft" min="1" value="<?php echo e($p["sqft"]); ?>" required>
      </div>

      <div class="field">
        <label for="price">Price <span class="hint">(poore rupaye me)</span></label>
        <input type="text" id="price" name="price" inputmode="numeric"
               placeholder="jaise: 14500000"
               value="<?php echo e($p["price"]); ?>" required>
        <div class="hint" id="pricePreview"></div>
      </div>

      <div class="field">
        <label for="badge">Badge <span class="hint">(optional)</span></label>
        <input type="text" id="badge" name="badge" maxlength="40"
               placeholder="jaise: Ready to Move"
               value="<?php echo e($p["badge"]); ?>">
      </div>

      <div class="field checkbox-field">
        <label class="check">
          <input type="checkbox" name="is_featured" value="1" <?php echo $p["is_featured"] ? "checked" : ""; ?>>
          Home page par Featured dikhao
        </label>
      </div>

      <div class="field full">
        <label for="description">Description <span class="hint">(optional)</span></label>
        <textarea id="description" name="description" rows="4" maxlength="2000"><?php echo e($p["description"]); ?></textarea>
      </div>

      <div class="field full">
        <label for="image">Photo <span class="hint">(JPG, PNG ya WebP, 2 MB tak)</span></label>
        <?php if ($isEdit && $p["image"]): ?>
          <div class="current-img">
            <img src="../public/images/<?php echo e($p["image"]); ?>" alt="">
            <span class="hint">Abhi ki photo. Nayi chuno to ye badal jayegi.</span>
          </div>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
               <?php echo $isEdit ? "" : "required"; ?>>
      </div>

    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-small">
        <?php echo $isEdit ? "Update Property" : "Save Property"; ?>
      </button>
      <a href="properties.php" class="cancel-link">Cancel</a>
    </div>
  </form>
</div>

<script>
  // Price likhte hi niche sundar form dikhao: 14500000 -> Rs 1.45 Cr
  const priceInput = document.getElementById("price");
  const preview    = document.getElementById("pricePreview");
  const typeSelect = document.getElementById("type");

  function trim0(num) {
    return num.toFixed(2).replace(/\.?0+$/, "");
  }

  function showPreview() {
    const price = parseInt(priceInput.value.replace(/[^0-9]/g, ""), 10);
    if (!price) { preview.textContent = ""; return; }

    const rs = "\u20B9";
    let text;
    if (typeSelect.value === "buy") {
      if (price >= 10000000)     text = trim0(price / 10000000) + " Cr";
      else if (price >= 100000)  text = trim0(price / 100000) + " Lakh";
      else                       text = price.toLocaleString("en-IN");
    } else {
      if (price >= 100000)       text = trim0(price / 100000) + " Lakh / month";
      else                       text = price.toLocaleString("en-IN") + " / month";
    }
    preview.textContent = "= " + rs + text;
  }

  priceInput.addEventListener("input", showPreview);
  typeSelect.addEventListener("change", showPreview);
  showPreview();
</script>

<?php require __DIR__ . "/footer.php"; ?>