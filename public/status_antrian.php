<?php
include("../config/db.php");

// Hitung jumlah waiting
$count = $conn->query("SELECT COUNT(*) as jml FROM antrian WHERE status='waiting'")->fetch_assoc();
$jml_waiting = $count['jml'];

// Ambil daftar waiting
$list = $conn->query("SELECT no_antrian FROM antrian WHERE status='waiting' ORDER BY id ASC");

$response = [
    "jumlah" => $jml_waiting,
    "daftar" => []
];

while ($row = $list->fetch_assoc()) {
    $response["daftar"][] = $row['no_antrian'];
}

header('Content-Type: application/json');
echo json_encode($response);
