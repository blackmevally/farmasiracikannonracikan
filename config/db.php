<?php
declare(strict_types=1);

/**
 * Database configuration.
 *
 * Values can be supplied through environment variables so production
 * credentials are not stored in the repository.
 */
$host = getenv('ANTRIAN_DB_HOST') ?: 'localhost';
$user = getenv('ANTRIAN_DB_USER') ?: 'root';
$pass = getenv('ANTRIAN_DB_PASS') ?: '';
$db   = getenv('ANTRIAN_DB_NAME') ?: 'antrian_farmasi';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die('Koneksi database gagal.');
}

$conn->set_charset('utf8mb4');
?>
