<?php
include("../config/db.php");
date_default_timezone_set("Asia/Jakarta");

// ==========================
// Validasi input
// ==========================
$jenis = $_POST['jenis'] ?? 'obat';
if (!in_array($jenis, ['obat', 'racikan'])) die("Jenis antrian tidak valid.");

// ==========================
// HITUNG NOMOR BERIKUTNYA
// ==========================
$prefix = ($jenis == 'racikan') ? 'R' : 'O';
$q = $conn->prepare("SELECT no_antrian FROM antrian 
                     WHERE jenis=? AND DATE(created_at)=CURDATE()
                     ORDER BY id DESC LIMIT 1");
$q->bind_param("s", $jenis);
$q->execute();
$res = $q->get_result();
$lastNo = 0;
if ($row = $res->fetch_assoc()) {
    if (preg_match('/\d+/', $row['no_antrian'], $num)) {
        $lastNo = intval($num[0]);
    }
}
$nextNo = $prefix . sprintf("%03d", $lastNo + 1);

// ==========================
// SIMPAN KE DATABASE
// ==========================
$stmt = $conn->prepare("INSERT INTO antrian (no_antrian, jenis, status, created_at) VALUES (?, ?, 'waiting', NOW())");
$stmt->bind_param("ss", $nextNo, $jenis);
$stmt->execute();

// ==========================
// FUNGSI CETAK ESC/POS
// ==========================
function cmd($command) {
    exec($command . " 2>&1", $output, $status);
    return [$status, implode("\n", $output)];
}

function detectPrinterShare() {
    exec("wmic printer get ShareName,Default /format:list", $out);
    $share = null;
    foreach ($out as $line) {
        $line = trim($line);
        if (strpos($line, "ShareName=") === 0) {
            $share = substr($line, 10);
        }
        if (strpos($line, "Default=TRUE") !== false && !empty($share)) {
            return $share;
        }
    }
    return "ThermalPOS";
}

$ESC = chr(27);
$GS  = chr(29);
$ALIGN_CENTER = $ESC . "a" . chr(1);
$DOUBLE_SIZE  = $ESC . "!" . chr(56);
$NORMAL_SIZE  = $ESC . "!" . chr(0);
$BOLD_ON      = $ESC . "E" . chr(1);
$BOLD_OFF     = $ESC . "E" . chr(0);
$CUT          = $GS . "V" . chr(1);
$FEED6        = $ESC . "d" . chr(6);
$lineBreak    = "\r\n";

function buatStruk($no, $jenis, $tipe = "pasien") {
    global $ALIGN_CENTER, $DOUBLE_SIZE, $NORMAL_SIZE, $BOLD_ON, $BOLD_OFF, $CUT, $FEED6, $lineBreak;
    $judulJenis = (strtoupper(substr($no, 0, 1)) === 'R') ? "ANTRIAN OBAT RACIKAN" : "ANTRIAN OBAT BIASA";
    $str  = $ALIGN_CENTER;
    $str .= "========================" . $lineBreak;
    $str .= "APOTEK / FARMASI" . $lineBreak;
    $str .= "========================" . $lineBreak . $lineBreak;
    $str .= $judulJenis . $lineBreak . $lineBreak;
    $str .= "Nomor Antrian:" . $lineBreak;
    $str .= $BOLD_ON . $DOUBLE_SIZE . $no . $lineBreak . $BOLD_OFF . $NORMAL_SIZE;
    $str .= "------------------------" . $lineBreak;
    $str .= "Tanggal: " . date("d-m-Y H:i:s") . $lineBreak;
    if ($tipe === "pasien") {
        $str .= "Lembar untuk pasien" . $lineBreak;
        $str .= "Harap menunggu panggilan" . $lineBreak;
    } else {
        $str .= "Lembar untuk petugas" . $lineBreak;
        $str .= "Tempel pada lembar checklist" . $lineBreak;
    }
    $str .= "========================" . $lineBreak;
    $str .= $FEED6 . $CUT;
    return $str;
}

// Buat dua lembar (pasien + petugas)
$tempFile = __DIR__ . "\\struk.txt";
$struk  = buatStruk($nextNo, $jenis, "pasien");
$struk .= buatStruk($nextNo, $jenis, "petugas");
file_put_contents($tempFile, $struk);

// ==========================
// CETAK
// ==========================
$printerShare = detectPrinterShare();
cmd("net use LPT1 /delete /y");
cmd("net use LPT1 \\\\localhost\\$printerShare /persistent:yes");

$cmdPrint = "copy /B \"$tempFile\" LPT1";
[$status, $output] = cmd($cmdPrint);

if ($status !== 0) {
    cmd("net start spooler");
    sleep(2);
    cmd("net use LPT1 /delete /y");
    cmd("net use LPT1 \\\\localhost\\$printerShare /persistent:yes");
    [$status2, $output2] = cmd($cmdPrint);

    if ($status2 !== 0) {
        error_log("Gagal cetak. CMD: $cmdPrint | Output: $output2");
        header("Location: index.php?error=1");
        exit;
    }
}

// ==========================
// REDIRECT KE HALAMAN SELESAI
// ==========================
header("Location: selesai.php?no=" . urlencode($nextNo));
exit;
?>
