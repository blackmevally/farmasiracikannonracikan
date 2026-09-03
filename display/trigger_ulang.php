<?php
// trigger_ulang.php
include("../config/db.php");

header('Content-Type: application/json');

if (!isset($_POST['no']) || !isset($_POST['loket'])) {
    echo json_encode(["status" => "error", "msg" => "Data tidak lengkap"]);
    exit;
}

$no = $_POST['no'];
$loket = $_POST['loket'];

echo json_encode([
    "status" => "ok",
    "latest" => [
        "no" => $no,
        "loket" => $loket,
        "time" => time()
    ]
]);
