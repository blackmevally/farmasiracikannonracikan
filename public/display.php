<?php
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Display Antrian Farmasi</title>
    <style>
        body { background:#2ecc71; color:white; font-family:Arial,sans-serif; text-align:center; margin:0; padding:0; }
        h1 { background:#27ae60; padding:20px; margin:0; font-size:40px; }
        .nomor { font-size:160px; font-weight:bold; margin:60px 0 20px; color:#f1c40f; text-shadow:3px 3px 8px rgba(0,0,0,0.4);}
        .loket { font-size:50px; background:#e74c3c; display:inline-block; padding:15px 40px; border-radius:10px;}
        .btn-aktif { position:fixed; top:20px; right:20px; padding:10px 20px; font-size:16px; border:none; border-radius:6px; background:#f1c40f; cursor:pointer; font-weight:bold;}
        .btn-aktif:hover { background:#f39c12; }
    </style>
</head>
<body>
    <h1>Antrian Farmasi</h1>
    <div id="nomor" class="nomor">---</div>
    <div id="loket" class="loket">Menuju -</div>
    <button id="btnSound" class="btn-aktif">🔊 Aktifkan Suara</button>

    <script>
        let lastSpoken = "";
        let soundEnabled = false;

        document.getElementById("btnSound").addEventListener("click", () => {
            soundEnabled = true;
            alert("✅ Suara berhasil diaktifkan. Panggilan berikutnya akan otomatis dibacakan.");
            document.getElementById("btnSound").style.display = "none";
        });

        // Fungsi konversi angka ke kata (Indonesia)
        function angkaKeKata(n) {
            n = parseInt(n, 10);
            if (isNaN(n)) return "";

            const satuan = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];

            function toWords(x) {
                if (x < 12) return satuan[x];
                else if (x < 20) return satuan[x - 10] + " belas";
                else if (x < 100) return satuan[Math.floor(x / 10)] + " puluh" + (x % 10 !== 0 ? " " + satuan[x % 10] : "");
                else if (x < 200) return "seratus" + (x - 100 !== 0 ? " " + toWords(x - 100) : "");
                else if (x < 1000) return satuan[Math.floor(x / 100)] + " ratus" + (x % 100 !== 0 ? " " + toWords(x % 100) : "");
                else if (x < 2000) return "seribu" + (x - 1000 !== 0 ? " " + toWords(x - 1000) : "");
                else if (x < 1000000) return toWords(Math.floor(x / 1000)) + " ribu" + (x % 1000 !== 0 ? " " + toWords(x % 1000) : "");
                else if (x < 1000000000) return toWords(Math.floor(x / 1000000)) + " juta" + (x % 1000000 !== 0 ? " " + toWords(x % 1000000) : "");
                else if (x < 1000000000000) return toWords(Math.floor(x / 1000000000)) + " milyar" + (x % 1000000000 !== 0 ? " " + toWords(x % 1000000000) : "");
                else return x.toString();
            }

            return toWords(n).trim();
        }

        function playTingTong(callback){
            if (!soundEnabled) return;
            let audio = new Audio("tingtong.mp3?t=" + Date.now());
            audio.play().then(()=>{
                audio.onended = ()=>{ if(callback) callback(); };
            }).catch(err=>console.log("Ting-tong gagal:", err));
        }

        function speakNumber(num, loket){
            if (!("speechSynthesis" in window) || !soundEnabled) return;

            let numInt = parseInt(num, 10);
            let numKata = isNaN(numInt) ? num : angkaKeKata(numInt);

            let msg = new SpeechSynthesisUtterance("Nomor antrian farmasi " + numKata + ", silakan menuju ke " + loket);
            msg.lang = "id-ID";
            msg.rate = 0.9;

            let voices = speechSynthesis.getVoices();
            let voice = voices.find(v => v.lang === "id-ID" && v.name.toLowerCase().includes("google"));
            if (!voice) voice = voices.find(v => v.lang === "id-ID");
            if (!voice && voices.length > 0) voice = voices[0];
            if (voice) msg.voice = voice;

            speechSynthesis.cancel();
            speechSynthesis.speak(msg);
        }

        async function checkUpdate(){
            try {
                let res = await fetch("status_display.php"); // API ambil data terbaru
                let data = await res.json();

                document.getElementById("nomor").innerText = data.no;
                document.getElementById("loket").innerText = "Menuju " + data.loket;

                let currentKey = data.no + "_" + data.time;
                if (soundEnabled && currentKey !== lastSpoken && data.no !== "---") {
                    playTingTong(()=>speakNumber(data.no, data.loket));
                    lastSpoken = currentKey;
                }
            } catch(e) {
                console.error("Gagal ambil data display", e);
            }
        }

        // Cek data tiap 3 detik
        setInterval(checkUpdate, 3000);
        checkUpdate();

        // Fallback kalau suara belum muncul
        window.speechSynthesis.onvoiceschanged = ()=>{ if(soundEnabled) checkUpdate(); };
    </script>
</body>
</html>
