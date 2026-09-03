<?php
header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🔊 Diagnosa & Perbaikan Otomatis Suara Antrian</title>
<style>
body { font-family: Consolas, monospace; background:#111; color:#eee; padding:20px; }
h1 { color:#00ff90; }
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
<h1>🎧 Diagnosa & Auto-Fix Suara Display Antrian</h1>

<p>Halaman ini akan menganalisa dan memperbaiki otomatis masalah suara panggilan (TTS) pada <b>display antrian</b>.</p>
<p>Langkah perbaikan meliputi:</p>
<ul>
<li>✅ Cek ketersediaan <b>SpeechSynthesis API</b></li>
<li>✅ Cek daftar suara bahasa Indonesia</li>
<li>✅ Cek izin autoplay suara</li>
<li>✅ Tes suara “ting-tong”</li>
<li>✅ Tes TTS: “Nomor antrian satu satu menuju loket satu”</li>
<li>✅ Jika gagal → perbaiki izin audio & aktifkan suara otomatis</li>
</ul>

<button id="runTest">🚀 Jalankan Tes & Auto-Fix</button>
<button id="forceEnable">🎯 Paksa Aktifkan Suara Sekarang</button>

<div id="results"></div>
<div id="log"></div>

<script>
const log = msg => {
    const el = document.getElementById("log");
    el.textContent += msg + "\\n";
    el.scrollTop = el.scrollHeight;
};

// ✅ Auto play unlock helper
async function unlockAudio() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        if (ctx.state === "suspended") {
            await ctx.resume();
            log("AudioContext diaktifkan kembali ✅");
        }
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        gain.gain.value = 0.05;
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    } catch (err) {
        log("Gagal aktifkan audio context: " + err);
    }
}

async function testTTS() {
    const results = [];
    log("=== MULAI TES & AUTO-FIX SUARA ===");

    // 1️⃣ Cek SpeechSynthesis API
    if ('speechSynthesis' in window) {
        results.push(["SpeechSynthesis API", "✅ Tersedia", "ok"]);
    } else {
        results.push(["SpeechSynthesis API", "❌ Tidak didukung di browser ini", "fail"]);
        log("ERROR: Browser tidak mendukung SpeechSynthesis API");
        return results;
    }

    // 2️⃣ Cek daftar suara
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
        log("Ditemukan suara Indonesia: " + idVoices.map(v=>v.name).join(", "));
    } else {
        results.push(["Suara Indonesia", "⚠️ Tidak ditemukan, pakai default", "fail"]);
    }

    // 3️⃣ Cek izin audio / autoplay
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        if (ctx.state !== "running") await ctx.resume();
        results.push(["Autoplay Suara", "✅ Diizinkan", "ok"]);
        log("Autoplay OK, AudioContext: " + ctx.state);
    } catch (e) {
        results.push(["Autoplay Suara", "⚠️ Diblokir (klik layar untuk aktifkan)", "fail"]);
        log("AudioContext gagal dijalankan: " + e);
    }

    // 4️⃣ Tes audio lokal
    try {
        const beep = new Audio("https://actions.google.com/sounds/v1/alarms/beep_short.ogg");
        await beep.play();
        results.push(["Tes Audio Lokal", "✅ Bunyi 'ting-tong' berhasil", "ok"]);
    } catch (err) {
        results.push(["Tes Audio Lokal", "❌ Gagal play audio (blokir autoplay)", "fail"]);
        log("Audio gagal: " + err);
    }

    // 5️⃣ Tes TTS
    try {
        const utt = new SpeechSynthesisUtterance("Nomor antrian satu satu menuju loket satu.");
        utt.lang = idVoices.length ? idVoices[0].lang : "id-ID";
        utt.voice = idVoices[0] || null;
        utt.rate = 1;
        speechSynthesis.speak(utt);
        results.push(["Tes TTS", "✅ Kalimat panggilan berhasil dijalankan", "ok"]);
        log("TTS berhasil dijalankan.");
    } catch (e) {
        results.push(["Tes TTS", "❌ Gagal jalankan TTS: " + e.message, "fail"]);
        log("TTS error: " + e);
    }

    return results;
}

async function runDiagnostic() {
    const resDiv = document.getElementById("results");
    resDiv.innerHTML = "<p>⏳ Sedang menganalisa dan memperbaiki...</p>";
    await unlockAudio();
    const results = await testTTS();

    resDiv.innerHTML = `<table><tr><th>Langkah</th><th>Hasil</th></tr>${
        results.map(r=>`<tr><td>${r[0]}</td><td class='${r[2]}'>${r[1]}</td></tr>`).join("")
    }</table>`;
    log("=== SELESAI ===");
}

document.getElementById("runTest").addEventListener("click", runDiagnostic);

// 🔁 Paksa aktifkan suara manual
document.getElementById("forceEnable").addEventListener("click", async () => {
    log("Menjalankan perbaikan audio manual...");
    await unlockAudio();
    try {
        const sound = new Audio("https://actions.google.com/sounds/v1/cartoon/wood_plank_flicks.ogg");
        await sound.play();
        log("✅ Suara diaktifkan secara manual. Sekarang TTS akan berfungsi di display.php");
        alert("✅ Suara berhasil diaktifkan! Sekarang buka ulang display.php untuk mengetes panggilan.");
    } catch (err) {
        log("❌ Gagal aktifkan suara: " + err);
        alert("❌ Gagal aktifkan suara. Klik layar & coba lagi.");
    }
});
</script>

<div class="notice">
<b>💡 Tips:</b><br>
- Jalankan halaman ini sekali di setiap browser <b>sebelum membuka display.php</b>.<br>
- Jika Chrome/Edge masih blokir, klik layar sekali atau tekan tombol "🎯 Paksa Aktifkan Suara Sekarang".<br>
- Suara akan aktif otomatis di <b>SpeechSynthesisUtterance</b> semua halaman (termasuk display).<br>
- Gunakan Chrome versi terbaru untuk hasil terbaik.<br>
</div>

</body>
</html>
