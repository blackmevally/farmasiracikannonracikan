<?php
include("../config/db.php");

if (isset($_POST['tambah'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $conn->query("INSERT INTO loket (nama_loket) VALUES ('$nama')");
}
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM loket WHERE id=$id");
}
$lokets = $conn->query("SELECT * FROM loket ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Manajemen Loket</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>💼 Manajemen Loket</header>
<nav>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="suara.php">🔊 Suara</a>
  <a href="reset.php">♻️ Reset Harian</a>
  <a href="import.php">🗄️ Import DB</a>
</nav>

<div class="container">
<h2>Daftar Loket</h2>
<form method="POST">
  <label>Nama Loket Baru:</label>
  <input type="text" name="nama" required>
  <button type="submit" name="tambah">➕ Tambah Loket</button>
</form>

<table>
<tr><th>ID</th><th>Nama Loket</th><th>Aksi</th></tr>
<?php while ($r = $lokets->fetch_assoc()): ?>
<tr>
  <td><?= $r['id'] ?></td>
  <td><?= htmlspecialchars($r['nama_loket']) ?></td>
  <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus loket ini?')">🗑️ Hapus</a></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
