<?php
header('Content-Type: application/json');
$configFile = __DIR__ . '/config_suara.json';

// Jika belum ada, buat default dua template
if (!file_exists($configFile)) {
    $default = [
        "template_obat" => "Panggilan pengambilan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
        "template_racikan" => "Panggilan pengambilan obat racikan, nomor antrian {nomor}, silakan menuju ke {loket}",
        "voice" => "default",
        "lang" => "id-ID",
        "volume" => 1,
        "rate" => 1
    ];
    file_put_contents($configFile, json_encode($default, JSON_PRETTY_PRINT));
}

echo file_get_contents($configFile);
?>
