<?php
// =========================================================
// admin/login.php - Admin login page
// =========================================================

session_start(); // session = server par yaad rakhna ki kaun login hai
require __DIR__ . "/../config/db.php";

// Pehle se login hai to seedha dashboard bhejo
if (!empty($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

// CSRF token: form sach me isi site se aaya hai ya nahi, ye check karne ke liye
if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token    = $_POST["csrf"] ?? "";
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!hash_equals($_SESSION["csrf"], $token)) {
        $error = "Session expire ho gaya. Page refresh karke dobara try karo.";
    } else {
        // Username se admin dhoondho (prepared statement = safe)
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // password_verify: likha hua password hash se match karta hai ya nahi
        if ($admin && password_verify($password, $admin["password_hash"])) {
            session_regenerate_id(true); // naya session id (security)
            $_SESSION["admin_id"]   = $admin["id"];
            $_SESSION["admin_user"] = $admin["username"];
            header("Location: dashboard.php");
            exit;
        } else {
            usleep(500000); // aadhe second ruko (baar-baar guess karne se rokta hai)
            $error = "Username ya password galat hai.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Aashiyana Realty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="login-page">
  <div class="login-card">
    <div class="brand">Aashiyana<span>.</span></div>
    <h1>Admin Login</h1>
    <p class="sub">Apne account se login karo</p>

    <?php if ($error): ?>
      <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" class="btn">Login</button>
    </form>

    <a href="../public/index.html" class="back">&larr; Website par wapas jao</a>
  </div>
</body>
</html>