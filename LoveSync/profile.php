<?php
/* ==========================================
   SESSION + DB
========================================== */

include "assets/connection.php"; 
// connection.php already has session_start()

if(!isset($_SESSION['user_id'])){
 header("Location: login.php");
 exit();
}

$user_id = $_SESSION['user_id'];


/* ==========================================
   FETCH USER
========================================== */

$user =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT *
 FROM users
 WHERE id='$user_id'
 ")
);


/* ==========================================
   FETCH RELATIONSHIP
========================================== */

$relationship =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT *
 FROM relationships
 WHERE partner1_id='$user_id'
 OR partner2_id='$user_id'
 ")
);


/* ==========================================
   FETCH PARTNER (IF COMMITTED)
========================================== */

$partner = null;

if($relationship){

 $partner_id =
 ($relationship['partner1_id']==$user_id)
 ? $relationship['partner2_id']
 : $relationship['partner1_id'];

 if($partner_id){

  $partner =
  mysqli_fetch_assoc(
   mysqli_query($conn,"
   SELECT *
   FROM users
   WHERE id='$partner_id'
   ")
  );
 }
}


/* ==========================================
   UPDATE PROFILE
========================================== */

if(isset($_POST['updateProfile'])){

$name = $_POST['name'];
$dob  = $_POST['dob'];
$tob  = $_POST['tob'];
$pob  = $_POST['pob'];
$lat  = $_POST['latitude'];
$lon  = $_POST['longitude'];

mysqli_query($conn,"
UPDATE users SET

 full_name ='$name',
 dob       ='$dob',
 tob       ='$tob',
 pob       ='$pob',
 latitude  ='$lat',
 longitude ='$lon'

WHERE id='$user_id'
");

header("Location: profile.php?updated=1");
exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · My Profile</title>
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
      max-width: 900px;
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
    }

    h1 {
      font-weight: 700;
      font-size: 2.2rem;
      color: #32243d;
      display: flex;
      align-items: center;
      gap: 0.8rem;
      margin-bottom: 0.5rem;
    }

    h2 {
      font-weight: 600;
      font-size: 1.6rem;
      color: #32243d;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    h2 i {
      color: #e27c9f;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.8rem;
      border-radius: 3rem;
      font-weight: 600;
      font-size: 1rem;
    }

    .status-single {
      background: #ffe6f0;
      color: #b34e7a;
      border: 1px solid #fbc1d4;
    }

    .status-committed {
      background: #e0f5e9;
      color: #1b7e4b;
      border: 1px solid #a8e0c0;
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

    .input-group {
      margin-bottom: 1.2rem;
      position: relative;
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

    input {
      width: 100%;
      padding: 1rem 1rem 1rem 3.2rem;
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
      margin-top: 0.8rem;
    }

    button:hover {
      transform: scale(1.02);
      box-shadow: 0 16px 24px -8px #cb7bb0;
    }

    .partner-detail {
      background: rgba(255, 245, 250, 0.7);
      border-radius: 1.5rem;
      padding: 1.5rem;
      margin-top: 1rem;
      border: 1px solid #f3cfdf;
    }

    .partner-detail p {
      margin: 0.8rem 0;
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 1.1rem;
    }

    .partner-detail i {
      color: #c86f9e;
      width: 1.8rem;
      text-align: center;
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
      margin-top: 1rem;
      text-align: center;
    }
  </style>
</head>
<body>
<div class="bg-bubble"></div>
<div class="bg-bubble2"></div>

<div class="wrapper">
  <!-- navigation bar (same as wish/dashboard) -->
  <nav class="navbar">
    <div class="nav-logo">
      <i class="fas fa-heart-circle-check"></i>
      <span>LoveSync</span>
    </div>
    <div class="nav-links">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="profile.php" style="color:#d4588a;"><i class="far fa-user"></i> Profile</a>
      <a href="wish.php"><i class="fas fa-envelope"></i> Wish Maker</a>
      <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <!-- page title -->
  <h1><i class="fas fa-id-badge"></i> My Profile</h1>

  <?php if(isset($_GET['updated'])){ ?>
    <div class="success-message">
      <i class="fas fa-check-circle"></i> Profile Updated Successfully ✅
    </div>
  <?php } ?>

  <!-- RELATIONSHIP STATUS CARD -->
  <div class="glass-card">
    <h2><i class="fas fa-heart"></i> Relationship Status</h2>

    <?php if($relationship && $partner){ ?>
      <div class="status-badge status-committed">
        <i class="fas fa-heart"></i> Committed ❤️
      </div>
      <p style="margin-top: 1.2rem; font-size: 1.2rem;">
        <i class="fas fa-user" style="color:#c672b0;"></i> 
        Partner: <strong><?= htmlspecialchars($partner['full_name'] ?? 'Not Available'); ?></strong>
      </p>
    <?php } else { ?>
      <div class="status-badge status-single">
        <i class="fas fa-star"></i> Single 💙
      </div>
      <p style="margin-top: 1.2rem;">No partner linked yet.</p>
    <?php } ?>
  </div>

  <!-- MY DETAILS (EDITABLE) -->
  <div class="glass-card">
    <h2><i class="fas fa-pencil-alt"></i> My Details</h2>
    <form method="POST">
      <div class="input-group">
        <i class="far fa-user"></i>
        <input type="text" name="name" placeholder="Full name" value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>" required>
      </div>

      <div class="input-group">
        <i class="fas fa-cake-candles"></i>
        <input type="date" name="dob" value="<?= $user['dob'] ?? ''; ?>">
      </div>

      <div class="input-group">
        <i class="fas fa-clock"></i>
        <input type="time" name="tob" value="<?= $user['tob'] ?? ''; ?>">
      </div>

      <div class="input-group">
        <i class="fas fa-map-pin"></i>
        <input type="text" name="pob" placeholder="Place of birth" value="<?= htmlspecialchars($user['pob'] ?? ''); ?>">
      </div>

      <div class="input-group">
        <i class="fas fa-globe"></i>
        <input type="text" name="latitude" placeholder="Latitude" value="<?= htmlspecialchars($user['latitude'] ?? ''); ?>">
      </div>

      <div class="input-group">
        <i class="fas fa-globe"></i>
        <input type="text" name="longitude" placeholder="Longitude" value="<?= htmlspecialchars($user['longitude'] ?? ''); ?>">
      </div>

      <button type="submit" name="updateProfile">
        <i class="fas fa-save"></i> Update Profile
      </button>
    </form>
  </div>

  <!-- PARTNER DETAILS (if committed) -->
  <?php if($partner){ ?>
    <div class="glass-card">
      <h2><i class="fas fa-heart"></i> Partner Details</h2>
      <div class="partner-detail">
        <p><i class="fas fa-user"></i> <strong><?= htmlspecialchars($partner['full_name'] ?? 'N/A'); ?></strong></p>
        <p><i class="fas fa-cake-candles"></i> DOB: <?= htmlspecialchars($partner['dob'] ?? 'N/A'); ?></p>
        <p><i class="fas fa-clock"></i> TOB: <?= htmlspecialchars($partner['tob'] ?? 'N/A'); ?></p>
        <p><i class="fas fa-map-pin"></i> POB: <?= htmlspecialchars($partner['pob'] ?? 'N/A'); ?></p>
      </div>
    </div>
  <?php } ?>

  <div class="info-note">
    <i class="fas fa-shield-heart"></i> Your information is securely stored.
  </div>
</div>

</body>
</html>