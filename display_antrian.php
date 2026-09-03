<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="10"> <!-- reload otomatis tiap 10 detik -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Display Antrian + Live TV</title>
<style>
  body {
    margin: 0;
    background: #000;
    display: flex;
    height: 100vh;
    color: white;
    font-family: Arial, sans-serif;
  }
  .antrian {
    flex: 1.5;
    padding: 20px;
    background: linear-gradient(180deg, #003366, #0055aa);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }
  .tv {
    flex: 1;
    background: black;
  }
  .no-antri {
    font-size: 120px;
    font-weight: bold;
  }
  .loket {
    font-size: 60px;
  }
</style>
</head>
<body>

<div class="antrian">
  <?php
  // --- Contoh koneksi database MySQL (XAMPP)
  $conn = new mysqli("localhost", "root", "", "antrian_farmasi");
  $q = $conn->query("SELECT * FROM antrian ORDER BY id DESC LIMIT 1");
  $data = $q->fetch_assoc();
  echo "<div class='no-antri'>No: " . $data['nomor'] . "</div>";
  echo "<div class='loket'>Loket: " . $data['loket'] . "</div>";
  ?>
</div>

<div class="tv">
  <!-- Contoh siaran Trans7 via YouTube Live -->
  <iframe width="100%" height="100%"
  src="https://www.youtube.com/embed/live_stream?channel=UCWeg2Pkate69NFdBeuRFTAw&autoplay=1&mute=1"
  frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>
</iframe>

</div>

</body>
</html>
