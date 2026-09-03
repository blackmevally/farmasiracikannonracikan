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
.header {
    background: rgba(0,0,0,0.2);
    padding: 20px;
    font-size: 40px;
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
.status.online {
    background: #2ecc71;
    color: white;
}
.status.offline {
    background: #e74c3c;
    color: white;
}
.main {
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 70vh;
}
#noAntrian {
    font-size: 300px; /* 30% lebih besar */
    font-weight: bold;
    color: #f1c40f;
    text-shadow: 4px 4px 10px rgba(0,0,0,0.4);
}
#loketTujuan {
    background: #e74c3c;
    color: white;
    display: inline-block;
    margin-top: 20px;
    padding: 15px 50px;
    border-radius: 12px;
    font-size: 40px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    display: flex;
    justify-content: space-around;
    background: rgba(0,0,0,0.2);
    padding: 20px 0;
}
.loket-box {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 15px;
    width: 25%;
    color: white;
}
.loket-box h2 {
    font-size: 28px;
    margin: 0;
    font-weight: 600;
}
.loket-box p {
    font-size: 48px;
    margin: 10px 0 0 0;
    color: #f1c40f;
    font-weight: bold;
}
#sound { display: none; }
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
</style>
</head>
<body>
<button id="btnSuara">🔊 Aktifkan Suara</button>

<div class="header">ANTRIAN FARMASI</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>

<div class="main">
    <div id="noAntrian">---</div>
    <div id="loketTujuan">Menunggu panggilan...</div>
</div>

<div class="footer" id="footerLoket">
    <div class="loket-box"><h2>Loket 3</h2><p id="loket3">---</p></div>
    <div class="loket-box"><h2>Loket 2</h2><p id="loket2">---</p></div>
    <div class="loket-box"><h2>Loket 1</h2><p id="loket1">---</p></div>
</div>

<audio id="bell" src="../tingtong.mp3"></audio>

<script>
const bell = document.getElementById("bell");
const statusEl = document.getElementById("status");

// 🎚️ Konfigurasi suara (default)
let suaraConfig = {
  template: "Panggilan penyerahan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
  voice: "default",
  lang: "id-ID",
  volume: 1,
  rate: 1
};

// 🔁 Ambil konfigurasi dari server
fetch("../config/get_suara.php")
  .then(res => res.json())
  .then(cfg => { suaraConfig = cfg; console.log("Config suara:", cfg); })
  .catch(() => console.warn("⚠️ Gagal memuat konfigurasi suara"));

// 🔢 Konversi angka ke kata (Bahasa Indonesia)
function angkaKeKata(angka) {
    const satuan = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan"];
    const belasan = ["sepuluh", "sebelas", "dua belas", "tiga belas", "empat belas", "lima belas", "enam belas", "tujuh belas", "delapan belas", "sembilan belas"];
    const puluhan = ["", "", "dua puluh", "tiga puluh", "empat puluh", "lima puluh", "enam puluh", "tujuh puluh", "delapan puluh", "sembilan puluh"];
    let n = parseInt(angka, 10);
    if (isNaN(n)) return angka;
    if (n < 10) return satuan[n];
    if (n < 20) return belasan[n - 10];
    if (n < 100) return puluhan[Math.floor(n / 10)] + (n % 10 ? " " + satuan[n % 10] : "");
    if (n < 200) return "seratus " + angkaKeKata(n - 100);
    if (n < 1000) return satuan[Math.floor(n / 100)] + " ratus " + angkaKeKata(n % 100);
    return angka;
}

// 🔊 Pemutar suara
function playVoice(data) {
  if (!window.speechSynthesis) return;

  let teks = suaraConfig.template
    .replace("{nomor}", angkaKeKata(data.no))
    .replace("{loket}", data.loket);

  const utter = new SpeechSynthesisUtterance(teks);
  utter.lang = suaraConfig.lang || "id-ID";
  utter.rate = parseFloat(suaraConfig.rate) || 1;
  utter.volume = parseFloat(suaraConfig.volume) || 1;

  const voices = speechSynthesis.getVoices();
  const v = voices.find(x => x.name === suaraConfig.voice);
  if (v) utter.voice = v;

  speechSynthesis.cancel();
  setTimeout(() => speechSynthesis.speak(utter), 500);
}

// 🟢 Status koneksi
function updateKoneksi(isOnline) {
    if (isOnline) {
        statusEl.classList.remove("offline");
        statusEl.classList.add("online");
        statusEl.innerHTML = "🟢 Terhubung ke Server";
    } else {
        statusEl.classList.remove("online");
        statusEl.classList.add("offline");
        statusEl.innerHTML = "🔴 Putus koneksi";
    }
}

let lastEventTime = 0;

// 🔔 Realtime EventSource
const evtSource = new EventSource("event_stream.php");
evtSource.onopen = () => updateKoneksi(true);
evtSource.onerror = () => updateKoneksi(false);
evtSource.addEventListener("ping", () => updateKoneksi(true));

evtSource.addEventListener("update", (e) => {
    updateKoneksi(true);
    const data = JSON.parse(e.data);
    if (!data.no || data.no === "---") return;

    if (data.time === lastEventTime) return;
    lastEventTime = data.time;

    document.getElementById("noAntrian").textContent = data.no;
    document.getElementById("loketTujuan").textContent = "Menuju " + data.loket;

    const loketNum = data.loket.match(/\d+/);
    if (loketNum) {
        const id = "loket" + loketNum[0];
        const el = document.getElementById(id);
        if (el) el.textContent = data.no;
    }

    // 🔔 Suara & panggilan
    bell.play().catch(()=>{});
    setTimeout(() => {
        playVoice(data);
    }, 1000);
});

// 🟡 Tombol aktifkan suara
document.getElementById("btnSuara").addEventListener("click", () => {
  const utter = new SpeechSynthesisUtterance("Suara aktif. Display siap digunakan.");
  utter.lang = "id-ID";
  speechSynthesis.speak(utter);
  document.getElementById("btnSuara").style.display = "none";
});
</script>
</body>
</html>
