<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

$stateFile = __DIR__ . "/../../config/display_state.json";

// Pastikan file ada dan format default-nya valid
if (!file_exists($stateFile)) {
    file_put_contents($stateFile, json_encode([
        "mute" => false,
        "volume" => 0.6,
        "reset" => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$state = json_decode(file_get_contents($stateFile), true);
if (!is_array($state)) $state = ["mute" => false, "volume" => 0.6, "reset" => false];

$action = $_GET["action"] ?? null;
$val = $_GET["val"] ?? null;
$response = ["status" => "ok", "msg" => ""];

switch ($action) {
    case "mute":
        $state["mute"] = true;
        $response["msg"] = "Display dimute";
        break;

    case "unmute":
        $state["mute"] = false;
        $response["msg"] = "Display unmuted";
        break;

    case "set_volume":
        $v = floatval($val);
        if ($v < 0) $v = 0;
        if ($v > 1) $v = 1;
        $state["volume"] = $v;
        $response["msg"] = "Volume diatur ke {$v}";
        break;

    case "reset_display":
        $state["reset"] = true;
        $response["msg"] = "Display akan direset";
        break;

    default:
        $response = ["status" => "error", "msg" => "Aksi tidak dikenal"];
}

file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
