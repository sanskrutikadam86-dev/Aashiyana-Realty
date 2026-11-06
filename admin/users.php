<?php
// =========================================================
// admin/users.php
// Kaam: login popup se aaye visitors ki details dikhana,
//       search karna, Call/WhatsApp karna, Excel (CSV) me download karna,
//       aur zaroorat par kisi ko delete karna.
// =========================================================

require_once __DIR__ . "/auth.php";            // login check
require_once __DIR__ . "/../config/db.php";    // database

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

// ---------- Search aur page ----------
$q    = trim($_GET["q"] ?? "");
$page = max(1, (int) ($_GET["page"] ?? 1));

// ---------- 1. Delete (POST) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $msg   = "error";
    $token = $_POST["csrf"] ?? "";
    $id    = (int) ($_POST["id"] ?? 0);

    if (hash_equals($_SESSION["csrf"], $token) && $id > 0 && ($_POST["action"] ?? "") === "delete") {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "deleted";
    }

    // Wapas usi search aur page par bhejo
    $back = "users.php?msg=" . $msg . "&page=" . max(1, (int) ($_POST["page"] ?? 1));
    if (($_POST["q"] ?? "") !== "") {
        $back .= "&q=" . urlencode($_POST["q"]);
    }
    header("Location: " . $back);
    exit;
}

// ---------- Search ka SQL ----------
$where  = "";
$params = [];
if ($q !== "") {
    $where  = " WHERE (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $like   = "%" . $q . "%";
    $params = [$like, $like, $like];
}

// ---------- 2. Excel (CSV) download ----------
if (isset($_GET["export"])) {
    // Excel me "=" ya "+" se shuru hone wala text formula ban jata hai (CSV injection).
    // Isliye aise text ke aage ek ' laga dete hain.
    $csvSafe = function ($value) {
        $value = (string) $value;
        if ($value !== "" && strpos("=+-@\t\r", $value[0]) !== false) {
            return "'" . $value;
        }
        return $value;
    };

    $stmt = $pdo->prepare(
        "SELECT name, phone, email, looking_for, source_page, visits, created_at, last_login
         FROM users" . $where . " ORDER BY id DESC"
    );
    $stmt->execute($params);

    header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="aashiyana-users-' . date("Y-m-d") . '.csv"');

    $out = fopen("php://output", "w");
    fwrite($out, "\xEF\xBB\xBF"); // Excel me Hindi/Marathi naam sahi dikhne ke liye
    fputcsv($out, ["Name", "Mobile", "Email", "Looking for", "Signed up from", "Visits", "Joined", "Last login"]);
    while ($row = $stmt->fetch()) {
        fputcsv($out, array_map($csvSafe, array_values($row)));
    }
    fclose($out);
    exit;
}

// ---------- 3. Messages ----------
$messages = [
    "deleted" => ["success", "User delete ho gaya."],
    "error"   => ["error",   "Kuch gadbad ho gayi. Dobara try karo."],
];
$flash = $messages[$_GET["msg"] ?? ""] ?? null;

// ---------- 4. Ginti aur pagination (ek page par 20) ----------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users" . $where);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$perPage = 20;
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;

// LIMIT/OFFSET ke numbers (int) bana diye hain, isliye seedha likhna safe hai
$stmt = $pdo->prepare(
    "SELECT * FROM users" . $where . " ORDER BY id DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$lookingLabels = [
    "buy"      => "Buy",
    "rent"     => "Rent",
    "sell"     => "Sell / Rent out",
    "browsing" => "Just browsing",
];

// ---------- 5. Page dikhana ----------
$pageTitle  = "Users";
$activePage = "users";
require __DIR__ . "/header.php";

$qs = $q !== "" ? "&q=" . urlencode($q) : "";
?>

<div class="page-head">
  <h1 class="page-title">Users <small>(<?php echo $total; ?>)</small></h1>
  <a href="users.php?export=1<?php echo $qs; ?>" class="btn btn-small btn-teal">Download Excel (CSV)</a>
</div>

<?php if ($flash): ?>
  <div class="alert <?php echo $flash[0]; ?>"><?php echo e($flash[1]); ?></div>
<?php endif; ?>

<form method="get" class="search-form">
  <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Name, mobile ya email se dhoondo">
  <button type="submit" class="btn btn-small">Search</button>
  <?php if ($q !== ""): ?>
    <a href="users.php" class="cancel-link">Clear</a>
  <?php endif; ?>
</form>

<div class="card">
  <?php if (!$users): ?>
    <p class="muted">
      <?php echo $q !== "" ? "Is search me koi user nahi mila." : "Abhi tak kisi ne login popup nahi bhara."; ?>
    </p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Contact</th>
            <th>Looking for</th>
            <th>Signed up from</th>
            <th>Joined</th>
            <th>Last visit</th>
            <th>Visits</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <?php
              $digits = preg_replace('/[^0-9]/', '', $u["phone"]);
              $waText = "Hello " . $u["name"] . ", this is Aashiyana Realty. Thank you for visiting our website. How can we help you find your home?";
              $waLink = "https://wa.me/91" . $digits . "?text=" . rawurlencode($waText);
            ?>
            <tr>
              <td>
                <strong><?php echo e($u["name"]); ?></strong>
                <?php if ($u["email"] !== ""): ?>
                  <div class="sub"><?php echo e($u["email"]); ?></div>
                <?php endif; ?>
              </td>

              <td>
                <?php echo e($u["phone"]); ?>
                <div class="contact-links">
                  <a class="mini-btn" href="tel:+91<?php echo e($digits); ?>">Call</a>
                  <a class="mini-btn wa" href="<?php echo e($waLink); ?>" target="_blank" rel="noopener">WhatsApp</a>
                </div>
              </td>

              <td><?php echo e($lookingLabels[$u["looking_for"]] ?? "-"); ?></td>
              <td><?php echo e($u["source_page"] !== "" ? $u["source_page"] : "-"); ?></td>

              <td class="nowrap"><?php echo date("d M Y", strtotime($u["created_at"])); ?>
                <div class="sub"><?php echo date("h:i A", strtotime($u["created_at"])); ?></div>
              </td>
              <td class="nowrap">
                <?php if ($u["last_login"]): ?>
                  <?php echo date("d M Y", strtotime($u["last_login"])); ?>
                  <div class="sub"><?php echo date("h:i A", strtotime($u["last_login"])); ?></div>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td><?php echo (int) $u["visits"]; ?></td>

              <td>
                <form method="post" class="inline-form"
                      onsubmit="return confirm('Ye user delete karna hai? Ye wapas nahi aayega.');">
                  <input type="hidden" name="csrf" value="<?php echo e($_SESSION["csrf"]); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int) $u["id"]; ?>">
                  <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                  <input type="hidden" name="q" value="<?php echo e($q); ?>">
                  <button type="submit" class="btn btn-small btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ===== Pagination ===== -->
    <?php if ($pages > 1): ?>
      <div class="pager">
        <?php if ($page > 1): ?>
          <a href="users.php?page=<?php echo $page - 1; ?><?php echo $qs; ?>">&larr; Pehle</a>
        <?php endif; ?>
        <span>Page <?php echo $page; ?> of <?php echo $pages; ?></span>
        <?php if ($page < $pages): ?>
          <a href="users.php?page=<?php echo $page + 1; ?><?php echo $qs; ?>">Aage &rarr;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/footer.php"; ?>