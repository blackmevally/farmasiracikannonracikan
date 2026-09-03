<?php
include("../config/db.php");
include("../config/stream_config.php");
date_default_timezone_set("Asia/Jakarta");
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
    margin: 0;
    overflow: hidden;
}
body::before {
    content: "";
    background: url("../config/assets/bg_rsu.png") no-repeat center center fixed;
    background-size: cover;
    position: fixed;
    inset: 0;
    opacity: 0.12;
    z-index: -1;
}
.header {
    background: rgba(0,0,0,0.3);
    padding: 20px;
    font-size: 40px;
    font-weight: 800;
	text-align: center;
}
.status {
    position: fixed;
    top: 10px; right: 20px;
    font-size: 14px;
    padding: 6px 14px;
    border-radius: 20px;
}
.status.online { background:#2ecc71 }
.status.offline { background:#e74c3c }

#ttsIndicator {
    position: fixed;
    top: 10px; left: 20px;
    background: rgba(0,0,0,0.45);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    z-index: 999;
}
#btnSuara {
    position: fixed;
    top: 55px; left: 20px;
    background:#f1c40f;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    font-weight:700;
    cursor:pointer;
    z-index:999;
}

.main {
    display:grid;
    grid-template-columns:70% 30%;
    height:calc(100vh - 260px);
    gap:12px;
    padding:16px;
}
.left {
    background:black;
    border-radius:12px;
    overflow:hidden;
}
.left video {
    width:100%;
    height:100%;
    object-fit:cover;
}
.right {
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}
.panel-box {
    background:rgba(0,0,0,0.3);
    border-radius:18px;
    padding:24px;
    text-align:center;
}
.panel-title {
    font-size:22px;
    font-weight:700;
    margin-bottom:8px;
}
#noObat, #noRacikan {
    font-size:140px;
    font-weight:900;
}
#noObat { color:#f1c40f }
#noRacikan { color:#00bfff }
.label {
    margin-top:10px;
    background:#e74c3c;
    padding:8px 20px;
    border-radius:10px;
    font-size:20px;
}

.footer {
    position:absolute;
    bottom:0;
    width:100%;
    display:flex;
    justify-content:space-around;
    background:rgba(0,0,0,0.25);
    padding:16px 0;
}
.loket-box {
    background:rgba(255,255,255,0.12);
    border-radius:12px;
    padding:12px;
    width:22%;
    text-align:center;
}
.loket-box h2 { margin:0; font-size:22px }
.loket-box p { margin:6px 0 0; font-size:40px; font-weight:800; color:#f1c40f }

@keyframes heartbeat {
    0%{transform:scale(1)}
    25%{transform:scale(1.05);box-shadow:0 0 25px var(--glow-color)}
    50%{transform:scale(1)}
    75%{transform:scale(1.05);box-shadow:0 0 25px var(--glow-color)}
    100%{transform:scale(1)}
}
.heartbeat{animation:heartbeat 1.3s ease-in-out infinite}
</style>
</head>
<body>

<div id="ttsIndicator">🔊 TTS Nonaktif</div>
<button id="btnSuara">🔊 Aktifkan Suara</button>
<div class="header">ANTRIAN FARMASI PENGAMBILAN OBAT</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>

<div class="main">
    <div class="left">
        <video id="tvStream" autoplay muted playsinline></video>
    </div>
    <div class="right">
        <div class="panel-box" id="boxObat">
            <div class="panel-title">ANTRIAN BUKAN RACIKAN (O)</div>
            <div id="noObat">---</div>
            <div class="label" id="loketObat">Menunggu...</div>
        </div>
        <div class="panel-box" id="boxRacikan">
            <div class="panel-title">ANTRIAN RACIKAN (R)</div>
            <div id="noRacikan">---</div>
            <div class="label" id="loketRacikan">Menunggu...</div>
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
/* ===================== GLOBAL ===================== */
const ENABLE_STREAMING = <?= ENABLE_STREAMING ? "true" : "false" ?>;
const bell = document.getElementById("bell");
const video = document.getElementById("tvStream");
const btnSuara = document.getElementById("btnSuara");
const ttsIndicator = document.getElementById("ttsIndicator");
const statusEl = document.getElementById("status");

let suaraConfig = null;
let ttsContext = null;
let ttsQueue = Promise.resolve();
let eventQueue = [];
let processing = false;
let lastCallTime = 0;
let sessionStartTime = Math.floor(Date.now()/1000);

let VIDEO_VOLUME_NORMAL = 0.3;
const VIDEO_VOLUME_DUCKED = 0.05;

/* ===================== VIDEO ===================== */
document.addEventListener("DOMContentLoaded", () => {
    speechSynthesis.getVoices();
    if (!ENABLE_STREAMING) return;

    const url = "http://192.168.9.136/hls/stream.m3u8";
    if (Hls.isSupported()) {
        const hls = new Hls({maxBufferLength:10});
        hls.loadSource(url);
        hls.attachMedia(video);
    } else video.src = url;
});

/* ===================== AUDIO HELPERS ===================== */
function fadeVolume(target){
    const step = target > video.volume ? 0.05 : -0.05;
    const t = setInterval(()=>{
        video.volume = Math.round((video.volume + step)*100)/100;
        if ((step<0 && video.volume<=target)||(step>0 && video.volume>=target)){
            video.volume = target;
            clearInterval(t);
        }
    },100);
}

async function ensureTTSContext(){
    if(!ttsContext){
        ttsContext = new (window.AudioContext||window.webkitAudioContext)();
        const buf = ttsContext.createBuffer(1,1,22050);
        const src = ttsContext.createBufferSource();
        src.buffer = buf;
        src.connect(ttsContext.destination);
        src.start(0);
        await ttsContext.resume();
    } else if(ttsContext.state==="suspended") await ttsContext.resume();
}

function angkaKeKata(n){
    const s=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan"];
    const b=["sepuluh","sebelas","dua belas","tiga belas","empat belas","lima belas","enam belas","tujuh belas","delapan belas","sembilan belas"];
    const p=["","","dua puluh","tiga puluh","empat puluh","lima puluh","enam puluh","tujuh puluh","delapan puluh","sembilan puluh"];
    n=parseInt(n); if(isNaN(n)) return n;
    if(n<10) return s[n];
    if(n<20) return b[n-10];
    if(n<100) return p[Math.floor(n/10)] + (n%10?" "+s[n%10]:"");
    return n;
}

function startHeartbeat(id,color){
    const el=document.getElementById(id);
    if(el){el.style.setProperty("--glow-color",color);el.classList.add("heartbeat");}
}
function stopHeartbeat(id){
    const el=document.getElementById(id);
    if(el) el.classList.remove("heartbeat");
}

/* ===================== TTS CORE (FULL FIX) ===================== */
async function speakQueued(teks, boxId, color){
    await ensureTTSContext();

    if (speechSynthesis.speaking || speechSynthesis.pending) {
        speechSynthesis.cancel();
        await new Promise(r=>setTimeout(r,120));
    }

    return new Promise(resolve=>{
        const u = new SpeechSynthesisUtterance(teks);
        u.lang = suaraConfig.lang || "id-ID";
        u.volume = suaraConfig.volume ?? 1;
        u.rate   = suaraConfig.rate ?? 1;

        const voices = speechSynthesis.getVoices();
        if (suaraConfig.voice) {
            const v = voices.find(x=>x.name===suaraConfig.voice);
            if(v) u.voice = v;
        }

        u.onstart = ()=>{
            startHeartbeat(boxId,color);
            ttsIndicator.textContent="🗣️ Membaca antrian...";
        };
        u.onend = ()=>{
            stopHeartbeat(boxId);
            ttsIndicator.textContent="🔊 TTS Aktif";
            setTimeout(resolve,100);
        };
        u.onerror = ()=>{
            stopHeartbeat(boxId);
            setTimeout(resolve,100);
        };
        speechSynthesis.speak(u);
    });
}

function playVoiceWithEffect(template, nomor, loket, boxId, color){
    ttsQueue = ttsQueue.then(async ()=>{
        fadeVolume(VIDEO_VOLUME_DUCKED);

        let nv = nomor.toString();
        const m = nv.match(/^([A-Za-z]+)?(\d+)$/);
        if(m){
            nv = (m[1]?m[1]+" ":"") + angkaKeKata(m[2]);
        }
        if(/^\d+$/.test(loket)) loket="loket "+angkaKeKata(loket);

        const teks = template.replace("{nomor}",nv).replace("{loket}",loket);
        await speakQueued(teks, boxId, color);

        fadeVolume(VIDEO_VOLUME_NORMAL);
        await new Promise(r=>setTimeout(r,300));
    });
    return ttsQueue;
}

/* ===================== EVENT QUEUE ===================== */
async function processEventQueue(){
    if(processing||eventQueue.length===0) return;
    processing=true;
    const d = eventQueue.shift();
    await handleCallEvent(d);
    processing=false;
    if(eventQueue.length>0) processEventQueue();
}

async function handleCallEvent(d){
    if(!d.no) return;
    if(d.time && !d.ulang){
        if(d.time<sessionStartTime || d.time<=lastCallTime) return;
    }
    lastCallTime=d.time||Date.now()/1000;

    await bell.play().catch(()=>{});
    await new Promise(r=>setTimeout(r,400));

    const nomor=d.no;
    const loket=d.loket||"1";
    const isRacikan=(d.jenis||"").toLowerCase()==="racikan"||nomor.startsWith("R");

    if(isRacikan){
        noRacikan.textContent=nomor;
        loketRacikan.textContent="Menuju "+loket;
        await playVoiceWithEffect(suaraConfig.template_racikan,nomor,loket,"boxRacikan","#00bfff");
    }else{
        noObat.textContent=nomor;
        loketObat.textContent="Menuju "+loket;
        await playVoiceWithEffect(suaraConfig.template_obat,nomor,loket,"boxObat","#f1c40f");
    }

    const ln=loket.match(/\d+/);
    if(ln){
        const el=document.getElementById("loket"+ln[0]);
        if(el) el.textContent=nomor;
    }
}

/* ===================== SSE ===================== */
async function startSSE(){
    const res = await fetch("../config/get_suara.php");
    suaraConfig = await res.json();
    VIDEO_VOLUME_NORMAL=(suaraConfig.volume??1)*0.3;

    const sse = new EventSource("event_stream.php");
    sse.onopen=()=>{statusEl.className="status online";statusEl.textContent="🟢 Terhubung"};
    sse.onerror=()=>{statusEl.className="status offline";statusEl.textContent="🔴 Putus koneksi"};
    sse.addEventListener("update",e=>{
        try{
            const d=JSON.parse(e.data);
            if(d.no){
                eventQueue.push(d);
                processEventQueue();
            }
        }catch{}
    });
}

/* ===================== BUTTON ===================== */
btnSuara.onclick = async ()=>{
    await ensureTTSContext();
    video.muted=false;
    video.volume=VIDEO_VOLUME_NORMAL;
    btnSuara.style.display="none";
    ttsIndicator.textContent="🔊 TTS Aktif";
    startSSE();
};
</script>
</body>
</html>
