<?php include("../config/db.php"); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Panel Pemanggil Antrian Farmasi</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: linear-gradient(135deg, #27ae60, #1e8449);
  color: white;
  text-align: center;
  padding: 40px;
}
h1 {
  font-size: 38px;
  margin-bottom: 20px;
}
select {
  font-size: 20px;
  padding: 8px 20px;
  margin: 10px;
  border-radius: 10px;
}
button {
  font-size: 22px;
  padding: 14px 40px;
  margin: 10px;
  border: none;
  border-radius: 10px;
  background: #f1c40f;
  color: #2c3e50;
  cursor: pointer;
  font-weight: bold;
  transition: 0.3s;
}
button:hover { background: #f39c12; }
.info {
  margin-top: 20px;
  font-size: 22px;
}
table {
  margin: 30px auto;
  width: 75%;
  border-collapse: collapse;
  background: rgba(0,0,0,0.2);
  border-radius: 10px;
  overflow: hidden;
}
th, td {
  border: 1px solid rgba(255,255,255,0.3);
  padding: 10px;
}
th { background: rgba(0,0,0,0.4); }
td { font-size: 20px; }
.highlight {
  background: rgba(241,196,15,0.25) !important;
  color: #fff;
  font-weight: bold;
}
.recent-called { 
  box-shadow: 0 0 12px rgba(241,196,15,0.6); 
  animation: pulse 1s linear 3; 
}
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.02); }
  100% { transform: scale(1); }
}
</style>
</head>
<body>
<h1>Panel Pemanggil Antrian Farmasi</h1>

<div>
  <label for="jenis">Jenis Antrian:</label>
  <select id="jenis">
    <option value="all">Semua</option>
    <option value="obat">Obat</option>
    <option value="racikan">Racikan</option>
  </select>

  <label for="loket">Pilih Loket:</label>
  <select id="loket">
    <option value="1">Loket 1</option>
    <option value="2">Loket 2</option>
    <option value="3">Loket 3</option>
  </select>

  <button id="btnPanggil">📢 Panggil Berikutnya</button>
  <button id="btnUlang">🔁 Panggil Ulang</button>
</div>

<p id="notif" class="info"></p>
<div class="info">Menunggu: <b id="waitingCount">0</b> pasien</div>

<h2>Daftar Antrian Menunggu</h2>
<table>
  <thead><tr><th>No Antrian</th><th>Jenis</th><th>Waktu Ambil</th></tr></thead>
  <tbody id="waitingList"><tr><td colspan="3">Memuat...</td></tr></tbody>
</table>

<audio id="sound" src="../tingtong.mp3"></audio>

<script>
const notif = document.getElementById("notif");
const sound = document.getElementById("sound");
const jenisSel = document.getElementById("jenis");
const loketSel = document.getElementById("loket");
let lastCalled = null;

// =============================
// Fungsi: Load daftar antrian
// =============================
async function loadWaiting() {
  const jenis = jenisSel.value;
  const res = await fetch("waiting_data.php?jenis=" + encodeURIComponent(jenis));
  const data = await res.json();

  const tbody = document.getElementById("waitingList");
  tbody.innerHTML = "";
  document.getElementById("waitingCount").textContent = data.length;

  if (data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="3">Tidak ada antrian menunggu</td></tr>`;
    return;
  }

  data.forEach(row => {
    // pastikan jenis benar (obat/racikan)
    let jenisRow = row.jenis;
    if (!jenisRow || (jenisRow !== 'obat' && jenisRow !== 'racikan')) {
      const p = (row.no_antrian || '').toUpperCase().charAt(0);
      jenisRow = (p === 'R') ? 'racikan' : 'obat';
    }

    const waktu = row.waktu_ambil || row.created_at || '-';
    const tr = document.createElement("tr");
    tr.dataset.no = row.no_antrian;
    tr.dataset.jenis = jenisRow;
    tr.innerHTML = `
      <td>${row.no_antrian}</td>
      <td>${jenisRow.toUpperCase()}</td>
      <td>${waktu}</td>
    `;

    if (jenisRow === "racikan") tr.style.backgroundColor = "rgba(52,152,219,0.15)";

    if (lastCalled && lastCalled.no === row.no_antrian && lastCalled.jenis === jenisRow) {
      tr.classList.add("highlight");
    }

    tbody.appendChild(tr);
  });
}

// =============================
// Fungsi bantu loket
// =============================
function formatLoket(loket) {
  let teks = loket ? loket.toString().trim() : "";
  if (!teks.toLowerCase().includes("loket")) teks = "Loket " + teks;
  return teks;
}

// =============================
// Fungsi: Panggil berikutnya
// =============================
async function panggilNext() {
  const jenis = jenisSel.value === 'all' ? 'obat' : jenisSel.value;
  const loket = loketSel.value;
  notif.textContent = "⏳ Memanggil nomor berikutnya...";
  notif.style.color = "#f1c40f";

  const res = await fetch("panggil_next.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ loket, jenis })
  });
  const data = await res.json();

  if (data.status === "ok") {
    lastCalled = { no: data.no, jenis: data.jenis };
    const loketDisplay = formatLoket(data.loket);
    notif.innerHTML = `✅ Nomor <b>${data.no}</b> (${data.jenis}) dipanggil ke <b>${loketDisplay}</b>`;
    notif.style.color = "#fff";
    sound.currentTime = 0; sound.play().catch(()=>{});

    await fetch("../display/log_panggilan.txt", {
      method: "POST",
      body: JSON.stringify({
        no: data.no,
        loket: loketDisplay,
        jenis: data.jenis,
        time: Date.now()
      })
    }).catch(()=>{});

    await loadWaiting();
    flashHighlightRow(data.no, data.jenis);
  } else {
    notif.textContent = "⚠️ Tidak ada antrian menunggu untuk jenis ini.";
    notif.style.color = "orange";
  }
}

// =============================
// Fungsi: Panggil ulang
// =============================
async function panggilUlang() {
  const jenis = jenisSel.value === 'all' ? 'obat' : jenisSel.value;
  const loket = loketSel.value;
  notif.textContent = "🔁 Memanggil ulang...";
  notif.style.color = "#f1c40f";

  const res = await fetch("panggil_ulang.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ loket, jenis })
  });
  const data = await res.json();

  if (data.status === "ok") {
    lastCalled = { no: data.no, jenis: data.jenis };
    const loketDisplay = formatLoket(data.loket);
    notif.innerHTML = `🔁 Nomor <b>${data.no}</b> (${data.jenis}) dipanggil ulang ke <b>${loketDisplay}</b>`;
    notif.style.color = "#fff";
    sound.currentTime = 0; sound.play().catch(()=>{});

    await fetch("../display/log_panggilan.txt", {
      method: "POST",
      body: JSON.stringify({
        no: data.no,
        loket: loketDisplay,
        jenis: data.jenis,
        time: Date.now()
      })
    }).catch(()=>{});

    await loadWaiting();
    flashHighlightRow(data.no, data.jenis);
  } else {
    notif.textContent = "⚠️ Belum ada nomor yang dipanggil untuk loket ini.";
    notif.style.color = "orange";
  }
}

// =============================
// Highlight baris panggilan
// =============================
function flashHighlightRow(no, jenis) {
  const rows = document.querySelectorAll("#waitingList tr");
  rows.forEach(r => r.classList.remove("recent-called"));
  for (const r of rows) {
    if (r.dataset.no === no && r.dataset.jenis === jenis) {
      r.classList.add("recent-called");
      setTimeout(()=> r.classList.remove("recent-called"), 4000);
      break;
    }
  }
}

// =============================
// Listener
// =============================
document.getElementById('btnPanggil').addEventListener('click', panggilNext);
document.getElementById('btnUlang').addEventListener('click', panggilUlang);
jenisSel.addEventListener('change', loadWaiting);
loketSel.addEventListener('change', ()=>{});

setInterval(loadWaiting, 3000);
loadWaiting();
</script>
</body>
</html>
