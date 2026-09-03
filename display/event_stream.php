<?php
/**
 * Server-Sent Events (SSE) – Versi Stabil 2025
 * ------------------------------------------------
 * Fitur utama:
 * ✅ Tidak reconnect terus
 * ✅ Ping tiap 3 detik agar koneksi tetap hidup
 * ✅ Anti buffering (Apache, Nginx, PHP-FPM)
 * ✅ Kirim event hanya jika log benar-benar berubah
 */

header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");
header("Access-Control-Allow-Origin: *");
header("X-Accel-Buffering: no"); // 🔒 Matikan buffering di Nginx/proxy

// Pastikan tidak ada buffer aktif
while (ob_get_level() > 0) ob_end_flush();
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', true);
ob_implicit_flush(true);

@set_time_limit(0); // jangan terminate karena waktu lama

// === PATH FILE LOG ===
$logFile = __DIR__ . "/../display/log_panggilan.txt";

// === VARIABEL UTAMA ===
$lastSentMTime = 0;
$lastPing = time();
$pingInterval = 3;   // kirim ping tiap 3 detik
$checkInterval = 1;  // cek file tiap 1 detik

// === Fungsi Kirim SSE ===
function sendSSE($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    // Tambah padding untuk memaksa flush pada proxy
    echo str_repeat(" ", 4096) . "\n";
    @ob_flush();
    @flush();
}

// === Loop utama ===
while (true) {
    clearstatcache(false, $logFile);

    if (file_exists($logFile)) {
        $currentMTime = filemtime($logFile) ?: 0;

        // Kirim event hanya jika file berubah
        if ($currentMTime > $lastSentMTime) {
            $content = trim(file_get_contents($logFile));
            if ($content !== "") {
                $data = json_decode($content, true);
                if (!$data || !isset($data['no'])) {
                    $data = ["no" => "---", "loket" => "?", "time" => time()];
                }

                // Pastikan jenis benar
                $jenis = $data['jenis'] ?? '';
                if (empty($jenis)) {
                    $no = strtoupper($data['no']);
                    $jenis = (strpos($no, 'R') === 0) ? 'racikan' : 'obat';
                }
                $data['jenis'] = $jenis;

                // ✅ Pastikan teks "Loket" tetap ada
                if (!empty($data['loket']) && stripos($data['loket'], 'loket') === false) {
                    $data['loket'] = "Loket " . trim($data['loket']);
                }

                // Gunakan timestamp dari log (jangan ubah setiap loop)
                if (empty($data['time'])) {
                    $data['time'] = $currentMTime ?: time();
                }

                // Kirim event ke client
                sendSSE("update", $data);
                $lastSentMTime = $currentMTime;
            }
        }
    }

    // === Kirim ping reguler ===
    if (time() - $lastPing >= $pingInterval) {
        sendSSE("ping", ["t" => time()]);
        $lastPing = time();
    }

    // Hindari CPU tinggi
    usleep($checkInterval * 1000000);
}
?>
