<?php
// trigger_tts.php
header('Content-Type: application/json');
require_once("../config/db.php");

$no = $_POST["no"] ?? "";
$loket = $_POST["loket"] ?? "";

if (!$no || !$loket) {
    echo json_encode(["status" => "error", "msg" => "Data tidak lengkap"]);
    exit;
}

// Simpan ke cache (file json) agar SSE bisa tangkap
$data = [
    "no" => $no,
    "loket" => $loket,
    "jenis" => (strpos(strtolower($no), "r") === 0 ? "racikan" : "obat"),
    "timestamp" => time()
];

file_put_contents("../config/tts_trigger.json", json_encode($data, JSON_PRETTY_PRINT));
echo json_encode(["status" => "ok"]);
