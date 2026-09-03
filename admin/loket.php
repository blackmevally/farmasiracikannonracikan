<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['antrian_admin'])) {
    http_response_code(403);
    exit('Akses admin diperlukan.');
}

include("../config/db.php");
$msg = "";

if (!isset($_SESSION['loket_csrf'])) {
    $_SESSION['loket_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['loket_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Permintaan tidak valid.');
    }

    if (isset($_POST['tambah'])) {
        $nama = trim($_POST['nama'] ?? '');
        if ($nama === '') {
            $msg = 'Nama loket wajib diisi.';
        } else {
            $stmt = $conn->prepare('INSERT INTO loket (nama_loket) VALUES (?)');
            $stmt->bind_param('s', $nama);
            $msg = $stmt->execute() ? 'Loket berhasil ditambahkan.' : 'Gagal menambahkan loket.';
        }
    }

    if (isset($_POST['hapus'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $msg = 'ID loket tidak valid.';
        } else {
            $stmt = $conn->prepare('DELETE FROM loket WHERE id = ?');
            $stmt->bind_param('i', $id);
            $msg = $stmt->execute() ? 'Loket berhasil dihapus.' : 'Gagal menghapus loket.';
        }
    }
}

$lokets = $conn->query("SELECT id, nama_loket FROM loket ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<?php if ($msg): ?><div class="success"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="POST">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
  <label>Nama Loket Baru:</label>
  <input type="text" name="nama" maxlength="100" required>
  <button type="submit" name="tambah">➕ Tambah Loket</button>
</form>

<table>
<tr><th>ID</th><th>Nama Loket</th><th>Aksi</th></tr>
<?php while ($r = $lokets->fetch_assoc()): ?>
<tr>
  <td><?= (int)$r['id'] ?></td>
  <td><?= htmlspecialchars($r['nama_loket'], ENT_QUOTES, 'UTF-8') ?></td>
  <td>
    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus loket ini?');">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <button type="submit" name="hapus">🗑️ Hapus</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
