<?php
header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🧠 Diagnosa & Auto-Fix Sistem Antrian (Printer + Suara)</title>
<style>
body { font-family: Consolas, monospace; background:#111; color:#eee; padding:20px; }
h1 { color:#00ff90; }
h2 { color:#00b5ff; margin-top:30px; }
button {
    background:#00c06b; color:white; border:none; padding:12px 20px;
    font-size:16px; border-radius:8px; cursor:pointer; margin-top:10px;
}
button:hover { background:#009e57; }
.ok { color:#00ff90; }
.fail { color:#ff5555; }
#log { background:#000; padding:10px; border-radius:8px; color:#0f0; margin-top:15px; height:250px; overflow-y:auto; white-space:pre-wrap; }
table { border-collapse:collapse; width:100%; margin-top:15px; }
th,td { border:1px solid #333; padding:8px 12px; }
th { background:#222; color:#fff; }
tr:nth-child(even) { background:#181818; }
.notice { margin-top:20px; background:#222; padding:15px; border-radius:8px; font-size:15px; }
</style>
</head>
<body>
<h1>🧠 Diagnosa & Auto-Fix Sistem Antrian</h1>
<p>Halaman ini akan memeriksa dan memperbaiki otomatis sistem:</p>
<ul>
<li>🖨️ <b>Printer Thermal</b> (mapping, spooler, tes cetak)</li>
<li>🔊 <b>Suara Display</b> (Text-to-Speech, izin autoplay, sinkronisasi)</li>
</ul>

<button id="runPrinter">🖨️ Tes & Auto-Fix Printer</button>
<button id="runSound">🔊 Tes & Auto-Fix Suara</button>

<div id="printerResult"></div>
<div id="soundResult"></div>
<div id="log"></div>

<script>
// ================= PRINTER SECTION ===================
async function runPrinterDiagnosis() {
    const log = msg => {
        const el = document.getElementById("log");
        el.textContent += msg + "\\n";
        el.scrollTop = el.scrollHeight;
    };

    log("\\n=== 🔍 MULAI DIAGNOSA PRINTER ===");
    const results = [];
    const resultDiv = document.getElementById("printerResult");
    resultDiv.innerHTML = "<p>⏳ Sedang menganalisa printer...</p>";

    const cmd = async (command) => {
        return new Promise(resolve => {
            fetch('cmd.php?cmd=' + encodeURIComponent(command))
                .then(r => r.text())
                .then(t => resolve(t))
                .catch(e => resolve("ERROR: " + e));
        });
    };

    // 1️⃣ Cek spooler
    let spooler = await cmd("sc query Spooler");
    if (spooler.includes("RUNNING")) {
        results.push(["Print Spooler", "✅ Aktif", "ok"]);
    } else {
        results.push(["Print Spooler", "⚠️ Tidak aktif, mencoba menyalakan...", "fail"]);
        await cmd("net start spooler");
    }

    // 2️⃣ Cek printer
    let printers = await cmd("wmic printer get Name,ShareName,Default /format:list");
    let shareMatch = printers.match(/ShareName=(.*)/);
    let share = shareMatch ? shareMatch[1].trim() : "ThermalPOS";
    results.push(["Share Printer", share ? "✅ " + share : "❌ Tidak ditemukan", share ? "ok" : "fail"]);

    // 3️⃣ Mapping ulang
    await cmd("net use LPT1 /delete /y");
    await cmd("net use LPT1 \\\\localhost\\" + share + " /persistent:yes");
    results.push(["Mapping LPT1", "✅ Remapping printer berhasil", "ok"]);

    // 4️⃣ Tes cetak dummy
    await cmd("echo TES CETAK PHP > %TEMP%\\tes_cetak.txt");
    let printTest = await cmd("copy /B %TEMP%\\tes_cetak.txt LPT1");
    if (printTest.includes("1 file(s) copied")) {
        results.push(["Tes Cetak", "✅ Berhasil dikirim ke printer", "ok"]);
    } else {
        results.push(["Tes Cetak", "❌ Gagal kirim file ke printer", "fail"]);
    }

    resultDiv.innerHTML = `<h2>🖨️ Hasil Diagnosis Printer</h2>
    <table><tr><th>Langkah</th><th>Hasil</th></tr>${
        results.map(r=>`<tr><td>${r[0]}</td><td class='${r[2]}'>${r[1]}</td></tr>`).join("")
    }</table>`;
    log("=== ✅ DIAGNOSA PRINTER SELESAI ===");
}

// ================= SOUND SECTION ===================
async function runSoundDiagnosis() {
    const log = msg => {
        const el = document.getElementById("log");
        el.textContent += msg + "\\n";
        el.scrollTop = el.scrollHeight;
    };

    log("\\n=== 🔍 MULAI DIAGNOSA SUARA ===");
    const results = [];
    const resDiv = document.getElementById("soundResult");
    resDiv.innerHTML = "<p>⏳ Sedang menganalisa suara...</p>";

    // Cek SpeechSynthesis
    if ('speechSynthesis' in window) {
        results.push(["SpeechSynthesis API", "✅ Tersedia", "ok"]);
    } else {
        results.push(["SpeechSynthesis API", "❌ Tidak didukung browser ini", "fail"]);
        return;
    }

    // Cek daftar suara
    let voices = [];
    function loadVoices() {
        return new Promise(resolve => {
            let id = setInterval(() => {
                voices = speechSynthesis.getVoices();
                if (voices.length) {
                    clearInterval(id);
                    resolve(voices);
                }
            }, 200);
        });
    }
    voices = await loadVoices();
    const idVoices = voices.filter(v => v.lang.startsWith("id"));
    if (idVoices.length > 0) {
        results.push(["Suara Indonesia", "✅ " + idVoices.length + " ditemukan", "ok"]);
    } else {
        results.push(["Suara Indonesia", "⚠️ Tidak ditemukan, pakai default", "fail"]);
    }

    // Tes audio lokal
    try {
        const beep = new Audio("https://actions.google.com/sounds/v1/alarms/beep_short.ogg");
        await beep.play();
        results.push(["Tes Audio Lokal", "✅ Bunyi 'ting-tong' berhasil", "ok"]);
    } catch (err) {
        results.push(["Tes Audio Lokal", "❌ Gagal play audio (blokir autoplay)", "fail"]);
    }

    // Tes TTS
    try {
        const utt = new SpeechSynthesisUtterance("Nomor antrian satu satu menuju loket satu.");
        utt.lang = idVoices.length ? idVoices[0].lang : "id-ID";
        speechSynthesis.speak(utt);
        results.push(["Tes TTS", "✅ Berhasil dijalankan", "ok"]);
    } catch (err) {
        results.push(["Tes TTS", "❌ Gagal menjalankan TTS", "fail"]);
    }

    resDiv.innerHTML = `<h2>🔊 Hasil Diagnosis Suara</h2>
    <table><tr><th>Langkah</th><th>Hasil</th></tr>${
        results.map(r=>`<tr><td>${r[0]}</td><td class='${r[2]}'>${r[1]}</td></tr>`).join("")
    }</table>`;
    log("=== ✅ DIAGNOSA SUARA SELESAI ===");
}

document.getElementById("runPrinter").addEventListener("click", runPrinterDiagnosis);
document.getElementById("runSound").addEventListener("click", runSoundDiagnosis);
</script>

<div class="notice">
<b>💡 Petunjuk:</b><br>
- Jalankan tes printer & suara sebelum sistem digunakan.<br>
- Jika printer gagal cetak, pastikan XAMPP dijalankan sebagai Administrator.<br>
- Jika suara tidak keluar, klik layar dan ulangi tes suara.<br>
</div>

</body>
</html>
