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
body::before {
    content: "";
    background: url("../config/assets/bg_rsu.png") no-repeat center center fixed;
    background-size: cover;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
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
    top: 10px; right: 20px;
    font-size: 16px;
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
}
.status.online { background: #2ecc71; }
.status.offline { background: #e74c3c; }

#ttsIndicator {
    position: fixed;
    top: 10px; left: 20px;
    background: rgba(0,0,0,0.4);
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 15px;
    font-weight: 500;
    z-index: 1000;
    backdrop-filter: blur(4px);
    box-shadow: 0 0 6px rgba(0,0,0,0.3);
}

#btnSuara {
    position: fixed; 
    top: 60px; 
    left: 20px;
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

.main {
    display: grid;
    grid-template-columns: 70% 30%;
    height: calc(100vh - 270px);
    gap: 10px;
    padding: 20px;
    box-sizing: border-box;
}

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
.loket-box h2 { font-size: 26px; margin: 0; font-weight: 600; color: #ecf0f1; }
.loket-box p { font-size: 46px; margin: 10px 0 0 0; font-weight: bold; color: #f1c40f; }

@keyframes heartbeat {
    0% { transform: scale(1); box-shadow: 0 0 0px rgba(255,255,255,0); }
    25% { transform: scale(1.06); box-shadow: 0 0 25px var(--glow-color); }
    50% { transform: scale(1); }
    75% { transform: scale(1.06); box-shadow: 0 0 25px var(--glow-color); }
    100% { transform: scale(1); }
}
.heartbeat { animation: heartbeat 1.3s ease-in-out infinite; }
</style>
</head>
<body>

<div id="ttsIndicator">🔊 TTS Nonaktif</div>
<button id="btnSuara">🔊 Aktifkan Suara</button>
<div class="header">ANTRIAN FARMASI PENGAMBILAN OBAT</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>

<div class="main">
    <div class="left">
        <video id="tvStream" autoplay muted playsinline controls></video>
    </div>

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

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const bell = document.getElementById("bell");
const statusEl = document.getElementById("status");
const ttsIndicator = document.getElementById("ttsIndicator");
const btnSuara = document.getElementById("btnSuara");
const video = document.getElementById("tvStream");

let suaraConfig = null;
let ttsContext = null;
let ttsQueue = Promise.resolve();
let lastNumberCalled = null;
let refreshCountdown = 6 * 60 * 60; // 6 jam
let VIDEO_VOLUME_NORMAL = 0.6;
const VIDEO_VOLUME_DUCKED = 0.05;
const FADE_SPEED = 0.05;

document.addEventListener("DOMContentLoaded", () => {
    const streamURL = "http://192.168.9.116:8080/live/stream.m3u8";
    video.volume = VIDEO_VOLUME_NORMAL;
    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(streamURL);
        hls.attachMedia(video);
    } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
        video.src = streamURL;
    }
});

function fadeVolume(target) {
    const step = (target > video.volume) ? FADE_SPEED : -FADE_SPEED;
    const timer = setInterval(() => {
        video.volume = Math.round((video.volume + step) * 100) / 100;
        if ((step < 0 && video.volume <= target) || (step > 0 && video.volume >= target)) {
            video.volume = target;
            clearInterval(timer);
        }
    }, 100);
}

async function ensureTTSContext() {
    if (!ttsContext) {
        try {
            ttsContext = new AudioContext();
            const buf = ttsContext.createBuffer(1, 1, 22050);
            const src = ttsContext.createBufferSource();
            src.buffer = buf;
            src.connect(ttsContext.destination);
            src.start(0);
            await ttsContext.resume();
        } catch {}
    } else if (ttsContext.state === "suspended") await ttsContext.resume();
}

async function loadSuaraConfig() {
    try {
        const res = await fetch("../config/get_suara.php");
        suaraConfig = await res.json();
    } catch {
        suaraConfig = {
            template_obat: "Panggilan pengambilan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
            template_racikan: "Panggilan pengambilan obat racikan, nomor antrian {nomor}, silakan menuju ke {loket}",
            voice: "Google Bahasa Indonesia", lang: "id-ID", volume: 1, rate: 1
        };
    }
    VIDEO_VOLUME_NORMAL = (suaraConfig.volume ?? 1) * 0.6;
}

function angkaKeKata(n) {
    const s=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan"];
    const b=["sepuluh","sebelas","dua belas","tiga belas","empat belas","lima belas","enam belas","tujuh belas","delapan belas","sembilan belas"];
    const p=["","", "dua puluh","tiga puluh","empat puluh","lima puluh","enam puluh","tujuh puluh","delapan puluh","sembilan puluh"];
    n=parseInt(n); if(isNaN(n))return n;
    if(n<10)return s[n]; if(n<20)return b[n-10];
    if(n<100)return p[Math.floor(n/10)] + (n%10?" "+s[n%10]:"");
    return n;
}

function startHeartbeat(id,color){const el=document.getElementById(id);if(el){el.style.setProperty("--glow-color",color);el.classList.add("heartbeat");}}
function stopHeartbeat(id){const el=document.getElementById(id);if(el)el.classList.remove("heartbeat");}

async function speakQueued(teks, boxId, color){
    await ensureTTSContext();
    await new Promise(resolve=>{
        const u=new SpeechSynthesisUtterance(teks);
        u.lang=suaraConfig.lang;
        u.volume=suaraConfig.volume;
        u.rate=suaraConfig.rate;
        const v=speechSynthesis.getVoices().find(v=>v.name===suaraConfig.voice);
        if(v)u.voice=v;
        u.onstart=()=>{startHeartbeat(boxId,color);ttsIndicator.textContent="🗣️ Membaca antrian...";};
        u.onend=()=>{stopHeartbeat(boxId);ttsIndicator.textContent="🔊 TTS Aktif";resolve();};
        u.onerror=()=>{stopHeartbeat(boxId);ttsIndicator.textContent="⚠️ Error TTS";resolve();};
        speechSynthesis.speak(u);
    });
}

async function playVoiceWithEffect(template, nomor, loket, boxId, color) {
    ttsQueue = ttsQueue.then(async () => {
        fadeVolume(VIDEO_VOLUME_DUCKED);
        let nomorVoice = nomor.toString().trim();
        const m = nomorVoice.match(/^([A-Za-z]+)?(\d+)$/);
        if (m) {
            const prefix = m[1] ? m[1].toUpperCase() + " " : "";
            const angka = m[2].replace(/^0+/, "");
            nomorVoice = prefix + angkaKeKata(angka);
        }
        if (/^\d+$/.test(loket)) loket = "loket " + angkaKeKata(loket);
        const teks = template.replace("{nomor}", nomorVoice).replace("{loket}", loket);
        await speakQueued(teks, boxId, color);
        fadeVolume(VIDEO_VOLUME_NORMAL);
        await new Promise(r => setTimeout(r, 300));
    });
}

function updateKoneksi(ok){
    statusEl.className="status "+(ok?"online":"offline");
    statusEl.innerHTML=ok?"🟢 Terhubung ke Server":"🔴 Putus koneksi";
}

// === SSE ===
async function startSSE() {
    await loadSuaraConfig();
    let reconnectDelay = 5;
    let reconnectTimer = null;

    const connectSSE = () => {
        const sse = new EventSource("event_stream.php");
        sse.onopen = () => {
            updateKoneksi(true);
            if (reconnectTimer) clearInterval(reconnectTimer);
        };
        sse.onerror = () => {
            sse.close();
            updateKoneksi(false);
            let count = reconnectDelay;
            reconnectTimer = setInterval(() => {
                count--;
                if (count <= 0) {
                    clearInterval(reconnectTimer);
                    connectSSE();
                }
            }, 1000);
        };
        sse.addEventListener("update", (e) => {
            const d = JSON.parse(e.data);
            if (!d.no) return;
            if (d.no === lastNumberCalled) return;
            lastNumberCalled = d.no;

            const jenis = (d.jenis || "").toLowerCase();
            const isRacikan = jenis === "racikan" || (d.no && d.no.startsWith("R"));
            const loket = d.loket || "1";
            const nomor = d.no;

            bell.play().catch(() => {});
            setTimeout(() => {
                if (isRacikan) {
                    document.getElementById("noRacikan").textContent = nomor;
                    document.getElementById("loketRacikan").textContent = "Menuju Loket " + loket;
                    playVoiceWithEffect(suaraConfig.template_racikan, nomor, loket, "boxRacikan", "#00bfff");
                } else {
                    document.getElementById("noObat").textContent = nomor;
                    document.getElementById("loketObat").textContent = "Menuju Loket " + loket;
                    playVoiceWithEffect(suaraConfig.template_obat, nomor, loket, "boxObat", "#f1c40f");
                }
                const ln = loket.match(/\d+/);
                if (ln) {
                    const el = document.getElementById("loket" + ln[0]);
                    if (el) el.textContent = nomor;
                }
            }, 800);
        });
    };
    connectSSE();
}

btnSuara.addEventListener("click", async () => {
    await ensureTTSContext();
    const init = new SpeechSynthesisUtterance("Inisialisasi suara display farmasi");
    init.lang = "id-ID";
    speechSynthesis.speak(init);

    setTimeout(() => {
        const ready = new SpeechSynthesisUtterance("Suara aktif, sistem siap digunakan.");
        ready.lang = "id-ID";
        speechSynthesis.speak(ready);
        btnSuara.style.display = "none";
        ttsIndicator.textContent = "🔊 TTS Aktif";
        startSSE();
    }, 1000);
});

// Auto refresh info
setInterval(() => {
    refreshCountdown--;
    const jam = Math.floor(refreshCountdown / 3600);
    const menit = Math.floor((refreshCountdown % 3600) / 60);
    const detik = refreshCountdown % 60;
    if(btnSuara.style.display==="none")
        ttsIndicator.textContent=`🔊 TTS Aktif | Refresh dalam ${jam}j ${menit}m ${detik}s`;
    else
        ttsIndicator.textContent=`🔇 TTS Nonaktif | Tekan tombol untuk aktifkan`;
    if(refreshCountdown<=0){
        if(video) video.pause();
        location.reload(true);
    }
},1000);

// === 🔧 Tambahan kontrol dari server (Mute/Volume/Reset) ===
async function applyDisplayControl() {
  try {
    const res = await fetch("../config/display_state.json?ts=" + Date.now());
    const st = await res.json();
    video.muted = !!st.mute;
    if (typeof st.volume === "number") {
      video.volume = Math.min(Math.max(st.volume, 0), 1);
    }
    if (st.reset) {
      await fetch("../config/display_state.json", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({...st, reset:false})
      });
      location.reload(true);
    }
  } catch (e) {}
}
setInterval(applyDisplayControl, 2000);
window.addEventListener("focus", applyDisplayControl);
document.addEventListener("visibilitychange", () => {
  if (!document.hidden) applyDisplayControl();
});

</script>
</body>
</html>
