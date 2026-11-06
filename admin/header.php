<?php
// =========================================================
// admin/header.php - Har admin page ka upar wala hissa
//
// Use karne ka tarika (kisi bhi admin page ke sabse upar):
//     $pageTitle  = "Dashboard";
//     $activePage = "dashboard";
//     require __DIR__ . "/header.php";
// =========================================================

require_once __DIR__ . "/auth.php";            // login check (pehredar)
require_once __DIR__ . "/../config/db.php";    // database connection

$pageTitle  = $pageTitle  ?? "Admin";
$activePage = $activePage ?? "";

// Navigation me active tab ko highlight karne ke liye
function navClass($name, $active) {
    return $name === $active ? "active" : "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | Aashiyana Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">

  <header class="topbar">
    <div class="brand">Aashiyana<span>.</span> <small>Admin</small></div>
    <div class="topbar-right">
      <span>Hello, <strong><?php echo htmlspecialchars($_SESSION["admin_user"]); ?></strong></span>
      <a href="../public/index.html" target="_blank" class="link">View Site</a>
      <a href="logout.php" class="btn btn-small">Logout</a>
    </div>
  </header>

  <nav class="admin-nav">
    <a href="dashboard.php"  class="<?php echo navClass('dashboard',  $activePage); ?>">Dashboard</a>
    <a href="properties.php" class="<?php echo navClass('properties', $activePage); ?>">Properties</a>
    <a href="enquiries.php"  class="<?php echo navClass('enquiries',  $activePage); ?>">Enquiries</a>
        <a href="users.php"      class="<?php echo navClass('users',      $activePage); ?>">Users</a>
  </nav>

  <main class="content">