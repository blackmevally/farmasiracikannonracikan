<?php
include("../config/db.php");
date_default_timezone_set("Asia/Jakarta");

$loket = $_POST['loket'] ?? '';
$no = $_POST['no'] ?? '';
$jenis = $_POST['jenis'] ?? 'obat';

// Jika tidak dikirim nomor, cari nomor terakhir yang sudah dipanggil di loket ini
if (empty($no) && !empty($loket)) {
    $q = $conn->query("SELECT no_antrian, jenis 
                       FROM antrian 
                       WHERE loket='$loket' AND status='called' 
                       ORDER BY updated_at DESC LIMIT 1");
    if ($q->num_rows > 0) {
        $r = $q->fetch_assoc();
        $no = $r['no_antrian'];
        $jenis = $r['jenis'];
    }
}

// Validasi
if (empty($loket) || empty($no)) {
    echo json_encode([
        "status" => "error",
        "message" => "Belum ada nomor yang dipanggil untuk loket ini."
    ]);
    exit;
}

// Pastikan antrian ada
$q = $conn->prepare("SELECT id, no_antrian, jenis FROM antrian WHERE no_antrian=? LIMIT 1");
$q->bind_param("s", $no);
$q->execute();
$res = $q->get_result();
if ($res->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Nomor antrian tidak ditemukan"]);
    exit;
}
$row = $res->fetch_assoc();

// Update status tetap "called"
$conn->query("UPDATE antrian 
              SET status='called', loket='$loket', updated_at=NOW() 
              WHERE id=" . $row['id']);

// Kirim data ke display — tambahkan flag "ulang" agar TTS tetap bunyi
$data = [
    "no" => $no,
    "loket" => "Loket " . $loket,
    "jenis" => $jenis,
    "ulang" => true,   // 🟢 flag penting untuk TTS ulang
    "time" => time()
];

// Tulis ke log display
$logFile = "../display/log_panggilan.txt";
file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT));

// Balasan ke frontend
echo json_encode([
    "status" => "ok",
    "no" => $no,
    "loket" => "Loket " . preg_replace('/[^0-9]/', '', $loket),
    "jenis" => $jenis
]);
?>
