<?php
include "assets/connection.php";

if(!isset($_SESSION['user_id'])){
 header("Location: login.php");
 exit();
}

$user_id = $_SESSION['user_id'];

/* =====================================================
   FETCH USER
===================================================== */

$user =
mysqli_fetch_assoc(
 mysqli_query($conn,
 "SELECT * FROM users
  WHERE id='$user_id'")
);

$status = $user['relationship_status'];


/* =====================================================
   FETCH RELATIONSHIP
===================================================== */

$relationship =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM relationships
 WHERE partner1_id='$user_id'
 OR partner2_id='$user_id'")
);


/* =====================================================
   SAVE STATUS
===================================================== */

if(isset($_POST['saveStatus'])){

$status = $_POST['status'];

mysqli_query($conn,"
UPDATE users
SET relationship_status='$status'
WHERE id='$user_id'
");

header("Location: dashboard.php");
exit();
}


/* =====================================================
   GENERATE PAIR CODE
===================================================== */

if(isset($_POST['generateCode'])){

$code = "LS" . rand(100000,999999);

mysqli_query($conn,"
INSERT INTO relationships
(partner1_id,pair_code)
VALUES
('$user_id','$code')
");

header("Location: dashboard.php?code=$code");
exit();
}


/* =====================================================
   JOIN CODE + ANNIVERSARY + ASTRO MATCH
===================================================== */

if(isset($_POST['joinCode'])){

$entered = $_POST['pair_code'];
$anniversary = $_POST['anniversary'];

$rel =
mysqli_fetch_assoc(
 mysqli_query($conn,
 "SELECT * FROM relationships
  WHERE pair_code='$entered'
  AND partner2_id IS NULL")
);

if($rel){

/* UPDATE RELATIONSHIP */

mysqli_query($conn,"
UPDATE relationships
SET partner2_id='$user_id',
    anniversary='$anniversary',
    relationship_start='$anniversary'
WHERE id='{$rel['id']}'
");

/* ===============================
   ASTRO MATCH API CALL (POSTMAN STYLE)
=============================== */

$partner1 =
mysqli_fetch_assoc(
 mysqli_query($conn,
 "SELECT * FROM users
  WHERE id='{$rel['partner1_id']}'")
);

$partner2 = $user;

/* PREPARE JSON EXACTLY LIKE POSTMAN */

$payload = json_encode([

  "boy_dob" => date("d/m/Y",
      strtotime($partner1['dob'])),

  "boy_tob" => date("H:i",
      strtotime($partner1['tob'])),

  "boy_tz"  => "5.5",

  "boy_lat" => strval($partner1['latitude']),
  "boy_lon" => strval($partner1['longitude']),


  "girl_dob" => date("d/m/Y",
      strtotime($partner2['dob'])),

  "girl_tob" => date("H:i",
      strtotime($partner2['tob'])),

  "girl_tz"  => "5.5",

  "girl_lat" => strval($partner2['latitude']),
  "girl_lon" => strval($partner2['longitude'])

]);

/* POSTMAN-STYLE CURL */

$curl = curl_init();

curl_setopt_array($curl, array(

  CURLOPT_URL => 'http://127.0.0.1:5004/astro-match',

  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',

  CURLOPT_POSTFIELDS => $payload,

  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
  ),

));

$response = curl_exec($curl);

if(curl_errno($curl)){
   echo "Curl Error: " . curl_error($curl);
}

curl_close($curl);

/* DECODE RESPONSE */

$result = json_decode($response, true);

/* DEBUG IF ERROR */

if(!$result){
   die("Astro API Error ❌ → ".$response);
}

/* ===============================
   STORE ASTRO REPORT
=============================== */

mysqli_query($conn,"
INSERT INTO astro_couple_reports
(
 relationship_id,
 guna_milan_score,
 compatibility_verdict,
 summary
)
VALUES
(
 '{$rel['id']}',
 '{$result['guna_milan_score']}',
 '{$result['compatibility_verdict']}',
 '".mysqli_real_escape_string($conn,$result['summary'])."'
)
");

/* ==========================================
   AUTO CREATE COUPLE VAULT
========================================== */

if(!$rel['vault_id']){

/* ===== VAULT DETAILS ===== */

$vaultName =
"LS" . $rel['id'];

$pin =
rand(1000,9999);

/* ===== EMAILS ===== */

$partner1Email =
$partner1['email'];

$partner2Email =
$partner2['email'];

/* ===== CREATE VAULT API ===== */

$vaultPayload = [

 "vault_name" => $vaultName,
 "pin" => $pin,
 "email" => $partner1Email,
 "is_couple" => 1
];

$ch = curl_init();

curl_setopt_array($ch, [

 CURLOPT_URL =>
 "http://127.0.0.1:5002/api/vault/create",

 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_CUSTOMREQUEST => "POST",

 CURLOPT_POSTFIELDS =>
 json_encode($vaultPayload),

 CURLOPT_HTTPHEADER =>
 ["Content-Type: application/json"]
]);

$vaultResponse =
curl_exec($ch);

curl_close($ch);

$vaultData =
json_decode($vaultResponse,true);

$vault_id =
$vaultData['vault_id'] ?? 0;


/* ===== STORE VAULT ID ===== */

if($vault_id){

mysqli_query($conn,"
UPDATE relationships
SET vault_id='$vault_id'
WHERE id='{$rel['id']}'
");

}


/* ==========================================
   SEND VAULT DETAILS EMAIL
========================================== */

$message = "
<h2>LoveSync Memory Vault ❤️</h2>

<p>Your couple vault has been created.</p>

<b>Vault Name:</b> $vaultName <br>
<b>PIN:</b> $pin <br><br>

Login to unlock memories 💑
";


$emailPayload = [

 "to" => $partner1Email,
 "subject" =>
 "LoveSync Vault Created ❤️",
 "message" => $message
];

$ch = curl_init();

curl_setopt_array($ch,[

 CURLOPT_URL =>
 "http://127.0.0.1:5003/send-email",

 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_CUSTOMREQUEST => "POST",

 CURLOPT_POSTFIELDS =>
 json_encode($emailPayload),

 CURLOPT_HTTPHEADER =>
 ["Content-Type: application/json"]
]);

curl_exec($ch);
curl_close($ch);


/* SEND TO PARTNER 2 */

$emailPayload['to'] =
$partner2Email;

$ch = curl_init();

curl_setopt_array($ch,[

 CURLOPT_URL =>
 "http://127.0.0.1:5003/send-email",

 CURLOPT_RETURNTRANSFER => true,
 CURLOPT_CUSTOMREQUEST => "POST",

 CURLOPT_POSTFIELDS =>
 json_encode($emailPayload),

 CURLOPT_HTTPHEADER =>
 ["Content-Type: application/json"]
]);

curl_exec($ch);
curl_close($ch);

}

header("Location: dashboard.php?paired=1");
exit();
}
}


/* =====================================================
   WHATSAPP CHAT FETCH
===================================================== */

$chat = null;

if($relationship){

$chat =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM chat_reports
 WHERE relationship_id='{$relationship['id']}'")
);
}


/* =====================================================
   LIVE GPS TRACKING
===================================================== */

if(isset($_POST['updateLocation'])){

$lat = $_POST['lat'];
$lon = $_POST['lon'];

/* SAVE USER CURRENT LOCATION */

mysqli_query($conn,"
UPDATE users
SET latitude='$lat',
    longitude='$lon'
WHERE id='$user_id'
");

/* IF BOTH LOCATIONS AVAILABLE → CALCULATE DISTANCE */

$partner_id =
($relationship['partner1_id']==$user_id)
? $relationship['partner2_id']
: $relationship['partner1_id'];

$partner =
mysqli_fetch_assoc(
mysqli_query($conn,
"SELECT latitude,longitude
 FROM users
 WHERE id='$partner_id'")
);

if($partner){

$distance = haversineDistance(
$lat,$lon,
$partner['latitude'],
$partner['longitude']
);

/* STORE SHORTEST DISTANCE */

mysqli_query($conn,"
INSERT INTO distance_logs
(relationship_id,
 partner1_lat,
 partner1_lon,
 partner2_lat,
 partner2_lon,
 distance_km)
VALUES
('{$relationship['id']}',
 '$lat','$lon',
 '{$partner['latitude']}',
 '{$partner['longitude']}',
 '$distance')
");
}

if($distance <= 0.1){

/* CHECK LAST MEET */

$lastMeet =
mysqli_fetch_assoc(
mysqli_query($conn,"
SELECT *
FROM meet_logs
WHERE relationship_id='{$relationship['id']}'
ORDER BY meet_date DESC
LIMIT 1")
);

/* INSERT ONLY IF NOT TODAY */

if(!$lastMeet ||
   $lastMeet['meet_date'] != date("Y-m-d")){

mysqli_query($conn,"
INSERT INTO meet_logs
(relationship_id,
 meet_date,
 location,
 latitude,
 longitude)
VALUES
('{$relationship['id']}',
 CURDATE(),
 'Auto detected meet',
 '$lat',
 '$lon')
");

}

}

exit();
}


/* =====================================================
   HAVERSINE DISTANCE FUNCTION
===================================================== */

function haversineDistance(
$lat1,$lon1,$lat2,$lon2){

$earth = 6371;

$dLat = deg2rad($lat2-$lat1);
$dLon = deg2rad($lon2-$lon1);

$a =
sin($dLat/2)*sin($dLat/2) +
cos(deg2rad($lat1)) *
cos(deg2rad($lat2)) *
sin($dLon/2)*sin($dLon/2);

$c = 2*atan2(sqrt($a),sqrt(1-$a));

return round($earth*$c,2);
}


/* =====================================================
   FETCH SHORTEST DISTANCE
===================================================== */

$distanceData = null;

if($relationship){

$distanceData =
mysqli_fetch_assoc(
mysqli_query($conn,"
SELECT *
FROM distance_logs
WHERE relationship_id='{$relationship['id']}'
ORDER BY distance_km ASC
LIMIT 1
"));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · Dashboard</title>
  <!-- Google Font & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
      color: #2a1d33;
    }

    /* background bubbles */
    .bg-bubble {
      position: fixed;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle at 30% 30%, rgba(255, 175, 200, 0.3), rgba(205, 150, 255, 0.15));
      border-radius: 50%;
      top: -50px;
      left: -50px;
      z-index: 0;
      filter: blur(60px);
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
    .dashboard-wrapper {
      position: relative;
      z-index: 10;
      max-width: 1300px;
      margin: 0 auto;
      padding: 1.5rem 2rem;
    }

    /* navigation */
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
      margin-bottom: 2.5rem;
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

    .user-greeting {
      font-weight: 600;
      color: #8e5c82;
      background: rgba(255,240,245,0.7);
      padding: 0.3rem 1.2rem;
      border-radius: 2rem;
    }

    /* glass card */
    .glass-card {
      background: rgba(255, 255, 255, 0.65);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: 2.5rem;
      box-shadow: 0 20px 40px -16px rgba(90, 40, 70, 0.2);
      padding: 2rem;
      margin-bottom: 2rem;
    }

    h2, h3 {
      font-weight: 600;
      color: #32243d;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    h2 i, h3 i {
      color: #e27c9f;
    }

    .flex-row {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      align-items: center;
    }

    .btn {
      display: inline-block;
      padding: 0.8rem 2rem;
      border-radius: 3rem;
      font-weight: 600;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: 0.2s;
      font-size: 1rem;
      background: linear-gradient(145deg, #ff8aad, #c472e0);
      color: white;
      box-shadow: 0 8px 18px -6px rgba(200, 100, 160, 0.4);
    }

    .btn-outline {
      background: rgba(255,255,255,0.7);
      color: #5a3f63;
      border: 1px solid #e2b2cf;
      box-shadow: none;
    }

    .btn-outline:hover {
      background: white;
    }

    .btn:hover {
      transform: scale(1.02);
      box-shadow: 0 12px 24px -8px #cb7bb0;
    }

    button {
      font-family: 'Inter', sans-serif;
    }

    input, select {
      padding: 0.9rem 1.5rem;
      border-radius: 3rem;
      border: 1.5px solid rgba(200, 160, 190, 0.3);
      background: rgba(255, 250, 250, 0.7);
      width: 100%;
      font-size: 1rem;
      transition: 0.2s;
    }

    input:focus {
      outline: none;
      border-color: #e27c9f;
      background: white;
      box-shadow: 0 0 0 4px rgba(230,120,160,0.1);
    }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .info-chip {
      background: rgba(255, 255, 255, 0.7);
      border-radius: 3rem;
      padding: 0.7rem 1.5rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0.5rem 0.5rem 0 0;
    }

    .map-container {
      border-radius: 2rem;
      overflow: hidden;
      border: 2px solid rgba(255, 255, 255, 0.7);
      margin-top: 1rem;
    }

    #map {
      height: 320px;
      width: 100%;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #deb0d0, transparent);
      margin: 2rem 0;
    }

    /* popup (status) */
    .popup-overlay {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.3);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 999;
    }

    .popup-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 3rem;
      padding: 2.5rem;
      max-width: 400px;
      width: 90%;
      border: 1px solid white;
      box-shadow: 0 30px 50px -20px #834e73;
    }

    .radio-group {
      display: flex;
      gap: 2rem;
      justify-content: center;
      margin: 1.5rem 0;
    }

    .radio-group label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: #f5e1ed;
      padding: 0.6rem 1.5rem;
      border-radius: 3rem;
    }

    /* additional link sections */
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 1.5rem;
    }
  </style>
</head>
<body>
<div class="bg-bubble"></div>
<div class="bg-bubble2"></div>

<div class="dashboard-wrapper">

  <!-- NAVBAR (dynamic links based on status) -->
  <nav class="navbar">
    <div class="nav-logo">
      <i class="fas fa-heart-circle-check"></i>
      <span>LoveSync</span>
    </div>
    <div class="nav-links">
      <?php if($status == "Single"): ?>
        <a href="profile.php"><i class="far fa-user"></i> Profile</a>
        <a href="chat.php"><i class="fas fa-comments"></i> Chat</a>
        <a href="blind.php"><i class="fas fa-star"></i> Blind</a>
        <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <?php elseif($relationship && $relationship['partner2_id']): ?>
        <!-- committed & paired -->
        <a href="profile.php"><i class="far fa-user"></i> Profile</a>
        <a href="astro.php"><i class="fas fa-sparkles"></i> Astro</a>
        <a href="vault.php"><i class="fas fa-cloud"></i> Vault</a>
        <a href="wish.php"><i class="fas fa-envelope"></i> Wish</a>
        <a href="whatsapp.php"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <?php elseif($status=="Committed" && !$relationship): ?>
        <a href="profile.php"><i class="far fa-user"></i> Profile</a>
        <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <?php else: ?>
        <!-- fallback -->
        <a href="profile.php"><i class="far fa-user"></i> Profile</a>
        <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <?php endif; ?>
      <span class="user-greeting"><i class="fas fa-heart" style="color:#e6769e;"></i> <?php echo htmlspecialchars($user['full_name']); ?></span>
    </div>
  </nav>

  <!-- STATUS POPUP (if no status set) -->
  <?php if($status == NULL): ?>
    <div class="popup-overlay">
      <div class="popup-card">
        <h3 style="text-align:center;"><i class="fas fa-question-circle"></i> Are you committed?</h3>
        <form method="POST">
          <div class="radio-group">
            <label><input type="radio" name="status" value="Committed" required> Yes, I'm committed</label>
            <label><input type="radio" name="status" value="Single"> No, I'm single</label>
          </div>
          <button type="submit" name="saveStatus" class="btn" style="width:100%;">Continue <i class="fas fa-arrow-right"></i></button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- SINGLE DASHBOARD -->
  <?php if($status == "Single"): ?>
    <div class="glass-card">
      <h2><i class="fas fa-user-astronaut"></i> Singles Dashboard</h2>
      <p style="font-size:1.2rem; margin-bottom:1.5rem;">Find meaningful connections through astrology & interests.</p>
      <div class="action-buttons">
        <a href="blind.php" class="btn"><i class="fas fa-crystal-ball"></i> Blind Dating</a>
        <a href="chat.php" class="btn btn-outline"><i class="fas fa-comments"></i> Chat Rooms</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- COMMITTED BUT NOT PAIRED -->
  <?php if($status=="Committed" && !$relationship): ?>
    <div class="glass-card">
      <h2><i class="fas fa-link"></i> Pair with your partner</h2>
      <div class="grid-2">
        <div style="background:rgba(255,255,255,0.5); border-radius:2rem; padding:2rem;">
          <h3>🔑 Generate code</h3>
          <p>Create a pairing code to share.</p>
          <?php if(isset($_GET['code'])): ?>
            <div class="info-chip"><i class="fas fa-key"></i> Code: <strong><?php echo $_GET['code']; ?></strong></div>
          <?php endif; ?>
          <form method="POST">
            <button type="submit" name="generateCode" class="btn" style="margin-top:1rem;">Generate new code</button>
          </form>
        </div>
        <div style="background:rgba(255,255,255,0.5); border-radius:2rem; padding:2rem;">
          <h3>🔐 Join with code</h3>
          <form method="POST">
            <input type="text" name="pair_code" placeholder="Enter partner's code" required style="margin-bottom:0.8rem;">
            <input type="date" name="anniversary" placeholder="Anniversary" required style="margin-bottom:0.8rem;">
            <button type="submit" name="joinCode" class="btn">Join relationship</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- COUPLE DASHBOARD (paired) -->
  <?php if($relationship && $relationship['partner2_id']): ?>
    <div class="glass-card">
      <h2><i class="fas fa-heart"></i> Couple Dashboard <span style="font-size:1rem; background:#f0d2e6; padding:0.2rem 1rem; border-radius:2rem; margin-left:1rem;">❤️ paired</span></h2>
    </div>

    <!-- Distance tracker card -->
    <div class="glass-card">
      <h3><i class="fas fa-map-marked-alt"></i> Distance Tracker</h3>
      <p>You are <strong><?php echo $distanceData['distance_km'] ?? "—"; ?> km</strong> apart</p>
      <div class="map-container">
        <div id="map"></div>
      </div>
    </div>

    <!-- Timeline & WhatsApp & Vault & Email -->
    <div class="grid-2">
      <!-- timeline card (updated to show next anniversary countdown) -->
      <div class="glass-card">
        <h3><i class="fas fa-calendar-alt"></i> Timeline</h3>
        <p><i class="fas fa-clock"></i> Since: <?php echo $relationship['relationship_start'] ?? "—"; ?></p>
        <p><i class="fas fa-hourglass-half"></i> Together: <span id="countdown">...</span></p>
        <?php 
        if($relationship['anniversary']): 
            $anniv_date = $relationship['anniversary'];
            $today = date('Y-m-d');
            $anniv_this_year = date('Y') . substr($anniv_date, 4);
            if ($anniv_this_year < $today) {
                $next_anniv = date('Y', strtotime('+1 year')) . substr($anniv_date, 4);
            } else {
                $next_anniv = $anniv_this_year;
            }
            $days_until = floor((strtotime($next_anniv) - strtotime($today)) / (60*60*24));
            $next_anniv_formatted = date('Y-m-d', strtotime($next_anniv));
        ?>
          <div class="info-chip"><i class="fas fa-gift"></i> Next anniv: <?php echo $next_anniv_formatted; ?></div>
          <div class="info-chip"><i class="fas fa-hourglass-start"></i> Countdown: <strong><?php echo $days_until; ?> days</strong></div>
        <?php endif; ?>
      </div>

      <!-- WhatsApp card -->
      <div class="glass-card">
        <h3><i class="fab fa-whatsapp"></i> WhatsApp Insights</h3>
        <?php if($chat): ?>
          <p>💬 Total msgs: <?php echo $chat['total_messages']; ?></p>
          <p>❤️ Love score: <?php echo $chat['love_score_percentage']; ?>%</p>
          <p>📅 First chat: <?php echo $chat['first_chat_date']; ?></p>
        <?php else: ?>
          <p>No chat analyzed yet.</p>
          <a href="upload_chat.php" class="btn btn-outline"><i class="fas fa-upload"></i> Upload chat</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Action cards for vault, email, astro -->
    <div class="grid-2">
      <div class="glass-card">
        <h3><i class="fas fa-cloud-upload-alt"></i> Memory Vault</h3>
        <p>Preserve your favourite moments.</p>
        <a href="vault.php" class="btn btn-outline"><i class="fas fa-images"></i> Open Vault</a>
      </div>
      <div class="glass-card">
        <h3><i class="fas fa-envelope-open-text"></i> Email Scheduler</h3>
        <p>Schedule love letters & surprises.</p>
        <a href="wish.php" class="btn btn-outline"><i class="fas fa-clock"></i> Schedule</a>
      </div>
    </div>

    <!-- Astro & extra -->
    <div class="glass-card">
      <div style="display:flex; flex-wrap:wrap; gap:2rem; align-items:center;">
        <div><i class="fas fa-star" style="font-size:2rem; color:#c672b0;"></i></div>
        <div><h3 style="margin:0;">Astrological compatibility</h3></div>
        <a href="astro.php" class="btn">View full report <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  <?php endif; ?>

  <!-- If no status or fallback, show nothing extra -->
</div> <!-- dashboard-wrapper -->

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  // GPS tracking (exact same)
  navigator.geolocation.getCurrentPosition(function(pos){
    fetch("dashboard.php", {
      method:"POST",
      headers:{ "Content-Type": "application/x-www-form-urlencoded" },
      body: "updateLocation=1&lat="+pos.coords.latitude+"&lon="+pos.coords.longitude
    });
  });

  // Map initialization if distance data exists
  <?php if($distanceData && isset($distanceData['partner1_lat'])): ?>
    var map = L.map('map').setView([
      <?php echo $distanceData['partner1_lat']; ?>,
      <?php echo $distanceData['partner1_lon']; ?>
    ], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    var p1 = [<?php echo $distanceData['partner1_lat']; ?>, <?php echo $distanceData['partner1_lon']; ?>];
    var p2 = [<?php echo $distanceData['partner2_lat']; ?>, <?php echo $distanceData['partner2_lon']; ?>];
    L.marker(p1).addTo(map).bindPopup("You");
    L.marker(p2).addTo(map).bindPopup("Partner");
    var midLat = (p1[0]+p2[0])/2;
    var midLon = (p1[1]+p2[1])/2;
    L.marker([midLat, midLon]).addTo(map).bindPopup("Best Meet Point ❤️");
  <?php endif; ?>

  // Anniversary countdown (days together)
  <?php if($relationship && $relationship['anniversary']): ?>
    var anniv = new Date("<?php echo $relationship['anniversary']; ?>");
    function updateCountdown() {
      var now = new Date();
      var diff = now - anniv;
      var days = Math.ceil(diff / (1000*60*60*24));
      document.getElementById("countdown").innerText = days + " days";
    }
    updateCountdown();
  <?php endif; ?>
</script>

</body>
</html>