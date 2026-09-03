<?php
/**
 * Stable Server-Sent Events (SSE) for Antrian Display
 * Realtime push untuk display tanpa timeout
 */

ignore_user_abort(true);
set_time_limit(0);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no'); // disable buffering (Nginx)

$file = __DIR__ . "/last_call.json";

// buat file jika belum ada
if (!file_exists($file)) {
    file_put_contents($file, json_encode(["no" => "---", "loket" => "-", "time" => time(), "type" => "init"]));
}

$lastModified = 0;
$pingCounter = 0;

while (!connection_aborted()) {
    clearstatcache(true, $file);

    $mtime = filemtime($file);
    if ($mtime !== $lastModified) {
        $lastModified = $mtime;
        $data = json_decode(file_get_contents($file), true);

        echo "event: update\n";
        echo "data: " . json_encode($data) . "\n\n";
        @ob_flush();
        @flush();
    }

    // heartbeat setiap 15 detik agar koneksi tidak putus
    $pingCounter++;
    if ($pingCounter >= 15) {
        echo "event: ping\n";
        echo "data: {}\n\n";
        @ob_flush();
        @flush();
        $pingCounter = 0;
    }

    usleep(500000); // 0.5 detik
}
