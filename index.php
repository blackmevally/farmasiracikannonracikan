<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🏥 Sistem Antrian Farmasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    background: linear-gradient(135deg, #27ae60, #1e8449);
    color: white;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
}
.container {
    text-align: center;
}
h1 {
    font-size: clamp(36px, 4vw, 72px);
    margin-bottom: 50px;
    text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}
h1 img {
    width: 60px;
    height: 60px;
}
.menu {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}
button {
    border: none;
    border-radius: 20px;
    padding: 40px 50px;
    font-size: clamp(22px, 3vw, 32px);
    font-weight: bold;
    color: white;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    transition: transform 0.25s ease, opacity 0.25s ease;
    width: 280px;
    height: 200px;
}
button:hover {
    transform: scale(1.05);
    opacity: 0.9;
}
.btn-ambil { background: linear-gradient(135deg, #f39c12, #e67e22); }
.btn-panggil { background: linear-gradient(135deg, #3498db, #2980b9); }
.btn-display { background: linear-gradient(135deg, #9b59b6, #8e44ad); }

.footer {
    position: absolute;
    bottom: 15px;
    width: 100%;
    text-align: center;
    font-size: clamp(14px, 1.5vw, 18px);
    opacity: 0.8;
    color: #ecf0f1;
}
</style>
</head>
<body>
    <div class="container">
        <h1>
            <img src="https://cdn-icons-png.flaticon.com/512/2967/2967506.png" alt="icon">
            Sistem Antrian Farmasi
        </h1>
        <div class="menu">
            <button class="btn-ambil" onclick="window.open('public/index.php', '_blank')">🧾 Ambil<br>Nomor<br>Antrian</button>
            <button class="btn-panggil" onclick="window.open('public/panggil.php', '_blank')">📢 Panel<br>Pemanggil</button>
            <button class="btn-display" onclick="bukaDisplay()">🖥️ Display<br>Antrian</button>
        </div>
    </div>
    <div class="footer">
        RSU Permata Medika Kebumen &copy; <?= date('Y') ?> – Sistem Antrian Farmasi
    </div>

<script>
// ✅ Membuka Display Antrian dalam jendela baru fullscreen-like
function bukaDisplay() {
    const width = screen.availWidth;
    const height = screen.availHeight;
    window.open(
        'display/stream.php',
        'displayWindow',
        `width=${width},height=${height},top=0,left=0,fullscreen=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no`
    );
}
</script>
<div style="position:fixed;bottom:20px;left:20px;">
  <a href="admin/dashboard.php" target="_blank"
     style="background:#27ae60;color:white;padding:12px 20px;
     border-radius:8px;text-decoration:none;font-weight:bold;">
     🔐 Admin Dashboard
  </a>
</div>
</body>
</html>
