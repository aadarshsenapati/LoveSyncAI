<?php
include "assets/connection.php";

if(!isset($_SESSION['user_id'])){
 header("Location: login.php");
 exit();
}

$user_id = $_SESSION['user_id'];

/* ==========================================
   FETCH RELATIONSHIP
========================================== */

$rel = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM relationships
WHERE partner1_id='$user_id'
OR partner2_id='$user_id'
"));

if(!$rel){
 die("No relationship found ❌");
}

$relationship_id = $rel['id'];

/* FETCH PARTNER EMAILS */
$p1 = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT email FROM users
WHERE id='{$rel['partner1_id']}'
"));

$p2 = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT email FROM users
WHERE id='{$rel['partner2_id']}'
"));

/* ==========================================
   CHECK IF VAULT EXISTS
========================================== */

$vault =
mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM couple_vaults
WHERE relationship_id='$relationship_id'
"));

/* ==========================================
   CREATE VAULT
========================================== */

if(isset($_POST['createVault'])){

 $code = rand(1000,9999);
 $vault_name = "LS".$code;

 /* CREATE VIA API */

 $payload = [
  "vault_name"=>$vault_name,
  "pin"=>$code,
  "email"=>$p1['email'],
  "is_couple"=>1
 ];

 $ch = curl_init();

 curl_setopt_array($ch,[
  CURLOPT_URL=>"http://localhost:5002/api/vault/create",
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_POST=>true,
  CURLOPT_POSTFIELDS=>json_encode($payload),
  CURLOPT_HTTPHEADER=>["Content-Type: application/json"]
 ]);

 $res = curl_exec($ch);
 curl_close($ch);

 $data = json_decode($res,true);

 if(isset($data['vault_id'])){

  /* STORE LOCALLY */

  mysqli_query($conn,"
  INSERT INTO couple_vaults(
   relationship_id,
   vault_name,
   vault_pin,
   vault_id
  ) VALUES(
   '$relationship_id',
   '$vault_name',
   '$code',
   '{$data['vault_id']}'
  )");

  /* SEND EMAIL TO BOTH */

  $msg = "Your LoveSync Couple Vault Code: $vault_name";

  foreach([$p1['email'],$p2['email']] as $mail){

   $payload = [
    "to"=>$mail,
    "subject"=>"LoveSync Vault Code ❤️",
    "message"=>$msg
   ];

   $ch = curl_init();

   curl_setopt_array($ch,[
    CURLOPT_URL=>"http://127.0.0.1:5003/send-email",
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>json_encode($payload),
    CURLOPT_HTTPHEADER=>["Content-Type: application/json"]
   ]);

   curl_exec($ch);
   curl_close($ch);
  }

  $success="Vault Created & Code Sent 💌";
 }
}

/* ==========================================
   LOGIN VAULT
========================================== */

if(isset($_POST['loginVault'])){

 $name=$_POST['vault_name'];
 $pin=$_POST['pin'];

 if($vault &&
    $name==$vault['vault_name'] &&
    $pin==$vault['vault_pin']){

  $_SESSION['vault_id']=$vault['vault_id'];
 }
}

/* ==========================================
   RESEND CODE
========================================== */

if(isset($_POST['resendCode'])){

 $msg="Your Vault Code: ".$vault['vault_name']." and the pin is ".$vault['vault_pin'];

 foreach([$p1['email'],$p2['email']] as $mail){

  $payload=[
   "to"=>$mail,
   "subject"=>"Vault Code Resent ❤️",
   "message"=>$msg
  ];

  $ch=curl_init();

  curl_setopt_array($ch,[
   CURLOPT_URL=>"http://127.0.0.1:5003/send-email",
   CURLOPT_RETURNTRANSFER=>true,
   CURLOPT_POST=>true,
   CURLOPT_POSTFIELDS=>json_encode($payload),
   CURLOPT_HTTPHEADER=>["Content-Type: application/json"]
  ]);

  curl_exec($ch);
  curl_close($ch);
 }

 $success="Code resent 📩";
}

/* ==========================================
   FETCH FILES
========================================== */

$files=[];

if(isset($_SESSION['vault_id'])){

 $vid=$_SESSION['vault_id'];

 $res=file_get_contents(
  "http://localhost:5002/api/files/$vid"
 );

 $files=json_decode($res,true)['files'] ?? [];
}

/* ==========================================
   UPLOAD FILE
========================================== */

if(isset($_POST['upload']) && isset($_SESSION['vault_id'])){

 $vid=$_SESSION['vault_id'];

 $curl=curl_init();

 curl_setopt_array($curl,[
  CURLOPT_URL=>"http://localhost:5002/api/upload",
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_POST=>true,
  CURLOPT_POSTFIELDS=>[
   "file"=>new CURLFILE($_FILES['file']['tmp_name']),
   "vault_id"=>$vid
  ]
 ]);

 curl_exec($curl);
 curl_close($curl);

 header("Location:vault.php");
}

/* ==========================================
   DELETE
========================================== */

if(isset($_GET['del'])){
 file_get_contents(
  "http://localhost:5002/api/delete/temp/".$_GET['del']
 );
 header("Location:vault.php");
}

if(isset($_GET['perma'])){
 file_get_contents(
  "http://localhost:5002/api/delete/permanent/".$_GET['perma']
 );
 header("Location:vault.php");
}

/* ==========================================
   ANALYTICS
========================================== */

$analytics=null;

if($files){

 $payload=["files"=>$files];

 $ch=curl_init();

 curl_setopt_array($ch,[
  CURLOPT_URL=>"http://localhost:5002/api/analytics",
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_POST=>true,
  CURLOPT_POSTFIELDS=>json_encode($payload),
  CURLOPT_HTTPHEADER=>["Content-Type: application/json"]
 ]);

 $res=curl_exec($ch);
 curl_close($ch);

 $analytics=json_decode($res,true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · Couple Vault</title>
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

    .wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 1300px;
      margin: 1rem auto;
    }

    /* navbar (consistent with other pages) */
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

    h2, h3 {
      font-weight: 600;
      color: #32243d;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    h2 i, h3 i {
      color: #e27c9f;
    }

    .input-group {
      position: relative;
      margin-bottom: 1.2rem;
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

    input {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 1.5px solid rgba(220, 180, 210, 0.4);
      border-radius: 3rem;
      font-size: 1rem;
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
      padding: 1rem 2rem;
      border: none;
      border-radius: 3rem;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.8rem;
      background: linear-gradient(145deg, #ff8aad, #c472e0);
      color: white;
      box-shadow: 0 10px 20px -8px rgba(220, 100, 150, 0.5);
    }

    button:hover {
      transform: scale(1.02);
      box-shadow: 0 16px 24px -8px #cb7bb0;
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.8);
      color: #5a3f63;
      border: 1px solid #e5c1d4;
      box-shadow: none;
    }

    .btn-secondary:hover {
      background: white;
    }

    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }

    .gallery-item {
      background: rgba(255, 255, 255, 0.5);
      border-radius: 1.5rem;
      padding: 1rem;
      text-align: center;
      backdrop-filter: blur(4px);
      border: 1px solid #f3cfdf;
    }

    .gallery-item img, .gallery-item video {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 1rem;
      margin-bottom: 0.8rem;
    }

    .gallery-actions {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
    }

    .gallery-actions a {
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      background: #ffe6f0;
      color: #b34e7a;
      font-size: 0.9rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
    }

    .success-message {
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid #a0e0c0;
      color: #1b6e4b;
      padding: 1rem 2rem;
      border-radius: 3rem;
      margin-bottom: 2rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .flex-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #deb0d0, transparent);
      margin: 2rem 0;
    }

    .info-note {
      text-align: center;
      color: #8a6a91;
      font-size: 0.85rem;
      margin-top: 1rem;
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
      <a href="whatsapp.php"><i class="fab fa-whatsapp"></i> WhatsApp</a>
      <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <h1><i class="fas fa-cloud-upload-alt"></i> Couple Memory Vault</h1>

  <!-- success/error messages -->
  <?php if(isset($success)): ?>
    <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
  <?php endif; ?>

  <!-- CREATE VAULT (if no vault exists) -->
  <?php if(!$vault): ?>
    <div class="glass-card" style="text-align: center;">
      <h2><i class="fas fa-lock"></i> Create Your Couple Vault</h2>
      <p style="margin-bottom: 2rem;">A secure space for your memories, accessible only with a code.</p>
      <form method="POST">
        <button type="submit" name="createVault"><i class="fas fa-plus-circle"></i> Create Vault</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- LOGIN TO VAULT (if vault exists but not logged in) -->
  <?php if($vault && !isset($_SESSION['vault_id'])): ?>
    <div class="glass-card">
      <h2><i class="fas fa-unlock-alt"></i> Unlock Vault</h2>
      <div class="flex-row" style="justify-content: space-between; align-items: flex-end;">
        <form method="POST" style="flex: 2;">
          <div class="input-group">
            <i class="fas fa-folder"></i>
            <input type="text" name="vault_name" placeholder="Vault Name" required>
          </div>
          <div class="input-group">
            <i class="fas fa-key"></i>
            <input type="text" name="pin" placeholder="4-digit PIN" required pattern="\d{4}" maxlength="4">
          </div>
          <button type="submit" name="loginVault"><i class="fas fa-sign-in-alt"></i> Unlock</button>
        </form>
        <form method="POST">
          <button type="submit" name="resendCode" class="btn-secondary"><i class="fas fa-redo-alt"></i> Resend Code</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- VAULT CONTENT (logged in) -->
  <?php if(isset($_SESSION['vault_id'])): ?>
    <!-- UPLOAD FORM -->
    <div class="glass-card">
      <h2><i class="fas fa-upload"></i> Add Memory</h2>
      <form method="POST" enctype="multipart/form-data">
        <div class="input-group">
          <i class="fas fa-image"></i>
          <input type="file" name="file" accept="image/*,video/*" required>
        </div>
        <button type="submit" name="upload"><i class="fas fa-cloud-upload-alt"></i> Upload</button>
      </form>
    </div>

    <!-- GALLERY -->
    <?php if($files): ?>
      <div class="glass-card">
        <h2><i class="fas fa-images"></i> Your Gallery</h2>
        <div class="gallery">
          <?php foreach($files as $f): ?>
            <div class="gallery-item">
              <?php
                $ext = pathinfo($f['url'], PATHINFO_EXTENSION);
                if(in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])):
              ?>
                <img src="<?= htmlspecialchars($f['url']); ?>" alt="memory">
              <?php else: ?>
                <video controls>
                  <source src="<?= htmlspecialchars($f['url']); ?>" type="video/mp4">
                </video>
              <?php endif; ?>
              <div class="gallery-actions">
                <a href="?del=<?= urlencode($f['file_id']); ?>" onclick="return confirm('Move to trash?');"><i class="fas fa-trash-alt"></i> Trash</a>
                <a href="?perma=<?= urlencode($f['file_id']); ?>" onclick="return confirm('Delete permanently?');"><i class="fas fa-ban"></i> Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="glass-card">
        <p style="text-align: center;">No memories yet. Upload your first photo or video!</p>
      </div>
    <?php endif; ?>

    <!-- ANALYTICS -->
    <?php if($analytics): ?>
      <div class="glass-card">
        <h2><i class="fas fa-chart-pie"></i> Vault Analytics</h2>
        <p><strong>Total memories:</strong> <?= $analytics['total_memories'] ?? 0; ?></p>
        <!-- you can add more analytics if available -->
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="info-note">
    <i class="fas fa-shield-heart"></i> Your memories are encrypted and stored securely.
  </div>
</div>
</body>
</html>