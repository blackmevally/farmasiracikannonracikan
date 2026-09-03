<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['antrian_admin'])) {
    http_response_code(403);
    exit('Akses admin diperlukan.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Sistem Antrian Farmasi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #27ae60, #1e8449); color: white; text-align: center; margin: 0; padding: 40px; }
    h1 { font-size: 40px; margin-bottom: 20px; }
    .menu-container { display: flex; justify-content: center; flex-wrap: wrap; gap: 30px; margin-top: 50px; }
    .menu-box { background: rgba(255,255,255,.15); padding: 30px 40px; border-radius: 15px; width: 250px; box-shadow: 0 6px 15px rgba(0,0,0,.3); cursor: pointer; transition: .3s; }
    .menu-box:hover { background: rgba(255,255,255,.25); transform: scale(1.05); }
    .menu-box h2 { margin: 10px 0 5px; font-size: 24px; color: #fff; }
    .menu-box p { font-size: 14px; color: #e0e0e0; }
    .footer { margin-top: 60px; font-size: 14px; opacity: .8; }
    a { color: white; text-decoration: none; }
    .diagnosa-btn { display: inline-block; background: #00c06b; color: white; padding: 15px 30px; border-radius: 10px; font-weight: bold; margin-top: 40px; box-shadow: 0 5px 10px rgba(0,0,0,.3); transition: .3s; }
    .diagnosa-btn:hover { background: #009c59; transform: scale(1.05); }
  </style>
</head>
<body>
  <h1>🧠 Admin Dashboard Antrian Farmasi</h1>
  <p>Kelola sistem antrian, loket, suara, dan database dari sini.</p>

  <div class="menu-container">
    <a href="loket.php"><div class="menu-box" style="background:#3498db;"><h2>💼 Daftar Loket</h2><p>Tambah, ubah, atau hapus loket yang tersedia.</p></div></a>
    <a href="suara.php"><div class="menu-box" style="background:#9b59b6;"><h2>🔊 Pengaturan Suara</h2><p>Atur jenis suara, volume, dan bahasa.</p></div></a>
    <a href="reset.php"><div class="menu-box" style="background:#e67e22;"><h2>🔄 Reset Antrian</h2><p>Reset nomor antrian harian.</p></div></a>
  </div>

  <div class="diagnosa-btn" style="background:#555;cursor:not-allowed;" title="Fitur diagnostik lama sedang diamankan">
    🧠 Diagnosa Sistem (sementara dinonaktifkan)
  </div>

  <div class="footer">
    © <?= date('Y') ?> RSU Permata Medika Kebumen — Sistem Antrian Farmasi
  </div>
</body>
</html>
