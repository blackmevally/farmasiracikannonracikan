<?php
include("../config/db.php");
date_default_timezone_set("Asia/Jakarta");
$jenis = $_GET['jenis'] ?? 'obat';
$today = date("Y-m-d");

$q = $conn->query("
    SELECT id, no_antrian, jenis, DATE_FORMAT(created_at, '%H:%i:%s') AS waktu_ambil
    FROM antrian
    WHERE status='waiting' AND DATE(created_at)='$today'
    ORDER BY id ASC
");

$data = [];
while ($r = $q->fetch_assoc()) {
    if (empty($r['jenis']) || !in_array($r['jenis'], ['obat','racikan'])) {
        $prefix = strtoupper(substr($r['no_antrian'], 0, 1));
        $r['jenis'] = ($prefix === 'R') ? 'racikan' : 'obat';
    }
    if ($jenis !== 'all' && $r['jenis'] !== $jenis) continue;
    $data[] = [
        'no_antrian' => $r['no_antrian'],
        'jenis' => strtoupper($r['jenis']),
        'waktu_ambil' => $r['waktu_ambil']
    ];
}
header("Content-Type: application/json");
echo json_encode($data);
?>
