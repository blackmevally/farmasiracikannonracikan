<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ambil 2 Tiket</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ===== LATAR BELAKANG ===== */
body {
    margin: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #1e8449, #27ae60, #2ecc71);
    background-size: 200% 200%;
    animation: gradientMove 6s infinite alternate;
    color: white;
    text-align: center;
    overflow: hidden;
}
@keyframes gradientMove {
    from { background-position: 0 0; }
    to { background-position: 100% 100%; }
}

/* ===== GAMBAR PANAH ===== */
.arrow {
    width: 80vw;
    max-width: 1000px;
    height: auto;
    transform: scaleY(0.8); /* Tinggi dikurangi 20% */
    margin-bottom: 60px;
    filter: drop-shadow(0 0 25px rgba(0,0,0,0.5));
    animation: pulse 1.3s infinite ease-in-out;
}
@keyframes pulse {
    0% { transform: scale(0.8,0.8); opacity: 0.95; }
    50% { transform: scale(0.84,0.84); opacity: 1; }
    100% { transform: scale(0.8,0.8); opacity: 0.95; }
}

/* ===== TEKS UTAMA ===== */
.text {
    font-size: 4.5vw;
    font-weight: 800;
    text-shadow: 2px 3px 15px rgba(0,0,0,0.6);
    margin-bottom: 25px;
    letter-spacing: 2px;
}

/* ===== TEKS INSTRUKSI ===== */
.instruksi {
    font-size: 2.3vw;
    line-height: 1.8;
    background: rgba(0,0,0,0.25);
    padding: 25px 60px;
    border-radius: 20px;
    display: inline-block;
    color: #fff7b0;
    box-shadow: 0 0 25px rgba(0,0,0,0.3);
    border: 2px solid rgba(255,255,255,0.25);
}

/* ===== ANIMASI FADE KESELURUHAN ===== */
.fade {
    animation: fadeInOut 10s ease forwards;
}
@keyframes fadeInOut {
    0% { opacity: 0; transform: scale(0.9); }
    15% { opacity: 1; transform: scale(1.05); }
    80% { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(0.95); }
}
</style>

<script>
// Otomatis kembali ke index.php setelah 7 detik
setTimeout(() => {
    window.location.href = "index.php";
}, 7000);
</script>
</head>

<body class="fade">
    <!-- Gambar Panah -->
    <img src="../config/assets/arrow.png" alt="Panah Arah" class="arrow">

    <!-- Teks utama -->
    <div class="text">SILAHKAN AMBIL 2 TIKET</div>

    <!-- Instruksi -->
    <div class="instruksi">
        1 UNTUK PETUGAS, KLIP DI CHECKLIST<br>
        1 UNTUK PASIEN
    </div>
</body>
</html>
