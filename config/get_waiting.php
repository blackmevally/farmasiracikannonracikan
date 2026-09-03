<?php
include("../config/db.php");

$today = date("Y-m-d");

function countWaiting($conn, $jenis, $today) {
    $q = $conn->query("SELECT COUNT(*) AS jml FROM antrian WHERE status='waiting' AND jenis='$jenis' AND DATE(created_at)='$today'");
    $r = $q->fetch_assoc();
    return intval($r['jml']);
}

echo json_encode([
    "obat" => countWaiting($conn, "obat", $today),
    "racikan" => countWaiting($conn, "racikan", $today)
]);
?>
