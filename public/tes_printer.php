<?php
$file = __DIR__ . "/tes.txt";
file_put_contents($file, "TEST CETAK PHP\n\n\n");

$cmd = "print /D:LPT1 \"$file\"";
exec($cmd, $out, $status);

if ($status === 0) {
    echo "✅ Printer berhasil mencetak tes.";
} else {
    echo "❌ Gagal mencetak ke LPT1. CMD: $cmd<br>Output: " . implode(", ", $out);
}
?>
