<?php
include "assets/connection.php";

$ASTRO_API =
"http://localhost:5004/astrodetails";

if(isset($_POST['signup'])){

$email = $_POST['email'];
$password = $_POST['password'];
$full_name = $_POST['full_name'];
$gender = $_POST['gender'];
$dob = $_POST['dob'];
$pob = $_POST['pob'];
$tob = $_POST['tob'];

$lat = $_POST['latitude'];
$lon = $_POST['longitude'];



/* =============================
   INSERT USER
============================= */

$query = "
INSERT INTO users
(email,password,full_name,gender,dob,pob,tob,latitude,longitude)
VALUES
('$email','$password','$full_name','$gender','$dob','$pob','$tob','$lat','$lon')
";

mysqli_query($conn,$query);

$user_id =
mysqli_insert_id($conn);



/* =============================
   ASTRO API CALL
============================= */

$payload = json_encode([

 "dob" => date(
   "d/m/Y",
   strtotime($dob)
 ),

 "tob" => $tob,
 "lat" => $lat,
 "lon" => $lon,
 "tz" => "5.5",
 "reference_id" =>
   "user_" . $user_id
]);

$ch = curl_init($ASTRO_API);

curl_setopt($ch,
 CURLOPT_RETURNTRANSFER,true);

curl_setopt($ch,
 CURLOPT_POST,true);

curl_setopt($ch,
 CURLOPT_POSTFIELDS,$payload);

curl_setopt($ch,
 CURLOPT_HTTPHEADER,[
  "Content-Type: application/json"
]);

$response =
curl_exec($ch);

$data =
json_decode($response,true);

curl_close($ch);



/* =============================
   STORE ASTRO DETAILS
============================= */

if(isset($data[0])){

mysqli_query($conn,"
INSERT INTO astro_details
(user_id,
 moon_nakshatra_no,
 moon_rasi_no,
 asc_degree,
 moon_degree,
 sun_degree,
 reference_id)
VALUES
('$user_id',
 '{$data[0][0]}',
 '{$data[0][1]}',
 '{$data[0][2]}',
 '{$data[0][3]}',
 '{$data[0][4]}',
 '{$data[0][5]}')
");

}

$success =
"Signup + Astro Profile Created ❤️";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync AI · Create account</title>
  <!-- Google Font & Font Awesome (same as login/index) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Leaflet (keep as before) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
    }

    /* background bubbles (same as login) */
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

    .signup-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 560px; /* slightly wider for form */
      margin: 2rem auto;
    }

    /* glass card */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 2.5rem;
      box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
      padding: 2.5rem 2.2rem;
    }

    .logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      font-weight: 700;
      font-size: 2rem;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #e3648c, #a07bda);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }

    .logo i {
      font-size: 2.2rem;
      background: none;
      -webkit-text-fill-color: #e17b9f;
      color: #e17b9f;
    }

    h2 {
      text-align: center;
      font-weight: 600;
      font-size: 1.8rem;
      color: #32243d;
      margin-bottom: 0.5rem;
    }

    .sub-head {
      text-align: center;
      color: #6f4f7a;
      margin-bottom: 2rem;
      font-size: 0.95rem;
      border-bottom: 1px dashed #dfc3d7;
      padding-bottom: 1rem;
    }

    .sub-head a {
      color: #c5538c;
      text-decoration: none;
      font-weight: 500;
    }

    .input-group {
      margin-bottom: 1.2rem;
      position: relative;
    }

    .input-group i {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: #ad7a9f;
      font-size: 1.2rem;
      z-index: 2;
    }

    input, select {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 1.5px solid rgba(220, 180, 210, 0.4);
      border-radius: 3rem;
      font-size: 1rem;
      background: rgba(255, 250, 250, 0.8);
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
      appearance: none;
    }

    select {
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%23ad7a9f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>');
      background-repeat: no-repeat;
      background-position: right 1.2rem center;
      background-size: 1.2rem;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #e27c9f;
      background: white;
      box-shadow: 0 0 0 4px rgba(230, 120, 160, 0.15);
    }

    /* label styling for date/time etc */
    .field-label {
      display: block;
      margin: 0.8rem 0 0.3rem 1rem;
      font-weight: 500;
      color: #6d4d75;
      font-size: 0.9rem;
    }

    /* suggestions box (keep functionality, style nicely) */
    #suggestions {
      background: white;
      border-radius: 1.5rem;
      margin-top: 0.3rem;
      border: 1px solid #e9cddf;
      max-height: 160px;
      overflow-y: auto;
      box-shadow: 0 8px 20px rgba(140, 80, 120, 0.1);
    }

    #suggestions div {
      padding: 0.8rem 1.5rem;
      cursor: pointer;
      font-size: 0.9rem;
      border-bottom: 1px solid #f5e2ed;
      transition: background 0.1s;
    }

    #suggestions div:hover {
      background: #ffeef6;
    }

    /* map container */
    .map-wrapper {
      margin: 1.2rem 0 1rem;
      border-radius: 2rem;
      overflow: hidden;
      border: 2px solid rgba(255, 255, 255, 0.7);
      box-shadow: 0 8px 18px rgba(170, 120, 150, 0.15);
    }

    #map {
      height: 220px;
      z-index: 5;
    }

    /* latitude/longitude readonly fields (they are hidden in design, but we keep them as readonly) */
    .coords-row {
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
    }

    .coords-row .input-group {
      flex: 1;
    }

    /* hide the default placeholder icons for lat/lon? they are readonly, we keep them */
    button {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(145deg, #ff8aad, #c472e0);
      border: none;
      border-radius: 3rem;
      color: white;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      box-shadow: 0 10px 20px -8px rgba(220, 100, 150, 0.5);
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1.5rem;
    }

    button:hover {
      transform: scale(1.02);
      box-shadow: 0 16px 24px -8px #cb7bb0;
    }

    .success {
      padding: 1rem;
      border-radius: 3rem;
      margin-bottom: 1.8rem;
      font-weight: 500;
      text-align: center;
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(4px);
      color: #297a5e;
      border: 1px solid #a0e0c0;
    }

    .extra-links {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
      font-size: 0.9rem;
    }

    .extra-links a {
      text-decoration: none;
      color: #8e6793;
      font-weight: 500;
    }

    .extra-links a:hover {
      color: #d4588a;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #deb0d0, transparent);
      margin: 2rem 0 1rem;
    }

    .back-home {
      text-align: center;
    }

    .back-home a {
      color: #7b5680;
      font-weight: 500;
      text-decoration: none;
    }

    .back-home i {
      margin-right: 6px;
    }

    /* small note */
    .secure-note {
      text-align: center;
      margin-top: 1.5rem;
      color: #9b83a3;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <div class="bg-bubble"></div>
  <div class="bg-bubble2"></div>

  <div class="signup-container">
    <div class="glass-card">

      <div class="logo">
        <i class="fas fa-heart-circle-check"></i>
        <span>LoveSync AI</span>
      </div>
      <h2>join the connection</h2>
      <div class="sub-head">
        <i class="fas fa-heart" style="color: #e6769e;"></i> 
        <a href="index.html">home</a> · 
        <a href="login.php">login</a>
      </div>

      <!-- success message -->
      <?php if(isset($success)): ?>
        <div class="success"><i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <!-- signup form (keep all original fields, names, ids) -->
      <form method="POST">

        <!-- email -->
        <div class="input-group">
          <i class="far fa-envelope"></i>
          <input type="email" name="email" placeholder="Email address" required>
        </div>

        <!-- password -->
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" placeholder="Password" required>
        </div>

        <!-- full name -->
        <div class="input-group">
          <i class="far fa-user"></i>
          <input type="text" name="full_name" placeholder="Full name" required>
        </div>

        <!-- gender -->
        <div class="input-group">
          <i class="fas fa-venus-mars"></i>
          <select name="gender" required>
            <option value="" disabled selected>Select gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>

        <!-- date of birth -->
        <div class="field-label"><i class="far fa-calendar-alt" style="margin-right: 6px;"></i>Date of birth</div>
        <div class="input-group" style="margin-top: 0;">
          <i class="fas fa-cake-candles"></i>
          <input type="date" name="dob" required>
        </div>

        <!-- place of birth with search -->
        <div class="field-label"><i class="fas fa-map-pin"></i> Place of birth</div>
        <div class="input-group" style="margin-top: 0;">
          <i class="fas fa-search"></i>
          <input type="text" id="locationSearch" name="pob" placeholder="Search city (e.g. Bhubaneswar)" onkeyup="searchLocation()" autocomplete="off" required>
        </div>
        <div id="suggestions"></div>

        <!-- time of birth -->
        <div class="field-label"><i class="far fa-clock"></i> Time of birth</div>
        <div class="input-group" style="margin-top: 0;">
          <i class="fas fa-clock"></i>
          <input type="time" name="tob" required>
        </div>

        <!-- latitude and longitude (readonly, hidden in style but we keep them visible as fields? original had them as inputs, we'll keep but style them) -->
        <div class="coords-row">
          <div class="input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="text" name="latitude" id="lat" placeholder="Latitude" readonly required>
          </div>
          <div class="input-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="text" name="longitude" id="lon" placeholder="Longitude" readonly required>
          </div>
        </div>

        <!-- map -->
        <div class="field-label"><i class="fas fa-globe"></i> Select on map (click to pin)</div>
        <div class="map-wrapper">
          <div id="map"></div>
        </div>

        <!-- submit button -->
        <button name="signup">
          <i class="fas fa-heart"></i> Create account
        </button>
      </form>

      <hr>

      <div class="extra-links">
        <a href="login.php"><i class="fas fa-arrow-right-to-bracket"></i> Log in</a>
      </div>

      <div class="back-home">
        <a href="index.html"><i class="fas fa-chevron-left"></i> back to LoveSync</a>
      </div>
    </div> <!-- end glass-card -->

    <div class="secure-note">
      <i class="fas fa-shield-heart"></i> your astro profile is created securely
    </div>
  </div> <!-- end signup-container -->

  <script>
    /* ================= MAP (identical to original, just with updated variable names) ================= */
    var map = L.map('map').setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    var marker;

    /* ================= SEARCH FUNCTION (same) ================= */
    function searchLocation() {
      let query = document.getElementById("locationSearch").value;
      if (query.length < 3) return;

      fetch("https://nominatim.openstreetmap.org/search?format=json&q=" + query)
        .then(res => res.json())
        .then(data => {
          let box = document.getElementById("suggestions");
          box.innerHTML = "";
          data.forEach(place => {
            let div = document.createElement("div");
            div.innerHTML = place.display_name;
            div.style.padding = "8px";
            div.style.cursor = "pointer";
            div.onclick = function () {
              selectLocation(place.lat, place.lon, place.display_name);
              box.innerHTML = "";
            };
            box.appendChild(div);
          });
        });
    }

    /* ============== SELECT LOCATION (same) ============== */
    function selectLocation(lat, lon, name) {
      document.getElementById("locationSearch").value = name;
      document.getElementById("lat").value = lat;
      document.getElementById("lon").value = lon;
      if (marker) {
        map.removeLayer(marker);
      }
      marker = L.marker([lat, lon]).addTo(map);
      map.setView([lat, lon], 10);
    }

    /* ============== MAP CLICK (same) ============== */
    map.on('click', function (e) {
      let lat = e.latlng.lat;
      let lon = e.latlng.lng;
      document.getElementById("lat").value = lat;
      document.getElementById("lon").value = lon;
      if (marker) {
        map.removeLayer(marker);
      }
      marker = L.marker([lat, lon]).addTo(map);
    });
  </script>
</body>
</html>