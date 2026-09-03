<?php
include("../config/db.php");

$result = $conn->query("SELECT no_antrian, loket, created_at 
                        FROM antrian 
                        WHERE status='called' 
                        ORDER BY created_at DESC LIMIT 1");

if ($row = $result->fetch_assoc()) {
    $out = [
        "no" => $row['no_antrian'],
        "loket" => $row['loket'],
        "time" => strtotime($row['created_at'])
    ];
} else {
    $out = ["no"=>"---","loket"=>"-","time"=>time()];
}

header('Content-Type: application/json');
echo json_encode($out);
