<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "antrian_farmasi";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
