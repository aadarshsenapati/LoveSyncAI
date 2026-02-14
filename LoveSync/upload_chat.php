<?php
include "assets/connection.php";

if(!isset($_SESSION['user_id'])){
 header("Location: login.php");
 exit();
}

$user_id = $_SESSION['user_id'];

/* ============================================
   FETCH RELATIONSHIP
============================================ */

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


/* ============================================
   UPLOAD ZIP → FLASK API
============================================ */

if(isset($_POST['upload'])){

 if($_FILES['zip']['error'] == 0){

  $zipPath = $_FILES['zip']['tmp_name'];

  /* ===== SAME POSTMAN METHOD ===== */

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL =>
    'http://127.0.0.1:5001/analyze-zip',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => array(
      'file' => new CURLFILE($zipPath)
    ),
  ));

  $response = curl_exec($curl);
  curl_close($curl);

  $data = json_decode($response,true);

  if(!$data){
   die("API Error ❌");
  }


/* ============================================
   CHECK EXISTING CHAT REPORT
============================================ */

$existing =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM chat_reports
 WHERE relationship_id='$relationship_id'
 ")
);

if($existing){

 $chat_id = $existing['id'];

 /* UPDATE */

 mysqli_query($conn,"
 UPDATE chat_reports SET

 first_chat_date = '{$data['first_chat_date']}',
 last_chat_date  = '{$data['last_chat_date']}',

 longest_chat_streak_days =
 '{$data['longest_chat_streak_days']}',

 longest_no_chat_gap_days =
 '{$data['longest_no_chat_gap_days']}',

 most_active_day =
 '{$data['most_active_day']}',

 most_active_hour =
 '{$data['most_active_hour']}',

 total_messages =
 '{$data['total_messages']}',

 media_messages =
 '{$data['media_messages']}',

 media_files_in_zip =
 '{$data['media_files_in_zip']}',

 love_score_percentage =
 '{$data['love_score_percentage']}',

 who_loves_more =
 '{$data['who_loves_more']}',

 longest_message_text =
 '".mysqli_real_escape_string(
   $conn,
   $data['longest_message']['message']
 )."',

 longest_message_sender =
 '{$data['longest_message']['sender']}',

 longest_message_datetime =
 '{$data['longest_message']['datetime']}',

 longest_message_length =
 '{$data['longest_message']['length_characters']}'

 WHERE relationship_id='$relationship_id'
 ");

}else{

 /* INSERT */

 mysqli_query($conn,"
 INSERT INTO chat_reports(

 relationship_id,
 first_chat_date,
 last_chat_date,
 longest_chat_streak_days,
 longest_no_chat_gap_days,
 most_active_day,
 most_active_hour,
 total_messages,
 media_messages,
 media_files_in_zip,
 love_score_percentage,
 who_loves_more,
 longest_message_text,
 longest_message_sender,
 longest_message_datetime,
 longest_message_length

 ) VALUES (

 '$relationship_id',
 '{$data['first_chat_date']}',
 '{$data['last_chat_date']}',
 '{$data['longest_chat_streak_days']}',
 '{$data['longest_no_chat_gap_days']}',
 '{$data['most_active_day']}',
 '{$data['most_active_hour']}',
 '{$data['total_messages']}',
 '{$data['media_messages']}',
 '{$data['media_files_in_zip']}',
 '{$data['love_score_percentage']}',
 '{$data['who_loves_more']}',
 '".mysqli_real_escape_string(
   $conn,
   $data['longest_message']['message']
 )."',
 '{$data['longest_message']['sender']}',
 '{$data['longest_message']['datetime']}',
 '{$data['longest_message']['length_characters']}'
 )");

 $chat_id = mysqli_insert_id($conn);
}


/* ============================================
   CLEAR OLD USER METRICS
============================================ */

mysqli_query($conn,"
DELETE FROM chat_user_metrics
WHERE chat_report_id='$chat_id'
");


/* ============================================
   INSERT USER METRICS
============================================ */

foreach(
 $data['messages_per_user']
 as $name => $count
){

 $love =
 $data['i_love_you_count_per_user'][$name] ?? 0;

 $miss =
 $data['i_miss_you_count_per_user'][$name] ?? 0;

 $formula =
 $data['love_formula_scores'][$name] ?? 0;

 mysqli_query($conn,"
 INSERT INTO chat_user_metrics(

 chat_report_id,
 user_name,
 messages_count,
 i_love_you_count,
 i_miss_you_count,
 love_formula_score

 ) VALUES (

 '$chat_id',
 '$name',
 '$count',
 '$love',
 '$miss',
 '$formula'
 )");
}


/* ============================================
   CLEAR OLD MOMENTS
============================================ */

mysqli_query($conn,"
DELETE FROM love_moments
WHERE chat_report_id='$chat_id'
");


/* ============================================
   SAVE MOMENTS FUNCTION
============================================ */

function saveMoment(
 $conn,$chat_id,$type,$obj
){

 if(!$obj) return;

 mysqli_query($conn,"
 INSERT INTO love_moments(

 chat_report_id,
 moment_type,
 sender,
 moment_datetime

 ) VALUES (

 '$chat_id',
 '$type',
 '{$obj['sender']}',
 '{$obj['datetime']}'
 )");
}


/* SAVE ALL MOMENTS */

saveMoment(
 $conn,$chat_id,
 "first_i_love_you",
 $data['first_i_love_you']
);

saveMoment(
 $conn,$chat_id,
 "first_i_miss_you",
 $data['first_i_miss_you']
);

saveMoment(
 $conn,$chat_id,
 "first_heart_emoji",
 $data['first_heart_emoji']
);

saveMoment(
 $conn,$chat_id,
 "first_kiss_emoji",
 $data['first_kiss_emoji']
);


$success = "Chat uploaded & analyzed ❤️";
 }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · WhatsApp Upload</title>
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

    /* two-column layout */
    .two-column {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      align-items: start;
    }

    /* glass card */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 2.5rem;
      box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
      padding: 2rem 2rem;
      height: 100%;
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

    h2 i {
      color: #e27c9f;
    }

    h3 {
      font-weight: 600;
      font-size: 1.3rem;
      color: #4f3570;
      margin: 1.5rem 0 0.8rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .instruction-step {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.5rem 0;
      background: rgba(255, 240, 250, 0.5);
      padding: 1rem 1.5rem;
      border-radius: 2rem;
    }

    .step-number {
      width: 2.5rem;
      height: 2.5rem;
      background: #eac0db;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.3rem;
      color: #3b2445;
    }

    .step-text {
      font-size: 1.1rem;
      color: #2d1f38;
    }

    .step-text small {
      display: block;
      color: #8a6a91;
      font-size: 0.9rem;
      margin-top: 0.2rem;
    }

    .device-toggle {
      display: flex;
      gap: 1rem;
      margin: 2rem 0 1rem;
    }

    .device-btn {
      background: rgba(255,255,255,0.6);
      border: 1px solid #e2b2cf;
      border-radius: 2rem;
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      cursor: pointer;
      transition: 0.2s;
    }

    .device-btn.active {
      background: #eac0db;
      border-color: #c672b0;
      color: #2d1f38;
    }

    .device-content {
      display: none;
    }

    .device-content.active {
      display: block;
    }

    .input-group {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .input-group i {
      position: absolute;
      left: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
      color: #ad7a9f;
      font-size: 1.2rem;
      z-index: 2;
    }

    input[type="file"] {
      width: 100%;
      padding: 1rem 1rem 1rem 3.2rem;
      border: 1.5px solid rgba(220, 180, 210, 0.4);
      border-radius: 3rem;
      font-size: 0.95rem;
      background: rgba(255, 250, 250, 0.8);
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
    }

    input[type="file"]::file-selector-button {
      background: #eac0db;
      border: none;
      border-radius: 2rem;
      padding: 0.4rem 1.2rem;
      margin-right: 1rem;
      color: #3b2445;
      font-weight: 500;
    }

    button {
      width: 100%;
      padding: 1rem;
      border: none;
      border-radius: 3rem;
      font-weight: 600;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
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

    .success-message {
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid #a0e0c0;
      color: #1b6e4b;
      padding: 1rem 2rem;
      border-radius: 3rem;
      margin-top: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .info-note {
      text-align: center;
      color: #8a6a91;
      font-size: 0.85rem;
      margin-top: 2rem;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #deb0d0, transparent);
      margin: 1.5rem 0;
    }

    @media (max-width: 768px) {
      .two-column {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
<div class="bg-bubble"></div>
<div class="bg-bubble2"></div>

<div class="wrapper">
  <!-- navigation -->
  <nav class="navbar">
    <div class="nav-logo">
      <i class="fas fa-heart-circle-check"></i>
      <span>LoveSync</span>
    </div>
    <div class="nav-links">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="profile.php"><i class="far fa-user"></i> Profile</a>
      <a href="wish.php"><i class="fas fa-envelope"></i> Wish</a>
      <a href="astro.php"><i class="fas fa-sparkles"></i> Astro</a>
      <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <!-- two-column layout -->
  <div class="two-column">
    <!-- LEFT: Instructions -->
    <div class="glass-card">
      <h2><i class="fas fa-info-circle"></i> How to Export</h2>
      <p style="margin-bottom: 1.5rem;">Follow these steps to get your WhatsApp chat .zip file:</p>

      <!-- Device toggle buttons (just for show, but we can make them interactive with simple JS) -->
      <div class="device-toggle" id="deviceToggle">
        <span class="device-btn active" data-device="android">Android</span>
        <span class="device-btn" data-device="ios">iPhone</span>
      </div>

      <!-- Android instructions (visible by default) -->
      <div id="androidSteps" class="device-content active">
        <div class="instruction-step">
          <div class="step-number">1</div>
          <div class="step-text">Open WhatsApp <i class="fab fa-whatsapp" style="color:#25D366;"></i></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">2</div>
          <div class="step-text">Open the chat you want to analyze<br><small>Individual or group chat</small></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">3</div>
          <div class="step-text">Tap on the contact/group name at the top</div>
        </div>
        <div class="instruction-step">
          <div class="step-number">4</div>
          <div class="step-text">Scroll down and tap <strong>Export Chat</strong></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">5</div>
          <div class="step-text">Choose <strong>Include Media</strong> (recommended) or Without Media<br><small>Media helps enrich your memory vault</small></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">6</div>
          <div class="step-text">Save the .zip file to your device</div>
        </div>
      </div>

      <!-- iOS instructions (hidden by default) -->
      <div id="iosSteps" class="device-content">
        <div class="instruction-step">
          <div class="step-number">1</div>
          <div class="step-text">Open WhatsApp <i class="fab fa-whatsapp" style="color:#25D366;"></i></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">2</div>
          <div class="step-text">Go to the chat you want to export</div>
        </div>
        <div class="instruction-step">
          <div class="step-number">3</div>
          <div class="step-text">Tap the contact/group name at the top</div>
        </div>
        <div class="instruction-step">
          <div class="step-number">4</div>
          <div class="step-text">Scroll down and tap <strong>Export Chat</strong></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">5</div>
          <div class="step-text">Choose <strong>Include Media</strong> or Without Media<br><small>Media files will be included in the .zip</small></div>
        </div>
        <div class="instruction-step">
          <div class="step-number">6</div>
          <div class="step-text">Save the .zip to Files or iCloud</div>
        </div>
      </div>

      <div class="info-note" style="margin-top: 2rem;">
        <i class="fas fa-shield-heart"></i> Your privacy is protected. All analysis happens locally.
      </div>
    </div>

    <!-- RIGHT: Upload Form -->
    <div class="glass-card">
      <h2><i class="fas fa-cloud-upload-alt"></i> Upload Chat</h2>
      <div class="sub-head">
        <i class="fas fa-heart"></i> Select your exported .zip file
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div class="input-group">
          <i class="fas fa-file-zipper"></i>
          <input type="file" name="zip" accept=".zip" required>
        </div>

        <button type="submit" name="upload">
          <i class="fas fa-cloud-upload-alt"></i> Upload & Analyze
        </button>
      </form>

      <?php if(isset($success)): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <meta http-equiv="refresh" content="1;url=whatsapp.php">
      <?php endif; ?>

      <hr>
      <div class="info-note">
        <i class="fas fa-shield-heart"></i> We only read metadata and message text, not your media content.
      </div>
    </div>
  </div>
</div>

<!-- Simple JS to toggle device instructions -->
<script>
  const deviceBtns = document.querySelectorAll('.device-btn');
  const androidSteps = document.getElementById('androidSteps');
  const iosSteps = document.getElementById('iosSteps');

  deviceBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      deviceBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const device = this.getAttribute('data-device');
      if (device === 'android') {
        androidSteps.classList.add('active');
        iosSteps.classList.remove('active');
      } else {
        iosSteps.classList.add('active');
        androidSteps.classList.remove('active');
      }
    });
  });
</script>
</body>
</html>