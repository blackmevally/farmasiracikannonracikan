<?php
include("../config/db.php");
$msg = "";

if (isset($_POST['import']) && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file']['tmp_name'];
    if (file_exists($file)) {
        $cmd = "\"C:\\xampp\\mysql\\bin\\mysql.exe\" -u root antrian_farmasi < \"$file\"";
        exec($cmd, $out, $status);
        $msg = $status === 0 ? "✅ Database berhasil diimport!" : "❌ Gagal import database.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Import Database ke MySQL</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>🗄️ Import Database ke MySQL</header>
<nav>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="loket.php">💼 Loket</a>
  <a href="suara.php">🔊 Suara</a>
  <a href="reset.php">♻️ Reset</a>
</nav>

<div class="container">
<h2>Import Database</h2>
<?php if ($msg): ?><div class="success"><?= $msg ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
  <label>Pilih file .SQL</label>
  <input type="file" name="sql_file" accept=".sql" required>
  <button type="submit" name="import">📤 Import Sekarang</button>
</form>
<p><b>Catatan:</b> Pastikan nama database <code>antrian_farmasi</code> sudah ada di MySQL.</p>
</div>
</body>
</html>
