<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['antrian_admin'])) {
    http_response_code(403);
    exit('Akses admin diperlukan.');
}

$configFile = __DIR__ . '/../config/config_suara.json';
$defaults = [
    'template_obat' => 'Panggilan pengambilan obat, nomor antrian {nomor}, silakan menuju ke {loket}',
    'template_racikan' => 'Panggilan pengambilan obat racikan, nomor antrian {nomor}, silakan menuju ke {loket}',
    'voice' => 'default',
    'lang' => 'id-ID',
    'volume' => 1,
    'rate' => 1
];

if (!isset($_SESSION['suara_csrf'])) {
    $_SESSION['suara_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['suara_csrf'];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Permintaan tidak valid.');
    }

    $lang = in_array($_POST['lang'] ?? '', ['id-ID', 'en-US'], true) ? $_POST['lang'] : 'id-ID';
    $volume = min(1, max(0, (float)($_POST['volume'] ?? 1)));
    $rate = min(2, max(0.5, (float)($_POST['rate'] ?? 1)));
    $data = [
        'template_obat' => trim((string)($_POST['template_obat'] ?? $defaults['template_obat'])),
        'template_racikan' => trim((string)($_POST['template_racikan'] ?? $defaults['template_racikan'])),
        'voice' => trim((string)($_POST['voice'] ?? 'default')),
        'lang' => $lang,
        'volume' => $volume,
        'rate' => $rate
    ];

    file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    $saved = true;
}

$config = $defaults;
if (is_file($configFile)) {
    $loaded = json_decode((string)file_get_contents($configFile), true);
    if (is_array($loaded)) {
        $config = array_merge($defaults, $loaded);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🔊 Pengaturan Template Suara</title>
<style>
body { font-family: "Segoe UI", sans-serif; background: #1e8449; color: white; margin: 0; padding: 30px; }
h1 { font-size: 28px; margin-top: 0; }
a { color: #fff; text-decoration: none; font-weight: bold; margin-right: 15px; }
.container { background: rgba(255,255,255,0.1); padding: 25px; border-radius: 12px; max-width: 700px; margin: 20px auto; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
label { display: block; margin-top: 10px; font-weight: bold; }
textarea, select, input[type=number] { width: 100%; box-sizing: border-box; padding: 10px; border-radius: 8px; border: none; margin-top: 5px; font-size: 16px; }
button { padding: 12px 25px; font-size: 16px; border: none; border-radius: 8px; margin-top: 15px; cursor: pointer; font-weight: bold; }
#btnSave { background: #27ae60; color: white; }
#btnTest { background: #f1c40f; color: #2c3e50; margin-left: 10px; }
.notice { background: rgba(255,255,255,0.2); padding: 8px; border-radius: 8px; margin-bottom: 10px; }
</style>
</head>
<body>
<h1>🔊 Pengaturan Template Suara</h1>
<div class="notice">
    <a href="dashboard.php">🏠 Dashboard</a> |
    <a href="loket.php">📦 Loket</a> |
    <a href="reset.php">♻️ Reset Harian</a>
</div>

<div class="container">
    <?php if ($saved): ?>
        <div style="background:#2ecc71;padding:10px;border-radius:6px;">✅ Pengaturan suara berhasil disimpan.</div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label>🎙️ Template Suara OBAT:</label>
        <small>Gunakan variabel: <b>{nomor}</b> dan <b>{loket}</b></small>
        <textarea name="template_obat" rows="3" required><?= htmlspecialchars((string)$config['template_obat'], ENT_QUOTES, 'UTF-8') ?></textarea>

        <label>🎙️ Template Suara RACIKAN:</label>
        <small>Gunakan variabel: <b>{nomor}</b> dan <b>{loket}</b></small>
        <textarea name="template_racikan" rows="3" required><?= htmlspecialchars((string)$config['template_racikan'], ENT_QUOTES, 'UTF-8') ?></textarea>

        <label>🗣️ Jenis Suara:</label>
        <select name="voice" id="voiceSelect"><option value="default">Default Browser</option></select>

        <label>🌐 Bahasa:</label>
        <select name="lang" id="langSelect">
            <option value="id-ID" <?= $config['lang'] === 'id-ID' ? 'selected' : '' ?>>Indonesia</option>
            <option value="en-US" <?= $config['lang'] === 'en-US' ? 'selected' : '' ?>>English</option>
        </select>

        <label>🔉 Volume (0–1):</label>
        <input type="number" name="volume" step="0.1" min="0" max="1" value="<?= htmlspecialchars((string)$config['volume'], ENT_QUOTES, 'UTF-8') ?>">

        <label>⚡ Kecepatan (0.5–2):</label>
        <input type="number" name="rate" step="0.1" min="0.5" max="2" value="<?= htmlspecialchars((string)$config['rate'], ENT_QUOTES, 'UTF-8') ?>">

        <div>
            <button id="btnSave" type="submit">💾 Simpan Template</button>
            <button id="btnTest" type="button">🔁 Uji Suara</button>
        </div>
    </form>
</div>

<script>
function loadVoices() {
    const select = document.getElementById('voiceSelect');
    const voices = speechSynthesis.getVoices();
    const currentVoice = <?= json_encode((string)$config['voice']) ?>;
    select.innerHTML = '';
    voices.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.name;
        opt.textContent = `${v.name} (${v.lang})`;
        if (v.name === currentVoice) opt.selected = true;
        select.appendChild(opt);
    });
    if (voices.length === 0) {
        const opt = document.createElement('option');
        opt.value = 'default';
        opt.textContent = 'Suara tidak tersedia (reload halaman)';
        select.appendChild(opt);
    }
}
window.speechSynthesis.onvoiceschanged = loadVoices;
loadVoices();

document.getElementById('btnTest').addEventListener('click', () => {
    const templateObat = document.querySelector('textarea[name=template_obat]').value;
    const templateRacikan = document.querySelector('textarea[name=template_racikan]').value;
    const voiceName = document.getElementById('voiceSelect').value;
    const lang = document.getElementById('langSelect').value;
    const volume = parseFloat(document.querySelector('input[name=volume]').value) || 1;
    const rate = parseFloat(document.querySelector('input[name=rate]').value) || 1;
    const testText = confirm('🔊 Uji suara RACIKAN? Tekan Cancel untuk uji OBAT.') ? templateRacikan : templateObat;
    const teks = testText.replaceAll('{nomor}', 'sepuluh').replaceAll('{loket}', 'loket satu');
    const utter = new SpeechSynthesisUtterance(teks);
    utter.lang = lang;
    utter.volume = Math.min(1, Math.max(0, volume));
    utter.rate = Math.min(2, Math.max(0.5, rate));
    const selectedVoice = speechSynthesis.getVoices().find(v => v.name === voiceName);
    if (selectedVoice) utter.voice = selectedVoice;
    speechSynthesis.cancel();
    speechSynthesis.speak(utter);
});
</script>
</body>
</html>
