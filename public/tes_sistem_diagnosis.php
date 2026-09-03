<?php
// ==============================================
// 🔧 Diagnosa & Auto-Fix Sistem Antrian Farmasi
// ==============================================

header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🧠 Diagnosa & Auto-Fix Sistem Antrian</title>
<style>
body {
  background-color: #111;
  color: #0f0;
  font-family: Consolas, monospace;
  padding: 20px;
}
h1 {
  color: #00ff66;
  font-family: "Segoe UI", sans-serif;
  font-size: 28px;
}
button {
  background: #00cc66;
  color: #fff;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  margin-right: 10px;
}
button:hover {
  background: #00ff88;
}
.log {
  background: #000;
  border-radius: 8px;
  padding: 15px;
  margin-top: 20px;
  color: #0f0;
  font-size: 14px;
  height: 350px;
  overflow-y: auto;
  white-space: pre-wrap;
  border: 1px solid #333;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
td, th {
  border: 1px solid #333;
  padding: 8px;
}
th {
  background: #111;
  color: #0ff;
  text-align: left;
}
tr:nth-child(even) { background: #181818; }
.green { color: #0f0; }
.yellow { color: #ff0; }
.red { color: #f33; }
.notice {
  background: rgba(255,255,255,0.1);
  border-radius: 8px;
  padding: 10px;
  margin-top: 10px;
}
</style>
</head>
<body>

<h1>🧠 Diagnosa & Auto-Fix Sistem Antrian</h1>
<p>Halaman ini akan memeriksa dan memperbaiki otomatis sistem:</p>
<ul>
  <li>🖨️ <b>Printer Thermal</b> (mapping, spooler, tes cetak)</li>
  <li>🔊 <b>Suara Display</b> (Text-to-Speech, izin autoplay, sinkronisasi)</li>
</ul>

<button onclick="tesPrinter()">🖨️ Tes & Auto-Fix Printer</button>
<button onclick="tesSuara()">🔊 Tes & Auto-Fix Suara</button>

<div id="hasilPrinter"></div>
<div id="hasilSuara"></div>
<div class="log" id="logOutput">💬 Log akan muncul di sini...</div>

<script>
// ==============================================
// 🔊 Tes & Auto-Fix Suara
// ==============================================
function tesSuara() {
  const log = document.getElementById('logOutput');
  log.innerText = "🎧 Memulai pemeriksaan suara display...\n";

  if (!('speechSynthesis' in window)) {
    log.innerText += "❌ Browser tidak mendukung Text-to-Speech.\nGunakan Chrome atau Edge versi terbaru.";
    return;
  }

  log.innerText += "✅ Browser mendukung TTS.\n🔍 Mengecek daftar suara...\n";
  const voices = speechSynthesis.getVoices();

  if (voices.length === 0) {
    log.innerText += "⚠️ Tidak ada suara terdeteksi. Tunggu 2 detik dan ulangi tes.\n";
    speechSynthesis.onvoiceschanged = tesSuara;
    return;
  }

  const voice = voices.find(v => v.lang.startsWith("id")) || voices[0];
  const utter = new SpeechSynthesisUtterance("Tes suara display sistem antrian. Semua berjalan dengan baik.");
  utter.lang = voice.lang;
  utter.voice = voice;
  utter.volume = 1;
  utter.rate = 1;

  speechSynthesis.speak(utter);
  log.innerText += "🗣️ Mengucapkan: '" + utter.text + "'\n";
  log.innerText += "🔁 Bahasa: " + utter.lang + " | Suara: " + voice.name + "\n";
  log.innerText += "✅ Tes suara selesai.\n";
}

// ==============================================
// 🖨️ Tes & Auto-Fix Printer Thermal
// ==============================================
function tesPrinter() {
  const log = document.getElementById('logOutput');
  log.innerText = "🖨️ Memulai diagnosa printer thermal...\n";

  fetch("tes_printer_diagnosis_autofix.php")
    .then(res => res.text())
    .then(html => {
      document.getElementById('hasilPrinter').innerHTML = html;
      log.innerText += "✅ Diagnosa printer selesai.\n";
    })
    .catch(err => {
      log.innerText += "❌ Gagal menjalankan diagnosa printer: " + err;
    });
}
</script>
</body>
</html>
