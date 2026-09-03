<?php include("../config/db.php"); ?>
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
    top: 0; left: 0; width: 100%; height: 100%;
    opacity: 0.12; z-index: -1;
}
.header {
    background: rgba(0,0,0,0.3);
    padding: 20px;
    font-size: 42px;
    font-weight: bold;
    letter-spacing: 2px;
}
.status {
    position: fixed; top: 10px; right: 20px;
    font-size: 16px; background: rgba(255,255,255,0.15);
    padding: 6px 14px; border-radius: 20px;
}
.status.online { background: #2ecc71; }
.status.offline { background: #e74c3c; }
.main {
    display: grid;
    grid-template-columns: 50% 50%;
    height: 70vh;
    gap: 10px;
    padding: 20px;
    box-sizing: border-box;
}
.panel { display: flex; flex-direction: column; justify-content: center; align-items: center; }
.panel-box {
    background: rgba(0,0,0,0.25);
    border-radius: 20px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.3);
    padding: 30px 20px 40px 20px;
    width: 85%; transition: transform 0.2s ease;
}
.panel-box:hover { transform: scale(1.02); }
.panel-title {
    font-size: 32px; font-weight: 600; margin-bottom: 10px;
    text-transform: uppercase; letter-spacing: 1px; color: #ecf0f1;
}
#noObat, #noRacikan {
    font-size: 220px; font-weight: bold;
    text-shadow: 4px 4px 10px rgba(0,0,0,0.4);
    margin: 0;
}
#noObat { color: #f1c40f; }
#noRacikan { color: #00bfff; }
.label {
    background: #e74c3c; color: white;
    display: inline-block; margin-top: 20px;
    padding: 10px 25px; border-radius: 10px;
    font-size: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    min-width: 250px;
}
.footer {
    position: absolute; bottom: 0; width: 100%;
    display: flex; justify-content: space-around;
    background: rgba(0,0,0,0.25); padding: 20px 0;
}
.loket-box {
    background: rgba(255,255,255,0.1);
    padding: 15px; border-radius: 15px; width: 25%;
    box-shadow: 0 5px 10px rgba(0,0,0,0.3);
}
.loket-box h2 { font-size: 26px; margin: 0; font-weight: 600; color: #ecf0f1; }
.loket-box p { font-size: 46px; margin: 10px 0 0 0; font-weight: bold; color: #f1c40f; }
#btnSuara {
    position: fixed; top: 20px; left: 20px;
    background: #f1c40f; border: none; color: #2c3e50;
    padding: 10px 20px; border-radius: 10px;
    font-size: 16px; cursor: pointer; font-weight: bold; z-index: 999;
}
/* heartbeat */
@keyframes heartbeat {
    0% { transform: scale(1); box-shadow: none; }
    25% { transform: scale(1.05); box-shadow: 0 0 25px var(--glow-color); }
    50% { transform: scale(1); }
    75% { transform: scale(1.05); box-shadow: 0 0 25px var(--glow-color); }
    100% { transform: scale(1); }
}
.heartbeat { animation: heartbeat 1.3s ease-in-out infinite; }
</style>
</head>
<body>
<button id="btnSuara">🔊 Aktifkan Suara</button>
<div class="header">ANTRIAN FARMASI PENGAMBILAN OBAT</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>

<div class="main">
    <div class="panel">
        <div class="panel-box" id="boxObat">
            <div class="panel-title">Antrian Bukan Racikan</div>
            <div id="noObat">---</div>
            <div class="label" id="loketObat">Menunggu panggilan...</div>
        </div>
    </div>
    <div class="panel">
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
let ttsQueue = Promise.resolve(); // urutkan antrian TTS
let lastUtter = null;

// === INIT ===
speechSynthesis.onvoiceschanged = () => { voicesReady = true; };

// === LOAD CONFIG ===
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

// === UTIL ===
function angkaKeKata(n){
    const s=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan"];
    const b=["sepuluh","sebelas","dua belas","tiga belas","empat belas","lima belas","enam belas","tujuh belas","delapan belas","sembilan belas"];
    const p=["","", "dua puluh","tiga puluh","empat puluh","lima puluh","enam puluh","tujuh puluh","delapan puluh","sembilan puluh"];
    n=parseInt(n); if(isNaN(n))return n;
    if(n<10)return s[n]; if(n<20)return b[n-10];
    if(n<100)return p[Math.floor(n/10)] + (n%10?" "+s[n%10]:"");
    return n;
}
function startHeartbeat(id,color){
    const el=document.getElementById(id);
    el.style.setProperty("--glow-color",color);
    el.classList.add("heartbeat");
}
function stopHeartbeat(id){
    const el=document.getElementById(id);
    if(el) el.classList.remove("heartbeat");
}

// === PEMBACAAN SUARA STABIL ===
async function speakSafe(text,boxId,color){
    return new Promise(resolve=>{
        const u = new SpeechSynthesisUtterance(text);
        u.lang = suaraConfig.lang;
        u.volume = suaraConfig.volume;
        u.rate = suaraConfig.rate;
        const v = speechSynthesis.getVoices().find(v=>v.name===suaraConfig.voice);
        if(v) u.voice=v;

        let finished=false;
        u.onstart=()=>{ startHeartbeat(boxId,color); };
        u.onend=()=>{ if(!finished){ finished=true; stopHeartbeat(boxId); resolve(true);} };
        u.onerror=()=>{ if(!finished){ finished=true; stopHeartbeat(boxId); resolve(false);} };

        // fallback timeout
        setTimeout(()=>{ if(!finished){ finished=true; stopHeartbeat(boxId); resolve(false);} }, 15000);

        speechSynthesis.speak(u);
        lastUtter=u;
    });
}

// === QUEUE HANDLER ===
async function queueTTS(template, nomor, loket, boxId, color){
    ttsQueue = ttsQueue.then(async()=>{
        // Buat teks
        let nomorVoice = nomor.toString().trim();
        const match = nomorVoice.match(/^([A-Za-z]+)?(\d+)$/);
        if(match){
            const prefix = match[1]?match[1].toUpperCase()+" ":"";
            const angkaPart = match[2].replace(/^0+/,"");
            nomorVoice = prefix + angkaKeKata(angkaPart);
        }
        if(/^\d+$/.test(loket)) loket = "loket " + angkaKeKata(loket);

        const teks = template.replace("{nomor}", nomorVoice).replace("{loket}", loket);

        await speakSafe(teks, boxId, color);
        await new Promise(r=>setTimeout(r,300)); // delay antar suara
    });
}

// === SSE HANDLER ===
function updateKoneksi(ok){
    statusEl.className="status "+(ok?"online":"offline");
    statusEl.innerHTML=ok?"🟢 Terhubung ke Server":"🔴 Putus koneksi";
}

async function startSSE(){
    await loadSuaraConfig();
    const sse = new EventSource("event_stream.php");
    sse.onopen=()=>updateKoneksi(true);
    sse.onerror=()=>updateKoneksi(false);

    let lastTime=0;
    sse.addEventListener("update",e=>{
        const d=JSON.parse(e.data);
        if(!d.no||d.time===lastTime)return;
        lastTime=d.time;

        const jenis=(d.jenis||"").toLowerCase();
        const isRacikan = jenis==="racikan"||(d.no&&d.no.startsWith("R"));
        const loket=d.loket||"1";
        const nomor=d.no;

        bell.play().catch(()=>{});
        setTimeout(()=>{
            if(isRacikan){
                document.getElementById("noRacikan").textContent=nomor;
                document.getElementById("loketRacikan").textContent="Menuju Loket "+loket;
                queueTTS(suaraConfig.template_racikan,nomor,loket,"boxRacikan","#00bfff");
            } else {
                document.getElementById("noObat").textContent=nomor;
                document.getElementById("loketObat").textContent="Menuju Loket "+loket;
                queueTTS(suaraConfig.template_obat,nomor,loket,"boxObat","#f1c40f");
            }
            const ln = loket.match(/\d+/);
            if(ln){
                const el=document.getElementById("loket"+ln[0]);
                if(el) el.textContent=nomor;
            }
        },800);
    });
}

// === BUTTON AKTIFKAN SUARA ===
document.getElementById("btnSuara").addEventListener("click",()=>{
    const u=new SpeechSynthesisUtterance("Inisialisasi suara...");
    u.lang="id-ID";
    speechSynthesis.speak(u);
    setTimeout(()=>{
        const u2=new SpeechSynthesisUtterance("Suara aktif. Display siap digunakan.");
        u2.lang="id-ID";
        speechSynthesis.speak(u2);
        document.getElementById("btnSuara").style.display="none";
        startSSE();
    },1000);
});
</script>
</body>
</html>
