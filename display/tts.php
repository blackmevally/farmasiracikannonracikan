<?php
require __DIR__ . '/vendor/autoload.php';
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;

header('Content-Type: application/json');

if (!isset($_POST['no']) || !isset($_POST['loket']) || !isset($_POST['time'])) {
    echo json_encode(["error" => "Parameter tidak lengkap"]);
    exit;
}

$no = $_POST['no'];
$loket = $_POST['loket'];
$time = $_POST['time'];

// Fungsi konversi angka ke kata
function angkaKeKata($n) {
    $n = intval($n);
    $satuan = ["nol","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh","sebelas"];
    $f = function($x) use (&$f, $satuan) {
        if ($x < 12) return $satuan[$x];
        if ($x < 20) return $f($x-10) . " belas";
        if ($x < 100) return $f(intval($x/10)) . " puluh" . ($x%10!==0 ? " ".$f($x%10) : "");
        if ($x < 200) return "seratus" . ($x-100!==0 ? " ".$f($x-100) : "");
        if ($x < 1000) return $f(intval($x/100)) . " ratus" . ($x%100!==0 ? " ".$f($x%100) : "");
        if ($x < 2000) return "seribu" . ($x-1000!==0 ? " ".$f($x-1000) : "");
        if ($x < 1000000) return $f(intval($x/1000)) . " ribu" . ($x%1000!==0 ? " ".$f($x%1000) : "");
        return strval($x);
    };
    return $f($n);
}

$text = "Nomor antrian " . angkaKeKata($no) . ", silakan menuju ke " . $loket;

$client = new TextToSpeechClient();
$input = (new SynthesisInput())->setText($text);

$voice = (new VoiceSelectionParams())
    ->setLanguageCode('id-ID')
    ->setSsmlGender(\Google\Cloud\TextToSpeech\V1\SsmlVoiceGender::FEMALE);

$audioConfig = (new AudioConfig())->setAudioEncoding(AudioEncoding::MP3);

$response = $client->synthesizeSpeech($input, $voice, $audioConfig);
$audioContent = $response->getAudioContent();

$filename = "tts_{$no}_{$time}.mp3";
$folder = __DIR__ . "/tts_audio/";
if (!is_dir($folder)) mkdir($folder, 0777, true);
file_put_contents($folder . $filename, $audioContent);

$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$url = $base . "/tts_audio/" . $filename;

echo json_encode(["url" => $url]);
