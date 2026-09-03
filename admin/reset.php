<?php
include("../config/db.php");
$msg = "";
if (isset($_POST['reset'])) {
    $conn->query("DELETE FROM antrian WHERE DATE(created_at)=CURDATE()");
    $msg = "Data antrian hari ini berhasil direset.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
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
<?php if ($msg): ?><div class="success"><?= $msg ?></div><?php endif; ?>
<form method="POST">
  <p>Semua data antrian hari ini akan dihapus. Lanjutkan?</p>
  <button type="submit" name="reset" style="background:#e74c3c;">⚠️ Reset Sekarang</button>
</form>
</div>
</body>
</html>
