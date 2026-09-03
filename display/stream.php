<?php
include("../config/db.php");
include("../config/stream_config.php");

// Production response headers. Tidak mengubah alur aplikasi lama.
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Display Antrian Farmasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
<style>
body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(180deg, #27ae60, #1e8449); color: white; text-align: center; margin: 0; overflow: hidden; }
body::before { content: ""; background: url("../config/assets/bg_rsu.png") no-repeat center center fixed; background-size: cover; position: fixed; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.12; z-index: -1; }
.header { background: rgba(0,0,0,0.3); padding: 20px; font-size: 42px; font-weight: bold; letter-spacing: 2px; }
.status { position: fixed; top: 10px; right: 20px; font-size: 16px; background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 20px; z-index: 1000; }
.status.online { background: #2ecc71; }
.status.offline { background: #e74c3c; }
#ttsIndicator { position: fixed; top: 10px; left: 20px; background: rgba(0,0,0,0.4); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 15px; font-weight: 500; z-index: 1000; backdrop-filter: blur(4px); box-shadow: 0 0 6px rgba(0,0,0,0.3); }
#btnSuara { position: fixed; top: 60px; left: 20px; background: #f1c40f; border: none; color: #2c3e50; padding: 10px 20px; border-radius: 10px; font-size: 16px; cursor: pointer; font-weight: bold; z-index: 999; }
#btnSuara:disabled { opacity: 0.6; cursor: wait; }
.main { display: grid; grid-template-columns: 70% 30%; height: calc(100vh - 270px); gap: 10px; padding: 20px; box-sizing: border-box; }
.left { display: flex; justify-content: center; align-items: center; background: black; overflow: hidden; border-radius: 12px; }
.left video { width: 100%; height: 100%; object-fit: cover; background: black; }
.right { display: flex; flex-direction: column; justify-content: space-around; align-items: center; }
.panel-box { background: rgba(0,0,0,0.25); border-radius: 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.3); padding: 30px 20px 40px 20px; width: 90%; transition: transform 0.2s ease; }
.panel-box:hover { transform: scale(1.02); }
.panel-title { font-size: 28px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; color: #ecf0f1; }
#noObat, #noRacikan { font-size: 160px; font-weight: bold; text-shadow: 4px 4px 10px rgba(0,0,0,0.4); margin: 0; }
#noObat { color: #f1c40f; }
#noRacikan { color: #00bfff; }
.label { background: #e74c3c; color: white; display: inline-block; margin-top: 15px; padding: 10px 25px; border-radius: 10px; font-size: 24px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: fit-content; min-width: 220px; }
.footer { position: absolute; bottom: 0; width: 100%; display: flex; justify-content: space-around; background: rgba(0,0,0,0.25); padding: 20px 0; }
.loket-box { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 15px; width: 25%; box-shadow: 0 5px 10px rgba(0,0,0,0.3); }
.loket-box h2 { font-size: 26px; margin: 0; font-weight: 600; color: #ecf0f1; }
.loket-box p { font-size: 46px; margin: 10px 0 0 0; font-weight: bold; color: #f1c40f; }
@keyframes heartbeat { 0% { transform: scale(1); box-shadow: 0 0 0px rgba(255,255,255,0); } 25% { transform: scale(1.06); box-shadow: 0 0 25px var(--glow-color); } 50% { transform: scale(1); } 75% { transform: scale(1.06); box-shadow: 0 0 25px var(--glow-color); } 100% { transform: scale(1); } }
.heartbeat { animation: heartbeat 1.3s ease-in-out infinite; }
</style>
</head>
<body>
<div id="ttsIndicator">🔊 TTS Nonaktif</div>
<button id="btnSuara">🔊 Aktifkan Suara</button>
<div class="header">ANTRIAN FARMASI PENGAMBILAN OBAT</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>
<div class="main">
    <div class="left"><video id="tvStream" autoplay muted playsinline controls></video></div>
    <div class="right">
        <div class="panel-box" id="boxObat"><div class="panel-title">Antrian Bukan Racikan</div><div id="noObat">---</div><div class="label" id="loketObat">Menunggu panggilan...</div></div>
        <div class="panel-box" id="boxRacikan"><div class="panel-title">Antrian Racikan</div><div id="noRacikan">---</div><div class="label" id="loketRacikan">Menunggu panggilan...</div></div>
    </div>
</div>
<div class="footer"><div class="loket-box"><h2>Loket 3</h2><p id="loket3">---</p></div><div class="loket-box"><h2>Loket 2</h2><p id="loket2">---</p></div><div class="loket-box"><h2>Loket 1</h2><p id="loket1">---</p></div></div>
<audio id="bell" src="../tingtong.mp3" preload="auto"></audio>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const STREAM_ENABLED = <?php echo defined('ENABLE_STREAMING') && ENABLE_STREAMING ? 'true' : 'false'; ?>;
const STREAM_URL = <?php echo json_encode(defined('STREAM_URL') ? STREAM_URL : ''); ?>;

const bell = document.getElementById("bell");
const statusEl = document.getElementById("status");
const ttsIndicator = document.getElementById("ttsIndicator");
const btnSuara = document.getElementById("btnSuara");
const video = document.getElementById("tvStream");
let suaraConfig = null;
let ttsContext = null;
let ttsQueue = Promise.resolve();
let lastNumberCalled = null;
let lastCallTime = 0;
let sse = null;
let sseReconnectTimer = null;
let sseReconnectAttempts = 0;
let hls = null;
let hlsRetryTimer = null;
let volumeFadeTimer = null;
let normalVolume = 0.3;
const VIDEO_VOLUME_DUCKED = 0.05;
const sessionStartTime = Math.floor(Date.now() / 1000);

function updateKoneksi(ok, message) {
    statusEl.className = "status " + (ok ? "online" : "offline");
    statusEl.textContent = ok ? "🟢 Terhubung ke Server" : (message || "🔴 Putus koneksi");
}

function clamp(value, min, max) { return Math.min(Math.max(value, min), max); }

function fadeVolume(target) {
    target = clamp(Number(target) || 0, 0, 1);
    if (volumeFadeTimer) { clearInterval(volumeFadeTimer); volumeFadeTimer = null; }
    const start = Number(video.volume) || 0;
    if (Math.abs(start - target) < 0.01) { video.volume = target; return; }
    const step = target > start ? 0.05 : -0.05;
    volumeFadeTimer = setInterval(() => {
        let next = Math.round((video.volume + step) * 100) / 100;
        if ((step < 0 && next <= target) || (step > 0 && next >= target)) {
            next = target;
            clearInterval(volumeFadeTimer);
            volumeFadeTimer = null;
        }
        video.volume = clamp(next, 0, 1);
    }, 100);
}

async function ensureTTSContext() {
    try {
        if (!ttsContext) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) ttsContext = new AudioCtx();
        }
        if (ttsContext && ttsContext.state === "suspended") await ttsContext.resume();
    } catch (e) { console.warn("AudioContext tidak dapat diaktifkan:", e); }
}

async function loadSuaraConfig() {
    const fallback = {
        template_obat: "Panggilan pengambilan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
        template_racikan: "Panggilan pengambilan obat racikan, nomor antrian {nomor}, silakan menuju ke {loket}",
        voice: "default", lang: "id-ID", volume: 1, rate: 1
    };
    try {
        const res = await fetch("../config/get_suara.php", { cache: "no-store" });
        if (!res.ok) throw new Error("HTTP " + res.status);
        const data = await res.json();
        suaraConfig = Object.assign({}, fallback, data || {});
    } catch (e) {
        console.warn("Config suara gagal dimuat, menggunakan default:", e);
        suaraConfig = fallback;
    }
    const volume = clamp(Number(suaraConfig.volume), 0, 1);
    normalVolume = clamp(volume * 0.3, 0, 1);
    video.volume = normalVolume;
}

function angkaKeKata(n) {
    const satuan = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan"];
    n = parseInt(n, 10);
    if (isNaN(n)) return "";
    if (n < 10) return satuan[n];
    if (n < 20) return n === 10 ? "sepuluh" : n === 11 ? "sebelas" : satuan[n - 10] + " belas";
    if (n < 100) return satuan[Math.floor(n / 10)] + " puluh" + (n % 10 ? " " + satuan[n % 10] : "");
    if (n < 200) return "seratus" + (n % 100 ? " " + angkaKeKata(n % 100) : "");
    if (n < 1000) return satuan[Math.floor(n / 100)] + " ratus" + (n % 100 ? " " + angkaKeKata(n % 100) : "");
    return String(n);
}

function startHeartbeat(id, color) {
    const el = document.getElementById(id);
    if (el) { el.style.setProperty("--glow-color", color); el.classList.add("heartbeat"); }
}
function stopHeartbeat(id) { const el = document.getElementById(id); if (el) el.classList.remove("heartbeat"); }

function getVoice() {
    if (!suaraConfig || !("speechSynthesis" in window)) return null;
    const voices = speechSynthesis.getVoices();
    return voices.find(v => v.name === suaraConfig.voice) ||
           voices.find(v => v.lang === suaraConfig.lang) ||
           voices.find(v => /^id(-|_)/i.test(v.lang)) || null;
}

function speakQueued(teks, boxId, color) {
    ttsQueue = ttsQueue.then(async () => {
        await ensureTTSContext();
        if (!("speechSynthesis" in window)) throw new Error("Speech Synthesis tidak tersedia");
        await new Promise(resolve => {
            const u = new SpeechSynthesisUtterance(teks);
            u.lang = suaraConfig.lang || "id-ID";
            u.volume = clamp(Number(suaraConfig.volume), 0, 1);
            u.rate = clamp(Number(suaraConfig.rate) || 1, 0.5, 2);
            const voice = getVoice();
            if (voice) u.voice = voice;
            let settled = false;
            const finish = (message) => {
                if (settled) return;
                settled = true;
                stopHeartbeat(boxId);
                ttsIndicator.textContent = message || "🔊 TTS Aktif";
                resolve();
            };
            u.onstart = () => { startHeartbeat(boxId, color); ttsIndicator.textContent = "🗣️ Membaca antrian..."; };
            u.onend = () => finish("🔊 TTS Aktif");
            u.onerror = () => finish("⚠️ Error TTS");
            speechSynthesis.speak(u);
            setTimeout(() => finish("🔊 TTS Aktif"), 15000);
        });
    }).catch(err => {
        console.warn("TTS queue error:", err);
        ttsIndicator.textContent = "⚠️ Error TTS";
    });
    return ttsQueue;
}

async function playVoiceWithEffect(template, nomor, loket, boxId, color) {
    ttsQueue = ttsQueue.then(async () => {
        fadeVolume(VIDEO_VOLUME_DUCKED);
        let nomorVoice = String(nomor || "").trim();
        const match = nomorVoice.match(/^([A-Za-z]+)?(\d+)$/);
        if (match) {
            const prefix = match[1] ? match[1].toUpperCase() + " " : "";
            const angka = match[2].replace(/^0+/, "") || "0";
            nomorVoice = prefix + angkaKeKata(angka);
        }
        let loketVoice = String(loket || "1").trim();
        if (/^\d+$/.test(loketVoice)) loketVoice = "loket " + angkaKeKata(loketVoice);
        let teks = String(template || "")
            .replaceAll("{nomor}", nomorVoice)
            .replaceAll("{loket}", loketVoice)
            .replace(/\bloket\s+loket\b/gi, "loket")
            .trim();
        await speakQueued(teks, boxId, color);
        fadeVolume(normalVolume);
        await new Promise(resolve => setTimeout(resolve, 300));
    });
    return ttsQueue;
}

function destroyHLS() {
    if (hls) { try { hls.destroy(); } catch (e) {} hls = null; }
    if (hlsRetryTimer) { clearTimeout(hlsRetryTimer); hlsRetryTimer = null; }
}

function scheduleHLSRetry(url) {
    if (hlsRetryTimer || !STREAM_ENABLED) return;
    hlsRetryTimer = setTimeout(() => { hlsRetryTimer = null; playStream(url); }, 4000);
}

function playStream(url) {
    destroyHLS();
    if (!url || !STREAM_ENABLED) return;
    if (window.Hls && Hls.isSupported()) {
        hls = new Hls({ maxBufferLength: 10, maxMaxBufferLength: 20, backBufferLength: 10, enableWorker: true });
        hls.loadSource(url);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => video.play().catch(() => {}));
        hls.on(Hls.Events.ERROR, (event, data) => {
            if (!data || !data.fatal) return;
            console.warn("HLS fatal error:", data.type, data.details);
            if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                try { hls.recoverMediaError(); } catch (e) { scheduleHLSRetry(url); }
            } else scheduleHLSRetry(url);
        });
    } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
        video.src = url;
        video.addEventListener("loadedmetadata", () => video.play().catch(() => {}), { once: true });
        video.addEventListener("error", () => scheduleHLSRetry(url), { once: true });
    } else console.error("HLS tidak didukung di browser ini");
}

function startStream() {
    if (!STREAM_ENABLED) {
        console.info("Streaming dinonaktifkan melalui stream_config.php");
        return;
    }
    playStream(STREAM_URL);
}

function clearSSEReconnect() {
    if (sseReconnectTimer) { clearTimeout(sseReconnectTimer); sseReconnectTimer = null; }
}

function scheduleSSEReconnect() {
    clearSSEReconnect();
    sseReconnectAttempts++;
    const delay = Math.min(30000, Math.max(3000, 3000 * Math.pow(2, Math.min(sseReconnectAttempts - 1, 3))));
    let seconds = Math.ceil(delay / 1000);
    updateKoneksi(false, `🔴 Putus koneksi — mencoba ulang dalam ${seconds} detik...`);
    const countdown = setInterval(() => {
        seconds--;
        if (seconds > 0) statusEl.textContent = `🔴 Putus koneksi — mencoba ulang dalam ${seconds} detik...`;
        else clearInterval(countdown);
    }, 1000);
    sseReconnectTimer = setTimeout(() => {
        clearInterval(countdown);
        sseReconnectTimer = null;
        connectSSE();
    }, delay);
}

function connectSSE() {
    clearSSEReconnect();
    if (sse) { try { sse.close(); } catch (e) {} sse = null; }
    try { sse = new EventSource("event_stream.php"); }
    catch (e) { scheduleSSEReconnect(); return; }

    sse.onopen = () => { sseReconnectAttempts = 0; updateKoneksi(true); };
    sse.onerror = () => {
        if (sse) { try { sse.close(); } catch (e) {} sse = null; }
        scheduleSSEReconnect();
    };

    sse.addEventListener("update", (e) => {
        let d;
        try { d = JSON.parse(e.data); } catch (err) { console.warn("Payload SSE tidak valid"); return; }
        if (!d || !d.no) return;
        const eventTime = Number(d.time) || Math.floor(Date.now() / 1000);
        const isRepeat = !!d.ulang;
        if (!isRepeat) {
            if (eventTime < sessionStartTime) return;
            if (eventTime < lastCallTime) return;
        }
        lastNumberCalled = String(d.no);
        lastCallTime = Math.max(lastCallTime, eventTime);
        const jenis = String(d.jenis || "").toLowerCase();
        const nomor = String(d.no);
        const isRacikan = jenis === "racikan" || nomor.toUpperCase().startsWith("R");
        const loket = String(d.loket || "1");
        bell.currentTime = 0;
        bell.play().catch(() => {});
        setTimeout(() => {
            const loketLabelRaw = loket.trim();
            const loketLabel = /^loket\b/i.test(loketLabelRaw) ? loketLabelRaw : "Loket " + loketLabelRaw;
            if (isRacikan) {
                document.getElementById("noRacikan").textContent = nomor;
                document.getElementById("loketRacikan").textContent = "Menuju " + loketLabel;
                playVoiceWithEffect(suaraConfig.template_racikan, nomor, loket, "boxRacikan", "#00bfff");
            } else {
                document.getElementById("noObat").textContent = nomor;
                document.getElementById("loketObat").textContent = "Menuju " + loketLabel;
                playVoiceWithEffect(suaraConfig.template_obat, nomor, loket, "boxObat", "#f1c40f");
            }
            const matchLoket = loket.match(/\d+/);
            if (matchLoket) {
                const el = document.getElementById("loket" + matchLoket[0]);
                if (el) el.textContent = nomor;
            }
        }, 800);
    });
}

async function startSSE() {
    await loadSuaraConfig();
    connectSSE();
}

btnSuara.addEventListener("click", async () => {
    btnSuara.disabled = true;
    await ensureTTSContext();
    await loadSuaraConfig();
    try {
        await speakQueued("Inisialisasi suara", "boxObat", "#f1c40f");
        await new Promise(resolve => setTimeout(resolve, 700));
        await speakQueued("Suara aktif", "boxObat", "#f1c40f");
        ttsIndicator.textContent = "🔊 TTS Aktif";
        btnSuara.style.display = "none";
        startSSE();
    } catch (e) {
        btnSuara.disabled = false;
        ttsIndicator.textContent = "⚠️ Gagal mengaktifkan suara";
    }
});

if ("speechSynthesis" in window) speechSynthesis.onvoiceschanged = () => {};

document.addEventListener("DOMContentLoaded", async () => {
    await loadSuaraConfig();
    startStream();
});

window.addEventListener("beforeunload", () => {
    clearSSEReconnect();
    if (sse) { try { sse.close(); } catch (e) {} }
    destroyHLS();
    if (volumeFadeTimer) clearInterval(volumeFadeTimer);
    try { speechSynthesis.cancel(); } catch (e) {}
});
</script>
</body>
</html>
