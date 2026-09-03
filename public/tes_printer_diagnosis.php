<?php
header("Content-Type: text/html; charset=utf-8");

function cmd($command) {
    exec($command . " 2>&1", $out, $status);
    return [$status, implode("\n", $out)];
}

function detectShareName() {
    exec("wmic printer get Name,ShareName,Default /format:list", $out);
    $share = null;
    $default = false;
    foreach ($out as $line) {
        $line = trim($line);
        if (strpos($line, "ShareName=") === 0) $share = substr($line, 10);
        if (strpos($line, "Default=TRUE") !== false && !empty($share)) {
            return $share;
        }
    }
    // fallback
    foreach ($out as $line) {
        if (strpos($line, "ShareName=") === 0 && trim(substr($line, 10)) !== "") {
            return trim(substr($line, 10));
        }
    }
    return null;
}

$autoFix = isset($_GET['fix']);
$results = [];
$share = detectShareName();

echo "<style>
body { font-family: Consolas, monospace; background: #111; color: #eee; padding: 20px; }
h1 { color: #00ff90; }
.ok { color: #00ff90; }
.fail { color: #ff5555; }
table { border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #333; padding: 8px 12px; }
th { background: #222; color: #fff; }
tr:nth-child(even) { background: #181818; }
button {
    background: #00c06b; color: white; border: none;
    padding: 12px 20px; font-size: 16px;
    border-radius: 8px; cursor: pointer; margin-bottom: 20px;
}
button:hover { background: #009e57; }
pre { background: #000; color: #0f0; padding: 10px; border-radius: 6px; overflow-x: auto; }
</style>";

echo "<h1>🖨️ Diagnosa Printer Thermal (Auto Check + Auto-Fix)</h1>";

if ($autoFix) {
    echo "<p>🔧 Menjalankan auto-fix...</p>";
    // Jalankan Print Spooler
    cmd("net start spooler");

    // Lepas & map ulang printer
    cmd("net use LPT1 /delete /y");
    if ($share) {
        cmd("net use LPT1 \\\\localhost\\$share /persistent:yes");
    }
}

// 1️⃣ Cek Print Spooler
[$status, $out] = cmd("sc query Spooler");
$results[] = [
    "Print Spooler",
    strpos($out, "RUNNING") !== false ? "Aktif ✅" : "❌ Tidak berjalan<br><code>net start spooler</code>",
    strpos($out, "RUNNING") !== false ? "ok" : "fail"
];

// 2️⃣ Deteksi printer default
if ($share) {
    $results[] = ["Printer Default", "Share ditemukan: <b>$share</b> ✅", "ok"];
} else {
    $results[] = ["Printer Default", "❌ Tidak ada printer share ditemukan", "fail"];
}

// 3️⃣ Cek mapping LPT1
[$s4, $map] = cmd("net use");
$results[] = [
    "Mapping LPT1",
    (strpos($map, "LPT1") !== false) ? "Terhubung ✅<br><pre>$map</pre>" : "❌ Tidak ada mapping aktif<br><code>net use LPT1 \\\\localhost\\$share /persistent:yes</code>",
    (strpos($map, "LPT1") !== false) ? "ok" : "fail"
];

// 4️⃣ Tes cetak dummy
$file = __DIR__ . "\\tes_diagnosis.txt";
file_put_contents($file, "=== TES CETAK PHP ===\r\n\r\nAntrian Farmasi\r\n" . date("d-m-Y H:i:s") . "\r\n\r\n");

if ($share) {
    [$s5, $out5] = cmd("copy /B \"$file\" \"\\\\localhost\\$share\"");
    $ok = strpos($out5, "1 file(s) copied") !== false;
    $results[] = [
        "Tes Cetak",
        $ok ? "✅ Struk tes berhasil dikirim ke printer." : "❌ Gagal kirim file ke printer.<br><pre>$out5</pre>",
        $ok ? "ok" : "fail"
    ];
} else {
    $results[] = ["Tes Cetak", "❌ Tidak ada printer untuk dites.", "fail"];
}

// 5️⃣ Tabel hasil
echo "<form method='get'><button type='submit' name='fix' value='1'>🔁 Perbaiki Otomatis & Uji Cetak Lagi</button></form>";
echo "<table><tr><th>Langkah</th><th>Status</th></tr>";
foreach ($results as $r) {
    echo "<tr><td>{$r[0]}</td><td class='{$r[2]}'>{$r[1]}</td></tr>";
}
echo "</table>";

echo "<hr><p><b>Tips:</b><br>
🧾 Pastikan printer dishare tanpa spasi (contoh: <code>ThermalPOS</code>).<br>
🔒 Jalankan XAMPP sebagai Administrator.<br>
🖨️ Tes manual bisa dilakukan dengan: <code>echo test > LPT1</code> di CMD.<br>
🔁 Tombol di atas akan memperbaiki Spooler, Mapping LPT1, dan uji cetak ulang otomatis.</p>";
?>
