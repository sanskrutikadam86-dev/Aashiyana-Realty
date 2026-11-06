<?php
// =========================================================
// admin/dashboard.php - Admin ka home page
// Counts aur latest enquiries dikhata hai.
// =========================================================

$pageTitle  = "Dashboard";
$activePage = "dashboard";
require __DIR__ . "/header.php";   // login check + upar ka design

// ---------- Database se counts nikalna ----------
$totalProps    = (int) $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$featuredProps = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE is_featured = 1")->fetchColumn();
$totalEnq      = (int) $pdo->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
$newEnq        = (int) $pdo->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();

// Type ke hisaab se properties: buy kitni, rent kitni, commercial kitni
$typeCounts = ["buy" => 0, "rent" => 0, "commercial" => 0];
foreach ($pdo->query("SELECT type, COUNT(*) AS c FROM properties GROUP BY type") as $row) {
    $typeCounts[$row["type"]] = (int) $row["c"];
}

// Latest 5 enquiries
$latest = $pdo->query(
    "SELECT id, name, phone, requirement, area, status, created_at
     FROM enquiries ORDER BY id DESC LIMIT 5"
)->fetchAll();
?>

<h1 class="page-title">Dashboard</h1>

<!-- ===== 4 Stat cards ===== -->
<div class="stats-row">
  <div class="stat-card">
    <span class="label">Total Properties</span>
    <span class="num"><?php echo $totalProps; ?></span>
  </div>
  <div class="stat-card">
    <span class="label">Featured (Home page)</span>
    <span class="num"><?php echo $featuredProps; ?></span>
  </div>
  <div class="stat-card">
    <span class="label">Total Enquiries</span>
    <span class="num"><?php echo $totalEnq; ?></span>
  </div>
  <div class="stat-card highlight">
    <span class="label">New Enquiries</span>
    <span class="num"><?php echo $newEnq; ?></span>
  </div>
</div>

<!-- ===== Properties by type ===== -->
<div class="card">
  <h2 class="section-title">Properties by type</h2>
  <div class="pills">
    <span class="pill">Buy <strong><?php echo $typeCounts["buy"]; ?></strong></span>
    <span class="pill">Rent <strong><?php echo $typeCounts["rent"]; ?></strong></span>
    <span class="pill">Commercial <strong><?php echo $typeCounts["commercial"]; ?></strong></span>
  </div>
</div>

<!-- ===== Latest enquiries ===== -->
<div class="card">
  <div class="card-head">
    <h2 class="section-title">Latest Enquiries</h2>
    <a href="enquiries.php" class="link">View All &rarr;</a>
  </div>

  <?php if (!$latest): ?>
    <p class="muted">Abhi koi enquiry nahi aayi. Contact form se ek test enquiry bhejke dekho.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Requirement</th>
            <th>Area</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($latest as $e): ?>
            <tr>
              <td><?php echo htmlspecialchars($e["name"]); ?></td>
              <td><?php echo htmlspecialchars($e["phone"]); ?></td>
              <td><?php echo htmlspecialchars($e["requirement"]); ?></td>
              <td><?php echo htmlspecialchars($e["area"] ?: "-"); ?></td>
              <td>
                <span class="status status-<?php echo htmlspecialchars($e["status"]); ?>">
                  <?php echo htmlspecialchars($e["status"]); ?>
                </span>
              </td>
              <td><?php echo date("d M Y, h:i A", strtotime($e["created_at"])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/footer.php"; ?>