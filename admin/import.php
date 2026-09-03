<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['antrian_admin'])) {
    http_response_code(403);
    exit('Akses admin diperlukan.');
}

// Database import is intentionally disabled from the web UI.
// Use phpMyAdmin/MySQL CLI on the server with an authenticated operator account.
http_response_code(410);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Database</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>🗄️ Import Database</header>
<nav>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="loket.php">💼 Loket</a>
  <a href="suara.php">🔊 Suara</a>
  <a href="reset.php">♻️ Reset</a>
</nav>
<div class="container">
  <h2>Import Database Dinonaktifkan</h2>
  <p>Import SQL melalui browser dinonaktifkan untuk mencegah eksekusi perintah database/OS dari endpoint web.</p>
  <p>Gunakan phpMyAdmin atau MySQL CLI langsung pada server dengan akun operator yang terautentikasi.</p>
</div>
</body>
</html>
