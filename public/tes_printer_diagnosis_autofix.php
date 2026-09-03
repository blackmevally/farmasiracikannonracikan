<?php
header("Content-Type: text/html; charset=utf-8");
echo "<h2>🖨️ Hasil Diagnosis Printer</h2>";
echo "<table><tr><th>Langkah</th><th>Status</th></tr>";

function step($name, $status, $color="green") {
  echo "<tr><td>$name</td><td class='$color'>$status</td></tr>";
}

// 1️⃣ Cek Print Spooler
exec("sc query spooler", $out1);
$out1 = implode("\n", $out1);
if (strpos($out1, "RUNNING") !== false) {
  step("Print Spooler", "Aktif ✅", "green");
} else {
  step("Print Spooler", "⚠️ Tidak aktif, mencoba menyalakan...", "yellow");
  exec("net start spooler", $fix1);
  sleep(2);
  exec("sc query spooler", $check2);
  if (strpos(implode("\n", $check2), "RUNNING") !== false) {
    step("Print Spooler", "Berhasil dinyalakan ✅", "green");
  } else {
    step("Print Spooler", "❌ Gagal menyalakan Spooler", "red");
  }
}

// 2️⃣ Cek Printer Default
exec('wmic printer get name,default', $printers);
$defaultPrinter = "Tidak ditemukan";
foreach ($printers as $p) {
  if (strpos($p, "TRUE") !== false) {
    $defaultPrinter = trim(explode("TRUE", $p)[0]);
  }
}
step("Share Printer", "🖨️ $defaultPrinter", $defaultPrinter != "Tidak ditemukan" ? "green" : "red");

$printerPath = "\\\\localhost\\" . preg_replace('/\s+/', '', $defaultPrinter);

// 3️⃣ Mapping LPT1
exec("net use LPT1 /delete /y");
exec("net use LPT1 \"$printerPath\" /persistent:yes", $mapOut, $mapStatus);
if ($mapStatus === 0) step("Mapping LPT1", "✅ Remapping printer berhasil", "green");
else step("Mapping LPT1", "❌ Gagal mapping printer", "red");

// 4️⃣ Tes Cetak
$tesFile = __DIR__ . "/tes.txt";
file_put_contents($tesFile, "Tes cetak otomatis antrian farmasi.\n");
$copyCmd = 'copy /B "' . $tesFile . '" "' . $printerPath . '"';
exec($copyCmd, $out, $ret);

if ($ret === 0) {
  step("Tes Cetak", "✅ Berhasil kirim file ke printer", "green");
} else {
  step("Tes Cetak", "❌ Gagal kirim file ke printer, mencoba Auto-Fix...", "red");

  // 🔁 AUTO FIX
  exec("net stop spooler");
  sleep(2);
  exec("net start spooler");
  exec("net use LPT1 /delete /y");
  exec("net use LPT1 \"$printerPath\" /persistent:yes");

  sleep(3);
  exec($copyCmd, $out2, $ret2);
  if ($ret2 === 0) step("Tes Cetak (AutoFix)", "✅ Berhasil setelah auto-recovery", "green");
  else step("Tes Cetak (AutoFix)", "❌ Masih gagal setelah perbaikan otomatis", "red");
}

echo "</table>";
echo "<div class='notice'>\n🔍 <b>MULAI DIAGNOSA PRINTER</b> ===> ✅ SELESAI.\n</div>";
?>
