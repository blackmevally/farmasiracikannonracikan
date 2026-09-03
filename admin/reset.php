<?php
declare(strict_types=1);

session_start();

// Temporary development gate: require an explicit admin session.
// Set $_SESSION['antrian_admin'] = true from the application's real login flow.
if (empty($_SESSION['antrian_admin'])) {
    http_response_code(403);
    exit('Akses admin diperlukan.');
}

include("../config/db.php");
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    if (!hash_equals($_SESSION['reset_csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Permintaan tidak valid.');
    }

    $stmt = $conn->prepare("DELETE FROM antrian WHERE DATE(created_at)=CURDATE()");
    if (!$stmt || !$stmt->execute()) {
        http_response_code(500);
        exit('Reset antrian gagal.');
    }
    $msg = "Data antrian hari ini berhasil direset.";
}

$_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Antrian Harian</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>♻️ Reset Antrian Harian</header>
<nav>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="loket.php">💼 Loket</a>
  <a href="suara.php">🔊 Suara</a>
  <a href="import.php">🗄️ Import DB</a>
</nav>

<div class="container">
<h2>Reset Antrian Hari Ini</h2>
<?php if ($msg): ?><div class="success"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="POST" onsubmit="return confirm('Semua data antrian hari ini akan dihapus. Lanjutkan?');">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['reset_csrf'], ENT_QUOTES, 'UTF-8') ?>">
  <p>Semua data antrian hari ini akan dihapus.</p>
  <button type="submit" name="reset" style="background:#e74c3c;">⚠️ Reset Sekarang</button>
</form>
</div>
</body>
</html>
