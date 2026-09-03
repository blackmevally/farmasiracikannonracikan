<?php
include("../config/db.php");
header('Content-Type: application/json');

// Ambil data dari POST
$no = $_POST['no'] ?? '';
$loket = $_POST['loket'] ?? '';

if (empty($no) || empty($loket)) {
    echo json_encode(["status" => "error", "msg" => "Data tidak lengkap"]);
    exit;
}

// Kirim respons agar display tahu harus memutar TTS
echo json_encode([
    "status" => "ok",
    "no" => $no,
    "loket" => $loket,
    "time" => time()
]);
