<?php 
include("../config/db.php");

// Fungsi ambil nomor berikutnya per jenis
function nextNomor($conn, $jenis) {
    $today = date("Y-m-d");
    $result = $conn->query("SELECT no_antrian FROM antrian WHERE DATE(created_at)='$today' AND jenis='$jenis' ORDER BY id DESC LIMIT 1");
    $row = $result->fetch_assoc();
    $last = $row ? intval(substr($row['no_antrian'], 1)) : 0;
    $prefix = ($jenis == 'racikan') ? 'R' : 'O';
    return $prefix . str_pad($last + 1, 3, "0", STR_PAD_LEFT);
}

// Fungsi hitung antrian menunggu per jenis
function countWaiting($conn, $jenis) {
    $today = date("Y-m-d");
    $q = $conn->query("SELECT COUNT(*) AS jml FROM antrian WHERE status='waiting' AND jenis='$jenis' AND DATE(created_at)='$today'");
    $row = $q->fetch_assoc();
    return intval($row['jml']);
}

$nextObat = nextNomor($conn, 'obat');
$nextRacikan = nextNomor($conn, 'racikan');
$countObat = countWaiting($conn, 'obat');
$countRacikan = countWaiting($conn, 'racikan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ambil Nomor Antrian Farmasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
  background: linear-gradient(135deg, #27ae60, #1e8449);
  font-family: 'Segoe UI', Arial, sans-serif;
  color: white;
  margin: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center; /* ⬅️ tengah atas-bawah */
  align-items: center;     /* ⬅️ tengah kiri-kanan */
  overflow: hidden;
}

h1 {
  font-size: 52px;
  margin-bottom: 40px;
  text-shadow: 2px 3px 5px rgba(0,0,0,0.3);
}

.container {
  display: flex;
  justify-content: center;
  align-items: center; /* ⬅️ kotak sejajar tengah vertikal */
  gap: 80px;
  flex-wrap: wrap;
  width: 100%;
  max-width: 1600px;
}

.card {
  background: white;
  color: #27ae60;
  border-radius: 25px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.4);
  width: 600px;
  height: 550px;
  padding: 40px 30px;
  transition: 0.3s ease;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.card:hover {
  transform: scale(1.05);
  box-shadow: 0 15px 35px rgba(0,0,0,0.5);
}

.card h2 {
  margin: 0;
  color: #2c3e50;
  font-size: 36px;
  font-weight: bold;
}

.nomor {
  font-size: 190px;
  font-weight: bold;
  margin: 20px 0;
  border: 8px solid #f1c40f;
  border-radius: 20px;
  text-shadow: 4px 6px 10px rgba(0,0,0,0.2);
  width: 80%;
  text-align: center;
}

/* 🔵 RACIKAN */
.nomor.racikan {
  color: #00bfff;
  border-color: #00bfff;
  background: rgba(0, 191, 255, 0.08);
}

/* 🟡 OBAT */
.nomor.obat {
  color: #f1c40f;
  border-color: #f1c40f;
  background: rgba(241, 196, 15, 0.08);
}

.label {
  background: #e74c3c;
  color: white;
  font-size: 28px;
  font-weight: bold;
  border-radius: 12px;
  width: 80%;
  height: 90px;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  margin-top: 10px;
  transition: 0.2s ease;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
.label:hover {
  background: #c0392b;
  transform: scale(1.03);
}

.waiting {
  font-size: 22px;
  margin-top: 15px;
  color: #f39c12;
  transition: transform 0.3s ease;
}

.footer {
  position: absolute;
  bottom: 20px;
  font-size: 20px;
  opacity: 0.85;
  font-weight: 500;
}
</style>
</head>
<body>

<h1>Ambil Nomor Antrian Farmasi</h1>
<div class="container">

  <!-- 🟨 OBAT DI KIRI -->
  <div class="card" onclick="ambilNomor('obat')">
    <h2>ANTRIAN BUKAN RACIKAN</h2>
    <div class="nomor obat"><?= $nextObat ?></div>
    <div class="label">PENCET 1x UNTUK AMBIL NOMOR ANTRIAN</div>
    <div class="waiting" id="waitObat">Menunggu: <?= $countObat ?> pasien</div>
  </div>

  <!-- 🟦 RACIKAN DI KANAN -->
  <div class="card" onclick="ambilNomor('racikan')">
    <h2>ANTRIAN RACIKAN</h2>
    <div class="nomor racikan"><?= $nextRacikan ?></div>
    <div class="label">PENCET 1x UNTUK AMBIL NOMOR ANTRIAN</div>
    <div class="waiting" id="waitRacikan">Menunggu: <?= $countRacikan ?> pasien</div>
  </div>

</div>

<div class="footer">RSU Permata Medika Kebumen — Sistem Antrian Farmasi</div>

<!-- ========================= -->
<!--     SCRIPT JAVASCRIPT     -->
<!-- ========================= -->
<script>
function ambilNomor(jenis) {
  const form = document.createElement("form");
  form.method = "POST";
  form.action = "cetak.php";

  const inputJenis = document.createElement("input");
  inputJenis.type = "hidden";
  inputJenis.name = "jenis";
  inputJenis.value = jenis;
  form.appendChild(inputJenis);

  const inputNext = document.createElement("input");
  inputNext.type = "hidden";
  inputNext.name = "next";
  inputNext.value = jenis === "obat" ? "<?= $nextObat ?>" : "<?= $nextRacikan ?>";
  form.appendChild(inputNext);

  document.body.appendChild(form);
  form.submit();
}

// =========================
//  REALTIME AUTO REFRESH
// =========================
async function updateWaiting() {
  try {
    const res = await fetch("../config/get_waiting.php");
    const data = await res.json();

    const elObat = document.getElementById("waitObat");
    const elRacikan = document.getElementById("waitRacikan");

    elObat.textContent = "Menunggu: " + data.obat + " pasien";
    elRacikan.textContent = "Menunggu: " + data.racikan + " pasien";

    [elObat, elRacikan].forEach(el => {
      el.style.transform = "scale(1.15)";
      setTimeout(() => el.style.transform = "scale(1)", 400);
    });
  } catch (err) {
    console.error("Gagal update jumlah antrian:", err);
  }
}

updateWaiting();
setInterval(updateWaiting, 5000);
</script>

</body>
</html>
