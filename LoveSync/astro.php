<?php
/* ==========================================
   SESSION + DB
========================================== */

include "assets/connection.php";

if(!isset($_SESSION['user_id'])){
 header("Location: login.php");
 exit();
}

$user_id = $_SESSION['user_id'];

/* ==========================================
   FETCH RELATIONSHIP
========================================== */

$relationship =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM relationships
 WHERE partner1_id='$user_id'
 OR partner2_id='$user_id'
 ")
);

if(!$relationship){
 die("No relationship found ❌");
}

$relationship_id = $relationship['id'];

/* ==========================================
   FETCH PARTNERS
========================================== */

$p1 =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM users
 WHERE id='{$relationship['partner1_id']}'
 ")
);

$p2 =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM users
 WHERE id='{$relationship['partner2_id']}'
 ")
);

/* ==========================================
   FETCH STORED ASTRO REPORT
========================================== */

$astro =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM astro_couple_reports
 WHERE relationship_id='$relationship_id'
 ")
);

/* ==========================================
   MANUAL COMPATIBILITY CHECK
========================================== */

$manualResult = null;
$apiError = null;

if(isset($_POST['manualCheck'])){

    // Format time properly (HH:MM only, no seconds)
    $boy_time = substr($_POST['boy_tob'], 0, 5);
    $girl_time = substr($_POST['girl_tob'], 0, 5);

    $data = [
        "boy_dob" => date("d/m/Y", strtotime($_POST['boy_dob'])),
        "boy_tob" => $boy_time,
        "boy_tz" => "5.5",
        "boy_lat" => $_POST['boy_lat'],
        "boy_lon" => $_POST['boy_lon'],
        "girl_dob" => date("d/m/Y", strtotime($_POST['girl_dob'])),
        "girl_tob" => $girl_time,
        "girl_tz" => "5.5",
        "girl_lat" => $_POST['girl_lat'],
        "girl_lon" => $_POST['girl_lon'],
    ];

    $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);

    $ch = curl_init();
    
    curl_setopt_array($ch, array(
        CURLOPT_URL => 'http://127.0.0.1:5004/astro-match',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)){
        $apiError = "cURL Error: " . curl_error($ch);
    }
    
    curl_close($ch);

    if($response){
        $manualResult = json_decode($response, true);
        
        if($manualResult === null || isset($manualResult['error'])){
            $apiError = "API Error (HTTP $httpCode): " . ($manualResult['error'] ?? $response);
        }
    } else {
        $apiError = "No response from API (HTTP $httpCode)";
    }
}

/* ==========================================
   FOREVER ANALYTICS
========================================== */

$loveScore = 0;

$chat =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT love_score_percentage
 FROM chat_reports
 WHERE relationship_id='$relationship_id'
 ")
);

if($chat){
 $loveScore = $chat['love_score_percentage'];
}

$astroScore = 0;

if($astro){
 preg_match('/\d+/', $astro['guna_milan_score'], $match);
 $astroScore = $match[0] ?? 0;
}

$foreverScore = round((($astroScore/36)*100 + $loveScore)/2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · Astrology Compatibility</title>
  <!-- Google Font & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(145deg, #fff3f8 0%, #f1e4ff 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 1.5rem;
      position: relative;
    }

    /* background bubbles */
    .bg-bubble {
      position: fixed;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle at 30% 30%, rgba(255, 175, 200, 0.4), rgba(205, 150, 255, 0.2));
      border-radius: 50%;
      top: -50px;
      left: -50px;
      z-index: 0;
      filter: blur(50px);
    }

    .bg-bubble2 {
      bottom: -50px;
      right: -30px;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle at 70% 70%, #ffe0f0, #e8caff);
      position: fixed;
      border-radius: 50%;
      filter: blur(70px);
      z-index: 0;
    }

    /* main container */
    .wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 1200px;
      margin: 1rem auto;
    }

    /* navigation (same as other pages) */
    .navbar {
      background: rgba(255, 255, 255, 0.6);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.8);
      border-radius: 3rem;
      padding: 0.8rem 2rem;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px -10px rgba(140, 80, 130, 0.2);
    }

    .nav-logo {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 700;
      font-size: 1.6rem;
      background: linear-gradient(135deg, #e3648c, #a07bda);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .nav-links {
      display: flex;
      flex-wrap: wrap;
      gap: 0.8rem 1.5rem;
      align-items: center;
    }

    .nav-links a {
      text-decoration: none;
      color: #4f3a5a;
      font-weight: 500;
      transition: 0.2s;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .nav-links a:hover {
      color: #d4588a;
    }

    .nav-links .btn-link {
      background: rgba(255, 255, 255, 0.8);
      border-radius: 2rem;
      padding: 0.4rem 1.2rem;
      border: 1px solid #e2bcdb;
    }

    .nav-links .btn-link:hover {
      background: white;
    }

    /* glass card */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 2.5rem;
      box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
      padding: 2rem 2.2rem;
      margin-bottom: 2rem;
      width: 100%;
    }

    h1 {
      font-weight: 700;
      font-size: 2.4rem;
      color: #32243d;
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    h2 {
      font-weight: 600;
      font-size: 1.8rem;
      color: #32243d;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.7rem;
    }

    h2 i, h3 i {
      color: #e27c9f;
    }

    h3 {
      font-weight: 600;
      font-size: 1.4rem;
      color: #32243d;
      margin: 1.5rem 0 1rem;
    }

    .error-box {
      background: #ffebee;
      border: 1px solid #f5c2c2;
      color: #b33441;
      padding: 1rem 2rem;
      border-radius: 3rem;
      margin-bottom: 2rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .gauge {
      font-size: 4rem;
      font-weight: 700;
      text-align: center;
      color: #d45a8c;
      background: rgba(255,255,255,0.5);
      padding: 1rem 2rem;
      border-radius: 5rem;
      display: inline-block;
      margin: 0 auto 1rem;
    }

    .text-center {
      text-align: center;
    }

    .input-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin: 1rem 0;
    }

    .input-group {
      position: relative;
      margin-bottom: 0.8rem;
    }

    .input-group i {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: #ad7a9f;
      font-size: 1rem;
      z-index: 2;
    }

    input, select {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 1.5px solid rgba(220, 180, 210, 0.4);
      border-radius: 3rem;
      font-size: 0.95rem;
      background: rgba(255, 250, 250, 0.8);
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
    }

    input:focus {
      outline: none;
      border-color: #e27c9f;
      background: white;
      box-shadow: 0 0 0 4px rgba(230, 120, 160, 0.15);
    }

    button {
      padding: 1rem 2.5rem;
      border: none;
      border-radius: 3rem;
      font-weight: 600;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.8rem;
      background: linear-gradient(145deg, #ff8aad, #c472e0);
      color: white;
      box-shadow: 0 10px 20px -8px rgba(220, 100, 150, 0.5);
      margin: 1rem 0;
    }

    button:hover {
      transform: scale(1.02);
      box-shadow: 0 16px 24px -8px #cb7bb0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 1.5rem 0;
      border-radius: 2rem;
      overflow: hidden;
      background: rgba(255, 255, 255, 0.5);
    }

    th {
      background: #eac0db;
      color: #3b2445;
      font-weight: 600;
      padding: 1rem;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid #f1cedf;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .badge {
      background: rgba(230, 120, 160, 0.2);
      border-radius: 3rem;
      padding: 0.3rem 1.2rem;
      font-weight: 500;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #deb0d0, transparent);
      margin: 2rem 0;
    }

    .info-note {
      color: #8a6a91;
      font-size: 0.9rem;
      text-align: center;
      margin-top: 1rem;
    }

    .partner-section {
      background: rgba(255, 240, 250, 0.5);
      border-radius: 2rem;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
<div class="bg-bubble"></div>
<div class="bg-bubble2"></div>

<div class="wrapper">
  <!-- navigation bar -->
  <nav class="navbar">
    <div class="nav-logo">
      <i class="fas fa-heart-circle-check"></i>
      <span>LoveSync</span>
    </div>
    <div class="nav-links">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="profile.php"><i class="far fa-user"></i> Profile</a>
      <a href="wish.php"><i class="fas fa-envelope"></i> Wish Maker</a>
      <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <h1><i class="fas fa-sparkles"></i> Astrology Compatibility</h1>

  <!-- API ERROR DISPLAY -->
  <?php if($apiError){ ?>
    <div class="glass-card error-box">
      <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($apiError); ?>
    </div>
  <?php } ?>

  <!-- STORED REPORT -->
  <?php if($astro){ ?>
    <div class="glass-card">
      <h2><i class="fas fa-database"></i> Stored Compatibility</h2>
      <p><span class="badge">Guna Milan Score: <?php echo $astro['guna_milan_score']; ?></span></p>
      <p><strong>Verdict:</strong> <?php echo $astro['compatibility_verdict']; ?></p>
      <p><?php echo $astro['summary']; ?></p>
    </div>
  <?php } ?>

  <!-- FOREVER ANALYTICS -->
  <div class="glass-card text-center">
    <h2><i class="fas fa-infinity"></i> Forever Possibility</h2>
    <div class="gauge"><?php echo $foreverScore; ?>%</div>
    <p style="color:#6f4f7a;">Based on Astrology + Love Score</p>
  </div>

  <!-- MANUAL CHECK FORM -->
  <div class="glass-card">
    <h2><i class="fas fa-calculator"></i> Manual Compatibility Check</h2>
    <form method="POST">
      <div class="partner-section">
        <h3><i class="fas fa-user"></i> Partner 1</h3>
        <div class="input-grid">
          <div class="input-group"><i class="fas fa-calendar"></i><input type="date" name="boy_dob" value="<?php echo $p1['dob']; ?>" required></div>
          <div class="input-group"><i class="fas fa-clock"></i><input type="time" name="boy_tob" value="<?php echo $p1['tob']; ?>" required></div>
          <div class="input-group"><i class="fas fa-map-pin"></i><input type="text" name="boy_lat" placeholder="Latitude" value="<?php echo $p1['latitude']; ?>" required></div>
          <div class="input-group"><i class="fas fa-map-pin"></i><input type="text" name="boy_lon" placeholder="Longitude" value="<?php echo $p1['longitude']; ?>" required></div>
        </div>
      </div>

      <div class="partner-section">
        <h3><i class="fas fa-user"></i> Partner 2</h3>
        <div class="input-grid">
          <div class="input-group"><i class="fas fa-calendar"></i><input type="date" name="girl_dob" value="<?php echo $p2['dob']; ?>" required></div>
          <div class="input-group"><i class="fas fa-clock"></i><input type="time" name="girl_tob" value="<?php echo $p2['tob']; ?>" required></div>
          <div class="input-group"><i class="fas fa-map-pin"></i><input type="text" name="girl_lat" placeholder="Latitude" value="<?php echo $p2['latitude']; ?>" required></div>
          <div class="input-group"><i class="fas fa-map-pin"></i><input type="text" name="girl_lon" placeholder="Longitude" value="<?php echo $p2['longitude']; ?>" required></div>
        </div>
      </div>

      <button type="submit" name="manualCheck"><i class="fas fa-search"></i> Check Compatibility</button>
    </form>
  </div>

  <!-- FULL API RESULT DISPLAY (only if available) -->
  <?php if($manualResult && !$apiError && isset($manualResult['guna_milan_score'])){ ?>
    <div class="glass-card">
      <h2><i class="fas fa-chart-pie"></i> Full Compatibility Report</h2>
      <p><span class="badge">Score: <?php echo $manualResult['guna_milan_score']; ?></span></p>
      <p><strong>Verdict:</strong> <?php echo $manualResult['compatibility_verdict']; ?></p>
      <p><?php echo $manualResult['summary']; ?></p>
    </div>

    <!-- ASHTAKOOT BREAKDOWN -->
    <?php if(isset($manualResult['ashtakoot_breakdown'])){ ?>
    <div class="glass-card">
      <h3><i class="fas fa-list"></i> Ashtakoot Breakdown</h3>
      <table>
        <tr><th>Koot</th><th>Score</th><th>Description</th></tr>
        <?php
        foreach($manualResult['ashtakoot_breakdown'] as $kootName => $kootData){
            $score = 'N/A';
            $description = $kootData['description'] ?? '';
            $scoreKeys = [
                'Bhakoot' => 'bhakoot', 'Gana' => 'gana', 'Graha Maitri' => 'grahamaitri',
                'Nadi' => 'nadi', 'Tara' => 'tara', 'Varna' => 'varna',
                'Vasya' => 'vasya', 'Yoni' => 'yoni'
            ];
            $scoreKey = $scoreKeys[$kootName] ?? strtolower($kootName);
            if(isset($kootData[$scoreKey])){
                $score = $kootData[$scoreKey] . '/' . $kootData['full_score'];
            }
            echo "<tr>
                <td><b>" . htmlspecialchars($kootName) . "</b></td>
                <td>$score</td>
                <td style='font-size:0.9rem;'>" . htmlspecialchars($description) . "</td>
            </tr>";
        }
        ?>
      </table>
    </div>
    <?php } ?>

    <!-- PLANETARY POSITIONS -->
    <?php if(isset($manualResult['boy_planets']) && isset($manualResult['girl_planets'])){ ?>
    <div class="glass-card">
      <h3><i class="fas fa-planet-ringed"></i> Planetary Positions</h3>
      <table>
        <tr><th>Planet</th><th>Partner 1</th><th>Partner 2</th></tr>
        <?php
        $boyPlanets = $manualResult['boy_planets'];
        $girlPlanets = $manualResult['girl_planets'];
        foreach($boyPlanets as $index => $boyPlanet){
            $planetName = $boyPlanet['full_name'];
            $boyDeg = round($boyPlanet['global_degree'], 2);
            $boyZodiac = $boyPlanet['zodiac'];
            $boyNakshatra = $boyPlanet['nakshatra'];
            $girlPlanet = $girlPlanets[$index];
            $girlDeg = round($girlPlanet['global_degree'], 2);
            $girlZodiac = $girlPlanet['zodiac'];
            $girlNakshatra = $girlPlanet['nakshatra'];
            echo "<tr>
                <td><b>" . htmlspecialchars($planetName) . "</b></td>
                <td>{$boyDeg}° {$boyZodiac}<br><small>{$boyNakshatra}</small></td>
                <td>{$girlDeg}° {$girlZodiac}<br><small>{$girlNakshatra}</small></td>
            </tr>";
        }
        ?>
      </table>
    </div>
    <?php } ?>

    <!-- ASTRO PROFILES -->
    <?php if(isset($manualResult['boy_astro_profile']) && isset($manualResult['girl_astro_profile'])){ ?>
    <div class="glass-card">
      <h3><i class="fas fa-id-card"></i> Astrological Profiles</h3>
      <table>
        <tr><th>Attribute</th><th>Partner 1</th><th>Partner 2</th></tr>
        <tr><td><b>Ascendant</b></td><td><?php echo $manualResult['boy_astro_profile']['ascendant_sign']; ?></td><td><?php echo $manualResult['girl_astro_profile']['ascendant_sign']; ?></td></tr>
        <tr><td><b>Rasi (Moon Sign)</b></td><td><?php echo $manualResult['boy_astro_profile']['rasi']; ?></td><td><?php echo $manualResult['girl_astro_profile']['rasi']; ?></td></tr>
        <tr><td><b>Nakshatra</b></td><td><?php echo $manualResult['boy_astro_profile']['nakshatra']; ?> (Pada <?php echo $manualResult['boy_astro_profile']['nakshatra_pada']; ?>)</td><td><?php echo $manualResult['girl_astro_profile']['nakshatra']; ?> (Pada <?php echo $manualResult['girl_astro_profile']['nakshatra_pada']; ?>)</td></tr>
        <tr><td><b>Gana</b></td><td><?php echo ucfirst($manualResult['boy_astro_profile']['gana']); ?></td><td><?php echo ucfirst($manualResult['girl_astro_profile']['gana']); ?></td></tr>
        <tr><td><b>Nadi</b></td><td><?php echo $manualResult['boy_astro_profile']['nadi']; ?></td><td><?php echo $manualResult['girl_astro_profile']['nadi']; ?></td></tr>
        <tr><td><b>Varna</b></td><td><?php echo $manualResult['boy_astro_profile']['varna']; ?></td><td><?php echo $manualResult['girl_astro_profile']['varna']; ?></td></tr>
        <tr><td><b>Current Dasa</b></td><td><?php echo $manualResult['boy_astro_profile']['current_dasa']; ?></td><td><?php echo $manualResult['girl_astro_profile']['current_dasa']; ?></td></tr>
      </table>
    </div>
    <?php } ?>

    <!-- REMAINING CALLS (optional) -->
    <?php if(isset($manualResult['remaining_api_calls'])){ ?>
    <div class="glass-card text-center">
      <p><i class="fas fa-chart-line"></i> Remaining API calls: <?php echo $manualResult['remaining_api_calls']; ?></p>
    </div>
    <?php } ?>
  <?php } ?>

  <div class="info-note">
    <i class="fas fa-shield-heart"></i> Astrology data powered by ancient wisdom and modern algorithms.
  </div>
</div>
</body>
</html>