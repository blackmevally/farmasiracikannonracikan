<?php
// display_control.php
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';
$val = $_GET['val'] ?? null;
$controlFile = __DIR__ . "/../../config/display_state.json";

$state = file_exists($controlFile) ? json_decode(file_get_contents($controlFile), true) : [
    "mute" => false,
    "volume" => 0.6,
    "reset" => false
];

switch ($action) {
    case "mute":
        $state["mute"] = true;
        break;
    case "unmute":
        $state["mute"] = false;
        break;
    case "set_volume":
        $val = floatval($val);
        if ($val >= 0 && $val <= 1) $state["volume"] = $val;
        break;
    case "reset_display":
        $state["reset"] = true;
        break;
}

file_put_contents($controlFile, json_encode($state, JSON_PRETTY_PRINT));
echo json_encode(["status" => "ok", "state" => $state]);
?>
