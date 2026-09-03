<?php
include("../config/db.php");

// 🔁 Jika ada panggilan ulang manual dari panggil.php
if (isset($_GET['ulang']) && $_GET['ulang'] == 1 && isset($_GET['no']) && isset($_GET['loket'])) {
    echo json_encode([
        "latest" => [
            "no" => $_GET['no'],
            "loket" => $_GET['loket'],
            "time" => time()
        ],
        "top3" => []
    ]);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Ambil panggilan terakhir
$res = $conn->query("SELECT no_antrian, loket, created_at FROM antrian WHERE status='called' ORDER BY created_at DESC LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $latest = [
        "no" => $row['no_antrian'],
        "loket" => $row['loket'],
        "time" => strtotime($row['created_at'])
    ];
} else {
    $latest = ["no" => "---", "loket" => "-", "time" => time()];
}

// Ambil 3 nomor terakhir per loket
$res2 = $conn->query("SELECT loket, no_antrian, MAX(created_at) as t FROM antrian WHERE status='called' GROUP BY loket ORDER BY t DESC LIMIT 3");
$top = [];
while ($r2 = $res2->fetch_assoc()) {
    $top[] = ["loket" => $r2['loket'], "no" => $r2['no_antrian']];
}
for ($i = count($top); $i < 3; $i++) {
    $top[] = ["loket" => "-", "no" => "---"];
}

echo json_encode(["latest" => $latest, "top3" => $top]);
