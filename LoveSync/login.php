<?php
include "assets/connection.php";

$API_URL = "http://127.0.0.1:5003/send-email";

/* =========================
   LOGIN
========================= */
if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query =
      "SELECT * FROM users
       WHERE email='$email'
       AND password='$password'";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {

        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];

        header("Location: dashboard.php");
        exit();

    } else {
        $error = "Invalid Email or Password ❌";
    }
}



/* =========================
   SEND OTP
========================= */
if (isset($_POST['forgot'])) {

    $email = $_POST['forgot_email'];

    $check =
      mysqli_query(
        $conn,
        "SELECT * FROM users
         WHERE email='$email'"
      );

    if (mysqli_num_rows($check) > 0) {

        $otp = rand(100000,999999);

        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;

        $data = [
            "to" => $email,
            "subject" => "LoveSync OTP Reset ❤️",
            "message" => "Your OTP is: $otp"
        ];

        $ch = curl_init($API_URL);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);

        $otp_sent = true;
        $success = "OTP sent to your email 💌";

    } else {
        $error = "Email not found ❌";
    }
}



/* =========================
   RESET PASSWORD
========================= */
if (isset($_POST['reset_password'])) {

    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];

    if ($otp == $_SESSION['reset_otp']) {

        $email = $_SESSION['reset_email'];

        mysqli_query(
          $conn,
          "UPDATE users
           SET password='$new_password'
           WHERE email='$email'"
        );

        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_email']);

        $success =
          "Password reset successful ❤️";

    } else {
        $error = "Invalid OTP ❌";
        $otp_sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync AI · Log in</title>
  <!-- Google Font & Font Awesome (same as index) -->
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
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
    }

    /* soft background blur effect */
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

    .login-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 440px;
    }

    /* glass card */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 2.5rem;
      box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
      padding: 2.8rem 2.2rem;
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
      margin-bottom: 1rem;
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
      margin-bottom: 1.4rem;
      position: relative;
    }

    .input-group i {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: #ad7a9f;
      font-size: 1.2rem;
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
    }

    button:hover {
      transform: scale(1.02);
      box-shadow: 0 16px 24px -8px #cb7bb0;
    }

    .link-btn {
      background: none;
      box-shadow: none;
      color: #b05b8f;
      font-weight: 500;
      margin-top: 0.8rem;
      border: 1.5px solid #e5c1d4;
      background: rgba(255, 255, 255, 0.5);
      backdrop-filter: blur(4px);
    }

    .link-btn:hover {
      background: white;
      transform: none;
      box-shadow: 0 6px 12px rgba(180, 120, 150, 0.2);
    }

    .toggle-link {
      text-align: center;
      margin: 1.5rem 0 0.5rem;
    }

    .toggle-link span {
      color: #b95c8f;
      font-weight: 600;
      cursor: pointer;
      text-decoration: underline dotted;
      text-underline-offset: 5px;
    }

    .message {
      padding: 1rem;
      border-radius: 3rem;
      margin-bottom: 1.8rem;
      font-weight: 500;
      text-align: center;
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(4px);
    }

    .error {
      color: #b33f4b;
      border: 1px solid #ffc0cb;
    }

    .success {
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

    /* forgot box and otp section transitions */
    #forgotBox, .otp-section {
      transition: all 0.2s ease;
    }
  </style>
</head>
<body>
  <div class="bg-bubble"></div>
  <div class="bg-bubble2"></div>

  <div class="login-container">
    <div class="glass-card">

      <!-- logo & back pointer -->
      <div class="logo">
        <i class="fas fa-heart-circle-check"></i>
        <span>LoveSync AI</span>
      </div>
      <h2>welcome back</h2>
      <div class="sub-head">
        <i class="fas fa-heart" style="color: #e6769e;"></> 
        <a href="index.php">return to home</a> · 
        <a href="signup.php">create account</a>
      </div>

      <!-- status messages -->
      <?php if(isset($error)): ?>
        <div class="message error"><i class="fas fa-circle-exclamation" style="margin-right: 8px;"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if(isset($success)): ?>
        <div class="message success"><i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <!-- LOGIN FORM (default) -->
      <form method="POST">
        <div class="input-group">
          <i class="far fa-envelope"></i>
          <input type="email" name="email" placeholder="Email address" required>
        </div>
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" name="login">
          <i class="fas fa-arrow-right-to-bracket"></i> Log in
        </button>
      </form>

      <!-- toggle forgot password link -->
      <div class="toggle-link">
        <span onclick="toggleForgot()"><i class="far fa-question-circle"></i> Forgot password?</span>
      </div>

      <!-- FORGOT PASSWORD BOX (hidden by default) -->
      <div id="forgotBox" style="display: none; margin-top: 1.5rem;">
        <form method="POST">
          <div class="input-group">
            <i class="far fa-envelope"></i>
            <input type="email" name="forgot_email" placeholder="Your email address" required>
          </div>
          <button type="submit" name="forgot" class="link-btn">
            <i class="fas fa-paper-plane"></i> Send OTP
          </button>
        </form>
        <div style="text-align: center; margin: 1rem 0 0; font-size:0.9rem; color:#8d5a81;">
          <i class="fas fa-heart" style="opacity:0.6;"></> we'll send a 6-digit code
        </div>
      </div>

      <!-- OTP / RESET SECTION (shown only after OTP sent) -->
      <?php if(isset($otp_sent) && $otp_sent === true): ?>
        <div class="otp-section" style="margin-top: 2rem; border-top: 2px dashed #f0c6df; padding-top: 2rem;">
          <h4 style="color:#483251; margin-bottom:1.2rem; text-align:center;">🔐 reset password</h4>
          <form method="POST">
            <div class="input-group">
              <i class="fas fa-hashtag"></i>
              <input type="text" name="otp" placeholder="6-digit OTP" required>
            </div>
            <div class="input-group">
              <i class="fas fa-key"></i>
              <input type="password" name="new_password" placeholder="New password" required>
            </div>
            <button type="submit" name="reset_password" class="link-btn" style="background: linear-gradient(145deg, #b483cf, #e37da0); color:white;">
              <i class="fas fa-check"></i> Update password
            </button>
          </form>
        </div>
      <?php endif; ?>

      <hr>

      <div class="extra-links">
        <a href="signup.php"><i class="fas fa-user-plus"></i> Sign up</a>
      </div>

      <div class="back-home">
        <a href="index.html"><i class="fas fa-chevron-left"></i> back to LoveSync</a>
      </div>

    </div> <!-- end glass-card -->

    <div style="text-align: center; margin-top: 1.5rem; color:#9b83a3; font-size:0.85rem;">
      <i class="fas fa-shield-heart"></i> secure & encrypted
    </div>
  </div> <!-- end login-container -->

  <script>
    function toggleForgot() {
      const box = document.getElementById("forgotBox");
      if (box.style.display === "none" || box.style.display === "") {
        box.style.display = "block";
        // optionally scroll into view
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        box.style.display = "none";
      }
    }

    // If any error or success, ensure forgot box stays visible if needed?
    // But keep it simple: no auto open.
  </script>
</body>
</html>