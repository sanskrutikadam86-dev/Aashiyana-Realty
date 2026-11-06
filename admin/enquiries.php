<?php
// =========================================================
// admin/enquiries.php
// Kaam: contact form se aayi enquiries dikhana,
//       status badalna (new / contacted / closed),
//       customer ko call ya WhatsApp karne ke links dena,
//       aur enquiry delete karna.
// =========================================================

require_once __DIR__ . "/auth.php";            // login check
require_once __DIR__ . "/../config/db.php";    // database

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

$allowedStatus = ["new", "contacted", "closed"];

// Kaunsa filter laga hai (all / new / contacted / closed)
$filter = $_GET["status"] ?? "all";
if (!in_array($filter, array_merge(["all"], $allowedStatus), true)) {
    $filter = "all";
}
$page = max(1, (int) ($_GET["page"] ?? 1));

// ---------- 1. Button dabane par kya karna hai (POST) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $msg    = "error";
    $token  = $_POST["csrf"] ?? "";
    $action = $_POST["action"] ?? "";
    $id     = (int) ($_POST["id"] ?? 0);

    if (hash_equals($_SESSION["csrf"], $token) && $id > 0) {

        if ($action === "set_status") {
            $newStatus = $_POST["status"] ?? "";
            if (in_array($newStatus, $allowedStatus, true)) {
                $stmt = $pdo->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $msg = "status";
            }
        } elseif ($action === "delete") {
            $stmt = $pdo->prepare("DELETE FROM enquiries WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "deleted";
        }
    }

    // Wapas usi filter aur page par bhejo (refresh par dobara action na ho)
    $backFilter = $_POST["filter"] ?? "all";
    if (!in_array($backFilter, array_merge(["all"], $allowedStatus), true)) {
        $backFilter = "all";
    }
    $backPage = max(1, (int) ($_POST["page"] ?? 1));
    header("Location: enquiries.php?status=" . $backFilter . "&page=" . $backPage . "&msg=" . $msg);
    exit;
}

$messages = [
    "status"  => ["success", "Enquiry ka status badal diya."],
    "deleted" => ["success", "Enquiry delete ho gayi."],
    "error"   => ["error",   "Kuch gadbad ho gayi. Dobara try karo."],
];
$flash = $messages[$_GET["msg"] ?? ""] ?? null;

// ---------- 2. Har status ki ginti (tabs ke liye) ----------
$counts = ["all" => 0, "new" => 0, "contacted" => 0, "closed" => 0];
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM enquiries GROUP BY status") as $row) {
    $counts[$row["status"]] = (int) $row["c"];
    $counts["all"] += (int) $row["c"];
}

// ---------- 3. Pagination (ek page par 15 enquiries) ----------
$perPage = 15;
$total   = $counts[$filter];
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;

// ---------- 4. Enquiries database se lana ----------
$sql    = "SELECT * FROM enquiries";
$params = [];
if ($filter !== "all") {
    $sql     .= " WHERE status = ?";
    $params[] = $filter;
}
// LIMIT/OFFSET ke numbers pehle (int) bana diye hain, isliye seedha likhna safe hai
$sql .= " ORDER BY id DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

// ---------- 5. Page dikhana ----------
$pageTitle  = "Enquiries";
$activePage = "enquiries";
require __DIR__ . "/header.php";

$tabs = [
    "all"       => "All",
    "new"       => "New",
    "contacted" => "Contacted",
    "closed"    => "Closed",
];
?>

<div class="page-head">
  <h1 class="page-title">Enquiries <small>(<?php echo $counts["all"]; ?>)</small></h1>
</div>

<?php if ($flash): ?>
  <div class="alert <?php echo $flash[0]; ?>"><?php echo e($flash[1]); ?></div>
<?php endif; ?>

<!-- ===== Status tabs ===== -->
<div class="filter-tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a href="enquiries.php?status=<?php echo $key; ?>"
       class="tab-link <?php echo $filter === $key ? "active" : ""; ?>">
      <?php echo $label; ?> <span class="tab-count"><?php echo $counts[$key]; ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if (!$enquiries): ?>
    <p class="muted">Is filter me koi enquiry nahi hai.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact</th>
            <th>Requirement</th>
            <th>Area</th>
            <th>Message</th>
            <th>Date</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enquiries as $q): ?>
            <?php
              // Phone se sirf digits rakho (link banane ke liye)
              $digits  = preg_replace('/[^0-9]/', '', $q["phone"]);
              $waText  = "Hello " . $q["name"] . ", this is Aashiyana Realty. Thank you for your enquiry. How can we help you?";
              $waLink  = "https://wa.me/91" . $digits . "?text=" . rawurlencode($waText);
            ?>
            <tr>
              <td>
                <strong><?php echo e($q["name"]); ?></strong>
                <?php if ($q["email"]): ?>
                  <div class="sub"><?php echo e($q["email"]); ?></div>
                <?php endif; ?>
              </td>

              <td>
                <?php echo e($q["phone"]); ?>
                <div class="contact-links">
                  <a class="mini-btn" href="tel:+91<?php echo e($digits); ?>">Call</a>
                  <a class="mini-btn wa" href="<?php echo e($waLink); ?>" target="_blank" rel="noopener">WhatsApp</a>
                </div>
              </td>

              <td>
                <?php echo e($q["requirement"] ?: "-"); ?>
                <div class="sub"><?php echo e($q["budget"] ?: ""); ?></div>
              </td>

              <td><?php echo e($q["area"] ?: "-"); ?></td>

              <td>
                <?php if ($q["message"]): ?>
                  <details class="msg">
                    <summary>Dekho</summary>
                    <p><?php echo nl2br(e($q["message"])); ?></p>
                  </details>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>

              <td class="nowrap"><?php echo date("d M Y", strtotime($q["created_at"])); ?>
                <div class="sub"><?php echo date("h:i A", strtotime($q["created_at"])); ?></div>
              </td>

              <!-- Status badalna: dropdown chunte hi form apne aap submit ho jata hai -->
              <td>
                <form method="post" class="inline-form">
                  <input type="hidden" name="csrf" value="<?php echo e($_SESSION["csrf"]); ?>">
                  <input type="hidden" name="action" value="set_status">
                  <input type="hidden" name="id" value="<?php echo (int) $q["id"]; ?>">
                  <input type="hidden" name="filter" value="<?php echo e($filter); ?>">
                  <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                  <select name="status" class="status-select status-<?php echo e($q["status"]); ?>"
                          onchange="this.form.submit()">
                    <?php foreach ($allowedStatus as $st): ?>
                      <option value="<?php echo $st; ?>" <?php echo $q["status"] === $st ? "selected" : ""; ?>>
                        <?php echo ucfirst($st); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>

              <!-- Delete -->
              <td>
                <form method="post" class="inline-form"
                      onsubmit="return confirm('Ye enquiry delete karni hai? Ye wapas nahi aayegi.');">
                  <input type="hidden" name="csrf" value="<?php echo e($_SESSION["csrf"]); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int) $q["id"]; ?>">
                  <input type="hidden" name="filter" value="<?php echo e($filter); ?>">
                  <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
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
          <a href="enquiries.php?status=<?php echo e($filter); ?>&page=<?php echo $page - 1; ?>">&larr; Pehle</a>
        <?php endif; ?>
        <span>Page <?php echo $page; ?> of <?php echo $pages; ?></span>
        <?php if ($page < $pages): ?>
          <a href="enquiries.php?status=<?php echo e($filter); ?>&page=<?php echo $page + 1; ?>">Aage &rarr;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/footer.php"; ?>