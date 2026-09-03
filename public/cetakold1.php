<?php
include("../config/db.php");

if (isset($_POST['ambil']) && isset($_POST['next'])) {
    $noAntri = $_POST['next'];

    // Simpan ke DB
    $conn->query("INSERT INTO antrian (no_antrian, status, created_at) VALUES ('$noAntri','waiting',NOW())");

    // Isi struk
    $struk  = "     *** ANTRIAN FARMASI ***\n\n";
    $struk .= "       No. Antrian Anda:\n\n";
    // Center + font besar 4x
    $struk .= "\x1B\x21\x30";  // double height + double width
    $struk .= "           $noAntri\n";
    $struk .= "\x1B\x21\x00";  // normal font
    $struk .= "\n===============================\n";
    $struk .= "    Silakan tunggu panggilan.\n";
    $struk .= "         Terima kasih.\n";

    // Potong kertas otomatis (jika printer support)
    $struk .= "\n\n\n\n\n";
    $struk .= "\x1D\x56\x00";  // ESC/POS cut

    // Simpan struk ke file sementara
    $file = tempnam(sys_get_temp_dir(), "print");
    file_put_contents($file, $struk);

    // Cetak langsung ke LPT1 (karena sudah dimapping oleh VBS)
    $printCmd = "print /D:LPT1 \"$file\"";
    exec($printCmd, $printOutput, $printStatus);

    // Hapus file sementara
    unlink($file);

    if ($printStatus === 0) {
        header("Location: index.php?msg=sukses");
        exit;
    } else {
        echo "<script>alert('⚠️ Gagal mencetak struk!');</script>";
        error_log("Print gagal. CMD: $printCmd | Output: " . implode(', ', $printOutput));
    }
} else {
    header("Location: index.php");
    exit;
}
?>
