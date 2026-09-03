<?php
include("../config/db.php");
date_default_timezone_set("Asia/Jakarta");

// Filter parameter
$jenis = isset($_GET['jenis']) ? strtolower($_GET['jenis']) : 'obat';
$today = date("Y-m-d");

// Query ambil antrian waiting
$sql = "
    SELECT 
        id, 
        no_antrian, 
        jenis, 
        DATE_FORMAT(created_at, '%H:%i:%s') AS waktu_ambil
    FROM antrian
    WHERE status='waiting' 
      AND DATE(created_at) = '$today'
    ORDER BY id ASC
";
$q = $conn->query($sql);

$data = [];
while ($r = $q->fetch_assoc()) {
    // Pastikan kolom jenis selalu valid
    $jenis_detected = strtolower($r['jenis']);
    if (empty($jenis_detected) || !in_array($jenis_detected, ['obat','racikan'])) {
        $prefix = strtoupper(substr($r['no_antrian'], 0, 1));
        $jenis_detected = ($prefix === 'R') ? 'racikan' : 'obat';
    }

    // Filter jika user minta spesifik
    if ($jenis !== 'all' && $jenis_detected !== $jenis) continue;

    $data[] = [
        'no_antrian' => strtoupper($r['no_antrian']),
        'jenis' => strtoupper($jenis_detected),
        'waktu_ambil' => $r['waktu_ambil']
    ];
}

// Response JSON
header("Content-Type: application/json; charset=utf-8");
echo json_encode([
    "tanggal" => $today,
    "total" => count($data),
    "data" => $data
], JSON_PRETTY_PRINT);
?>
