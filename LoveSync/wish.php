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

$relationship =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM relationships
 WHERE partner1_id='$user_id'
 OR partner2_id='$user_id'
 ")
);

/* ==========================================
   GET PARTNER EMAIL
========================================== */

$partner_email = "";

if($relationship){

 $partner_id =
 ($relationship['partner1_id']==$user_id)
 ? $relationship['partner2_id']
 : $relationship['partner1_id'];

 if($partner_id){

  $partner =
  mysqli_fetch_assoc(
   mysqli_query($conn,"
   SELECT email
   FROM users
   WHERE id='$partner_id'
   ")
  );

  $partner_email =
  $partner['email'] ?? "";
 }
}


// =============================
// HANDLE AJAX REQUESTS
// =============================
if(isset($_POST['action'])) {

    // =========================
    // 1️⃣ GENERATE AI MESSAGE (GROQ)
    // =========================
    if($_POST['action'] == "generate") {

        $prompt = $_POST['prompt'];

        // Get your free key from: https://console.groq.com/keys
        $apiKey = "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";

        $url = "https://api.groq.com/openai/v1/chat/completions";

        $data = [
            "model" => "llama-3.3-70b-versatile",
            "messages" => [
                [
                    "role" => "user",
                    "content" => "Write a heartfelt romantic or celebration message:\n".$prompt
                ]
            ],
            "temperature" => 0.7,
            "max_tokens" => 300
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer ".$apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(curl_errno($ch)) {
            echo json_encode([
                "output" => "cURL Error: ".curl_error($ch)
            ]);
            curl_close($ch);
            exit;
        }

        curl_close($ch);

        $result = json_decode($response, true);

        // Extract AI response
        if(isset($result['choices'][0]['message']['content'])) {
            echo json_encode([
                "output" => $result['choices'][0]['message']['content']
            ]);
        } else {
            echo json_encode([
                "output" => "AI Error: " . ($result['error']['message'] ?? 'Unknown error'),
                "debug" => $result,
                "http_code" => $httpCode
            ]);
        }

        exit;
    }

    // =========================
    // 2️⃣ SEND EMAIL NOW
    // =========================
    if($_POST['action'] == "send") {

        $data = [
            "to" => $_POST['to'],
            "subject" => $_POST['subject'],
            "message" => $_POST['message']
        ];

        $ch = curl_init("http://127.0.0.1:5003/send-email");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;
        exit;
    }

    // =========================
    // 3️⃣ SCHEDULE EMAIL
    // =========================
    if($_POST['action'] == "schedule") {

        $data = [
            "to" => $_POST['to'],
            "subject" => $_POST['subject'],
            "message" => $_POST['message'],
            "datetime" => $_POST['datetime']
        ];

        $ch = curl_init("http://127.0.0.1:5003/schedule-email");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · AI Wish Maker</title>
  <!-- Google Font & Font Awesome (same as other pages) -->
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

    /* background bubbles (same as dashboard) */
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
      max-width: 800px;
      margin: 1rem auto;
    }

    /* navigation (matching dashboard) */
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

    /* glass card for form */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 2.5rem;
      box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
      padding: 2.5rem 2.2rem;
      width: 100%;
    }

    h2 {
      font-weight: 600;
      font-size: 2rem;
      color: #32243d;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    h2 i {
      color: #e6769e;
    }

    .sub-head {
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

    label {
      font-weight: 500;
      color: #5a3f63;
      display: block;
      margin: 1rem 0 0.3rem 0.5rem;
    }

    input, select, textarea {
      width: 100%;
      padding: 1rem 1.5rem;
      border: 1.5px solid rgba(220, 180, 210, 0.4);
      border-radius: 3rem;
      font-size: 1rem;
      background: rgba(255, 250, 250, 0.8);
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
      margin-bottom: 0.8rem;
    }

    textarea {
      border-radius: 1.8rem;
      resize: vertical;
      min-height: 160px;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #e27c9f;
      background: white;
      box-shadow: 0 0 0 4px rgba(230, 120, 160, 0.15);
    }

    .button-group {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
      margin: 1.5rem 0;
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
      gap: 0.6rem;
      background: linear-gradient(145deg, #ff8aad, #c472e0);
      color: white;
      box-shadow: 0 10px 20px -8px rgba(220, 100, 150, 0.5);
    }

    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    button:hover:not(:disabled) {
      transform: scale(1.02);
      box-shadow: 0 16px 24px -8px #cb7bb0;
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.8);
      color: #5a3f63;
      border: 1px solid #e5c1d4;
      box-shadow: none;
    }

    .btn-secondary:hover:not(:disabled) {
      background: white;
    }

    .loading {
      display: none;
      margin: 1rem 0;
      color: #b05b8f;
      font-weight: 600;
      text-align: center;
    }

    #status {
      font-weight: 600;
      margin-top: 1.5rem;
      padding: 1rem;
      border-radius: 3rem;
      background: rgba(255, 255, 255, 0.7);
      text-align: center;
    }

    hr {
      border: none;
      height: 1px;
      background: linear-gradient(to right, transparent, #deb0d0, transparent);
      margin: 2rem 0;
    }

    .info-note {
      font-size: 0.85rem;
      color: #9b83a3;
      text-align: center;
      margin-top: 1rem;
    }

    /* Toast notification */
    .toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 3rem;
      padding: 1rem 2rem;
      box-shadow: 0 20px 40px -10px rgba(70, 20, 60, 0.3);
      display: flex;
      align-items: center;
      gap: 1rem;
      font-weight: 500;
      color: #32243d;
      transform: translateY(200%);
      transition: transform 0.3s ease;
      z-index: 9999;
    }

    .toast.show {
      transform: translateY(0);
    }

    .toast i {
      font-size: 1.5rem;
      color: #e6769e;
    }
  </style>
</head>
<body>
<div class="bg-bubble"></div>
<div class="bg-bubble2"></div>

<!-- Toast notification -->
<div id="toast" class="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMessage">Email sent!</span>
</div>

<div class="wrapper">
  <!-- navigation bar (consistent with dashboard) -->
  <nav class="navbar">
    <div class="nav-logo">
      <i class="fas fa-heart-circle-check"></i>
      <span>LoveSync</span>
    </div>
    <div class="nav-links">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="profile.php"><i class="far fa-user"></i> Profile</a>
      <a href="wish.php" style="color:#d4588a;"><i class="fas fa-envelope"></i> Wish Maker</a>
      <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <!-- main card -->
  <div class="glass-card">
    <h2><i class="fas fa-envelope-open-text"></i> AI Wish Maker & Sender</h2>
    <div class="sub-head">
      <i class="fas fa-heart" style="color: #e6769e;"></i> 
      Powered by Groq · schedule heartfelt messages
    </div>

    <!-- recipient name -->
    <label><i class="fas fa-user"></i> Recipient Name</label>
    <input type="text" id="name" placeholder="e.g. Alex">

    <!-- wish type -->
    <label><i class="fas fa-tag"></i> Message Type</label>
    <select id="type">
      <option value="birthday">Birthday Wish 🎂</option>
      <option value="love">Love Letter ❤️</option>
      <option value="anniversary">Anniversary 💑</option>
      <option value="apology">Apology 🙏</option>
      <option value="valentinesday">Valentine's Day 🌹</option>
    </select>

    <!-- generate button -->
    <div class="button-group">
      <button onclick="generateWish()"><i class="fas fa-magic"></i> Generate Message</button>
    </div>
    <div class="loading" id="loading">⏳ Generating romantic message...</div>

    <!-- message textarea -->
    <label><i class="fas fa-edit"></i> Edit Message</label>
    <textarea id="message" placeholder="Your message will appear here..."></textarea>

    <hr>

    <!-- email fields -->
    <label><i class="fas fa-envelope"></i> Recipient Email</label>
    <input type="email" id="email" placeholder="partner@example.com" value="<?php echo $partner_email; ?>">

    <label><i class="fas fa-heading"></i> Email Subject</label>
    <input type="text" id="subject" placeholder="e.g. Thinking of you...">

    <!-- schedule -->
    <label><i class="far fa-clock"></i> Schedule (optional)</label>
    <input type="datetime-local" id="datetime">

    <!-- action buttons -->
    <div class="button-group">
      <button onclick="sendNow()" id="sendBtn"><i class="fas fa-paper-plane"></i> Send Now</button>
      <button onclick="scheduleMail()" id="scheduleBtn" class="btn-secondary"><i class="fas fa-calendar-check"></i> Schedule</button>
    </div>

    <!-- status message -->
    <div id="status"></div>
    <div class="info-note">
      <i class="fas fa-shield-heart"></i> Messages are generated by AI and can be edited before sending.
    </div>
  </div>
</div>

<!-- JavaScript (identical functionality, just beautified) -->
<script>
// Toast function
function showToast(message, isSuccess = true) {
  const toast = document.getElementById('toast');
  const toastMsg = document.getElementById('toastMessage');
  toastMsg.innerText = message;
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}

// =============================
// GENERATE AI MESSAGE
// =============================
function generateWish() {
  let name = document.getElementById("name").value;
  let type = document.getElementById("type").value;
  
  if(!name) {
    alert("Please enter recipient name!");
    return;
  }

  let prompt = "";

  if(type === "birthday")
    prompt = `Write a heartfelt birthday wish for ${name}`;
  else if(type === "love")
    prompt = `Write a romantic love letter for ${name}`;
  else if(type === "anniversary")
    prompt = `Write an anniversary wish for ${name}`;
  else if(type === "valentinesday")
    prompt = `Write a valentine's day wish for ${name}`;
  else
    prompt = `Write an apology message for ${name}`;

  // Show loading
  document.getElementById("loading").style.display = "block";
  document.getElementById("message").value = "Generating...";

  let formData = new URLSearchParams();
  formData.append("action","generate");
  formData.append("prompt",prompt);

  fetch("wish.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById("loading").style.display = "none";
    
    if(data.output && !data.debug) {
      message.value = data.output;
    } else {
      message.value = data.output || "Error generating message";
    }
  })
  .catch(err => {
    document.getElementById("loading").style.display = "none";
    message.value = "Error generating message: " + err;
  });
}

// =============================
// SEND EMAIL
// =============================
function sendNow() {
  let email = document.getElementById("email");
  let subject = document.getElementById("subject");
  let message = document.getElementById("message");
  let status = document.getElementById("status");
  let sendBtn = document.getElementById("sendBtn");

  if(!email.value || !subject.value || !message.value) {
    alert("Please fill all fields!");
    return;
  }

  // Disable button to prevent multiple clicks
  sendBtn.disabled = true;
  status.innerText = "Sending...";

  let formData = new URLSearchParams();
  formData.append("action","send");
  formData.append("to", email.value);
  formData.append("subject", subject.value);
  formData.append("message", message.value);

  fetch("wish.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    status.innerText = data.status || data.error || "Sent!";
    showToast("Email sent successfully! 💌");
    sendBtn.disabled = false;
  })
  .catch(err => {
    status.innerText = "Error: " + err;
    showToast("Failed to send email", false);
    sendBtn.disabled = false;
  });
}

// =============================
// SCHEDULE EMAIL
// =============================
function scheduleMail() {
  let email = document.getElementById("email");
  let subject = document.getElementById("subject");
  let message = document.getElementById("message");
  let datetime = document.getElementById("datetime");
  let status = document.getElementById("status");
  let scheduleBtn = document.getElementById("scheduleBtn");

  if(!email.value || !subject.value || !message.value || !datetime.value) {
    alert("Please fill all fields including schedule time!");
    return;
  }

  // Disable button
  scheduleBtn.disabled = true;
  status.innerText = "Scheduling...";

  let dt = datetime.value.replace("T"," ");

  let formData = new URLSearchParams();
  formData.append("action","schedule");
  formData.append("to", email.value);
  formData.append("subject", subject.value);
  formData.append("message", message.value);
  formData.append("datetime", dt);

  fetch("wish.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    status.innerText = data.status || data.error || "Scheduled!";
    showToast("Email scheduled! 📅");
    scheduleBtn.disabled = false;
  })
  .catch(err => {
    status.innerText = "Error: " + err;
    showToast("Failed to schedule", false);
    scheduleBtn.disabled = false;
  });
}
</script>
</body>
</html>