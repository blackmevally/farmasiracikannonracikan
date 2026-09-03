<?php
include("../config/db.php");
date_default_timezone_set("Asia/Jakarta");

// Ambil parameter dari JS
$loket = $_POST['loket'] ?? '';
$jenis = $_POST['jenis'] ?? 'obat';

// Cek validasi
if (empty($loket)) {
    echo json_encode(["status" => "error", "message" => "Loket tidak dipilih"]);
    exit;
}

// Cari antrian waiting paling awal untuk jenis ini
$sql = "SELECT * FROM antrian 
        WHERE status='waiting' 
          AND jenis='$jenis' 
          AND DATE(created_at)=CURDATE()
        ORDER BY id ASC 
        LIMIT 1";
$q = $conn->query($sql);

if ($q->num_rows === 0) {
    echo json_encode(["status" => "empty", "message" => "Tidak ada antrian menunggu"]);
    exit;
}

$row = $q->fetch_assoc();
$id = $row['id'];
$no = $row['no_antrian'];

// Update status jadi 'called'
$conn->query("UPDATE antrian 
              SET status='called', 
                  loket='$loket', 
                  updated_at=NOW() 
              WHERE id=$id");

// Buat data untuk dikirim ke display
$data = [
    "no" => $no,
    "loket" => "Loket " . $loket,
    "jenis" => $jenis,
    "time" => time()
];

// Tulis ke file log (akan dibaca oleh event_stream.php)
$logFile = "../display/log_panggilan.txt";
file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT));

// Balas ke front-end panggil.php
echo json_encode([
    "status" => "ok",
    "no" => $no,
    "loket" => "Loket " . preg_replace('/[^0-9]/', '', $loket), // pastikan selalu format "Loket X"
    "jenis" => $jenis
]);

?>
