<?php
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Display Antrian Farmasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(180deg, #27ae60, #1e8449);
    color: white;
    text-align: center;
    margin: 0;
    overflow: hidden;
}

/* Background RS */
body::before {
    content: "";
    background: url("../config/assets/bg_rsu.png") no-repeat center center fixed;
    background-size: cover;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.12;
    z-index: -1;
}

.header {
    background: rgba(0,0,0,0.3);
    padding: 20px;
    font-size: 42px;
    font-weight: bold;
    letter-spacing: 2px;
}

.status {
    position: fixed;
    top: 10px;
    right: 20px;
    font-size: 16px;
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
}
.status.online { background: #2ecc71; }
.status.offline { background: #e74c3c; }

.main {
    display: grid;
    grid-template-columns: 70% 30%;
    height: calc(100vh - 270px);
    gap: 10px;
    padding: 20px;
    box-sizing: border-box;
}

/* Area video kiri */
.left {
    display: flex;
    justify-content: center;
    align-items: center;
    background: black;
    overflow: hidden;
    border-radius: 12px;
}
.left video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: black;
}

/* Panel kanan */
.right {
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    align-items: center;
}

.panel-box {
    background: rgba(0,0,0,0.25);
    border-radius: 20px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.3);
    padding: 30px 20px 40px 20px;
    width: 90%;
    transition: transform 0.2s ease;
}
.panel-box:hover { transform: scale(1.02); }

.panel-title {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 10px;
    text-transform: uppercase;
    color: #ecf0f1;
}

#noObat, #noRacikan {
    font-size: 160px;
    font-weight: bold;
    text-shadow: 4px 4px 10px rgba(0,0,0,0.4);
    margin: 0;
}
#noObat { color: #f1c40f; }
#noRacikan { color: #00bfff; }

.label {
    background: #e74c3c;
    color: white;
    display: inline-block;
    margin-top: 15px;
    padding: 10px 25px;
    border-radius: 10px;
    font-size: 24px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: fit-content;
    min-width: 220px;
}

.footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    display: flex;
    justify-content: space-around;
    background: rgba(0,0,0,0.25);
    padding: 20px 0;
}
.loket-box {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 15px;
    width: 25%;
    box-shadow: 0 5px 10px rgba(0,0,0,0.3);
}
.loket-box h2 {
    font-size: 26px;
    margin: 0;
    font-weight: 600;
    color: #ecf0f1;
}
.loket-box p {
    font-size: 46px;
    margin: 10px 0 0 0;
    font-weight: bold;
    color: #f1c40f;
}

#btnSuara {
    position: fixed;
    top: 20px; left: 20px;
    background: #f1c40f;
    border: none;
    color: #2c3e50;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    font-weight: bold;
    z-index: 999;
}

/* Animasi heartbeat */
@keyframes heartbeat {
    0% { transform: scale(1); box-shadow: 0 0 0px rgba(255,255,255,0); }
    25% { transform: scale(1.06); box-shadow: 0 0 25px var(--glow-color); }
    50% { transform: scale(1); box-shadow: 0 0 0px rgba(255,255,255,0); }
    75% { transform: scale(1.06); box-shadow: 0 0 25px var(--glow-color); }
    100% { transform: scale(1); box-shadow: 0 0 0px rgba(255,255,255,0); }
}
.heartbeat {
    animation: heartbeat 1.3s ease-in-out infinite;
}
</style>
</head>
<body>
<button id="btnSuara">🔊 Aktifkan Suara</button>

<div class="header">ANTRIAN FARMASI PENGAMBILAN OBAT</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>

<div class="main">
    <!-- KIRI: VIDEO STREAM -->
    <div class="left">
        <video id="tvStream" autoplay muted playsinline controls></video>
        <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
        <script>
        let video;
        document.addEventListener("DOMContentLoaded", function () {
            video = document.getElementById("tvStream");
            const streamURL = "http://192.168.9.116:8080/live/stream.m3u8"; // sesuaikan IP server

            if (Hls.isSupported()) {
                const hls = new Hls({ autoStartLoad: true, enableWorker: true, lowLatencyMode: true });
                hls.loadSource(streamURL);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => video.play().catch(()=>{}));
            } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
                video.src = streamURL;
                video.addEventListener("loadedmetadata", () => video.play().catch(()=>{}));
            }
        });
        </script>
    </div>

    <!-- KANAN: PANEL ANTRIAN -->
    <div class="right">
        <div class="panel-box" id="boxObat">
            <div class="panel-title">Antrian Bukan Racikan</div>
            <div id="noObat">---</div>
            <div class="label" id="loketObat">Menunggu panggilan...</div>
        </div>
        <div class="panel-box" id="boxRacikan">
            <div class="panel-title">Antrian Racikan</div>
            <div id="noRacikan">---</div>
            <div class="label" id="loketRacikan">Menunggu panggilan...</div>
        </div>
    </div>
</div>

<div class="footer">
    <div class="loket-box"><h2>Loket 3</h2><p id="loket3">---</p></div>
    <div class="loket-box"><h2>Loket 2</h2><p id="loket2">---</p></div>
    <div class="loket-box"><h2>Loket 1</h2><p id="loket1">---</p></div>
</div>

<audio id="bell" src="../tingtong.mp3"></audio>

<script>
const bell = document.getElementById("bell");
const statusEl = document.getElementById("status");
let suaraConfig = null;
let voicesReady = false;

// Inisialisasi suara
speechSynthesis.onvoiceschanged = () => { voicesReady = true; };

// Ambil pengaturan suara
async function loadSuaraConfig() {
    try {
        const res = await fetch("../config/get_suara.php");
        suaraConfig = await res.json();
    } catch {
        suaraConfig = {
            template_obat: "Panggilan pengambilan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
            template_racikan: "Panggilan pengambilan obat racikan, nomor antrian {nomor}, silakan menuju ke {loket}",
            voice: "Google Bahasa Indonesia",
            lang: "id-ID",
            volume: 1,
            rate: 1
        };
    }
}

// Konversi angka ke kata
function angkaKeKata(angka) {
    const s = ["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan"];
    const b = ["sepuluh","sebelas","dua belas","tiga belas","empat belas","lima belas","enam belas","tujuh belas","delapan belas","sembilan belas"];
    const p = ["","", "dua puluh","tiga puluh","empat puluh","lima puluh","enam puluh","tujuh puluh","delapan puluh","sembilan puluh"];
    let n = parseInt(angka);
    if(isNaN(n)) return angka;
    if(n<10) return s[n];
    if(n<20) return b[n-10];
    if(n<100) return p[Math.floor(n/10)] + (n%10?" "+s[n%10]:"");
    return angka;
}

// Efek heartbeat
function startHeartbeat(boxId, color) {
    const el = document.getElementById(boxId);
    el.style.setProperty("--glow-color", color);
    el.classList.add("heartbeat");
}
function stopHeartbeat(boxId) {
    const el = document.getElementById(boxId);
    el.classList.remove("heartbeat");
}

// Fungsi TTS dengan Ducking Video
function playVoiceWithEffect(template, nomor, loket, boxId, color) {
    if (!window.speechSynthesis || !suaraConfig) return;
    if (!voicesReady) {
        setTimeout(() => playVoiceWithEffect(template, nomor, loket, boxId, color), 300);
        return;
    }

    let nomorVoice = nomor.toString().trim();
    const match = nomorVoice.match(/^([A-Za-z]+)?(\d+)$/);
    if (match) {
        const prefix = match[1] ? match[1].toUpperCase() + " " : "";
        const angkaPart = match[2].replace(/^0+/, "");
        const kata = angkaKeKata(angkaPart);
        nomorVoice = prefix + kata;
    }

    let loketVoice = loket.toString().trim();
    if (/^\d+$/.test(loketVoice)) loketVoice = "loket " + angkaKeKata(loketVoice);

    const teks = template.replace("{nomor}", nomorVoice).replace("{loket}", loketVoice);
    const utter = new SpeechSynthesisUtterance(teks);
    utter.lang = suaraConfig.lang || "id-ID";
    utter.volume = suaraConfig.volume ?? 1;
    utter.rate = suaraConfig.rate ?? 1;

    const voices = speechSynthesis.getVoices();
    const selected = voices.find(v => v.name === suaraConfig.voice);
    if (selected) utter.voice = selected;

    // ✅ Fitur Ducking Audio
    utter.onstart = () => {
        startHeartbeat(boxId, color);
        if (video && !video.paused) video.pause(); // pause video saat TTS
    };
    utter.onend = () => {
        stopHeartbeat(boxId);
        if (video && video.paused) video.play().catch(()=>{}); // lanjutkan video setelah TTS
    };

    speechSynthesis.cancel();
    speechSynthesis.speak(utter);
}

// SSE
function updateKoneksi(ok) {
    statusEl.className = "status " + (ok ? "online" : "offline");
    statusEl.innerHTML = ok ? "🟢 Terhubung ke Server" : "🔴 Putus koneksi";
}

async function startSSE() {
    await loadSuaraConfig();
    const sse = new EventSource("event_stream.php");
    sse.onopen = () => updateKoneksi(true);
    sse.onerror = () => updateKoneksi(false);
    sse.addEventListener("ping", () => updateKoneksi(true));

    let lastTime = 0;
    sse.addEventListener("update", e => {
        updateKoneksi(true);
        const d = JSON.parse(e.data);
        if (!d.no || d.time === lastTime) return;
        lastTime = d.time;

        const jenis = (d.jenis || "").toLowerCase();
        const isRacikan = jenis === "racikan" || (d.no && d.no.startsWith("R"));
        const loket = d.loket || "1";
        const nomor = d.no;

        bell.play().catch(() => {});
        bell.onended = () => {
            if (isRacikan) {
                document.getElementById("noRacikan").textContent = nomor;
                document.getElementById("loketRacikan").textContent = "Menuju Loket " + loket;
                playVoiceWithEffect(suaraConfig.template_racikan, nomor, loket, "boxRacikan", "#00bfff");
            } else {
                document.getElementById("noObat").textContent = nomor;
                document.getElementById("loketObat").textContent = "Menuju Loket " + loket;
                playVoiceWithEffect(suaraConfig.template_obat, nomor, loket, "boxObat", "#f1c40f");
            }
        };
    });
}

// Tombol aktifkan suara
document.getElementById("btnSuara").addEventListener("click", () => {
    const dummy = new SpeechSynthesisUtterance("Inisialisasi suara...");
    dummy.lang = "id-ID";
    speechSynthesis.speak(dummy);

    setTimeout(() => {
        const u = new SpeechSynthesisUtterance("Suara aktif. Display siap digunakan.");
        u.lang = "id-ID";
        speechSynthesis.speak(u);
        document.getElementById("btnSuara").style.display = "none";
        startSSE();
    }, 1000);
});
</script>
</body>
</html>
