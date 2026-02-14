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
   FETCH CHAT REPORT
============================================ */

$chat =
mysqli_fetch_assoc(
 mysqli_query($conn,"
 SELECT * FROM chat_reports
 WHERE relationship_id='$relationship_id'
 ")
);

if(!$chat){
 die("No WhatsApp data found ❌");
}

$chat_id = $chat['id'];

/* ============================================
   FETCH USER METRICS
============================================ */

$users =
mysqli_query($conn,"
SELECT * FROM chat_user_metrics
WHERE chat_report_id='$chat_id'
");

/* ============================================
   FETCH LOVE MOMENTS
============================================ */

$moments =
mysqli_query($conn,"
SELECT * FROM love_moments
WHERE chat_report_id='$chat_id'
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync · WhatsApp Analytics</title>
  <!-- Google Font & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    /* navbar */
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

    h3 {
      font-weight: 600;
      font-size: 1.3rem;
      color: #32243d;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }

    .center {
      text-align: center;
    }

    /* love gauge */
    .gauge-container {
      display: flex;
      justify-content: center;
      margin: 1.5rem 0;
    }

    .gauge {
      width: 220px;
      height: 220px;
      border-radius: 50%;
      background: conic-gradient(#ff6b9d 0% <?php echo $chat['love_score_percentage']; ?>%, #f0d2e6 <?php echo $chat['love_score_percentage']; ?>% 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .gauge-inner {
      width: 170px;
      height: 170px;
      border-radius: 50%;
      background: white;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 2.2rem;
      color: #ff3d7a;
      box-shadow: inset 0 4px 10px rgba(0,0,0,0.05);
    }

    .gauge-inner small {
      font-size: 1rem;
      color: #8e6793;
      font-weight: 400;
    }

    .stat-row {
      display: flex;
      justify-content: space-between;
      padding: 0.8rem 0;
      border-bottom: 1px dashed #f0c6df;
    }

    .stat-row:last-child {
      border-bottom: none;
    }

    .stat-label {
      color: #5a3f63;
      font-weight: 500;
    }

    .stat-value {
      font-weight: 700;
      color: #32243d;
    }

    .longest-message-box {
      background: rgba(255, 255, 255, 0.5);
      border-radius: 1.5rem;
      padding: 1.5rem;
      max-height: 200px;
      overflow-y: auto;
      font-size: 0.95rem;
      line-height: 1.5;
      border: 1px solid #f3cfdf;
    }

    .moment-item {
      background: rgba(255, 240, 250, 0.5);
      border-radius: 2rem;
      padding: 0.8rem 1.5rem;
      margin-bottom: 0.8rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      border: 1px solid #f3cfdf;
    }

    .moment-type {
      background: #eac0db;
      padding: 0.3rem 1rem;
      border-radius: 2rem;
      font-weight: 600;
      font-size: 0.85rem;
      color: #3b2445;
    }

    canvas {
      max-height: 300px;
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
      <a href="whatsapp.php" style="color:#d4588a;"><i class="fab fa-whatsapp"></i> WhatsApp</a>
      <a href="vault.php"><i class="fas fa-cloud"></i> Vault</a>
      <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <h1><i class="fab fa-whatsapp"></i> WhatsApp Love Analytics</h1>

  <!-- LOVE SCORE GAUGE -->
  <div class="glass-card center">
    <h2><i class="fas fa-heart"></i> Love Score</h2>
    <div class="gauge-container">
      <div class="gauge">
        <div class="gauge-inner">
          <?php echo $chat['love_score_percentage']; ?>%
          <small>compatibility</small>
        </div>
      </div>
    </div>
    <p style="color:#6f4f7a;">Emotional & textual bonding strength</p>
  </div>

  <!-- CORE STATS (two columns) -->
  <div class="grid-2">
    <!-- Chat Overview -->
    <div class="glass-card">
      <h3><i class="fas fa-chart-bar"></i> Chat Overview</h3>
      <div class="stat-row"><span class="stat-label">Total Messages</span><span class="stat-value"><?php echo $chat['total_messages']; ?></span></div>
      <div class="stat-row"><span class="stat-label">Media Messages</span><span class="stat-value"><?php echo $chat['media_messages']; ?></span></div>
      <div class="stat-row"><span class="stat-label">Media Files</span><span class="stat-value"><?php echo $chat['media_files_in_zip']; ?></span></div>
      <div class="stat-row"><span class="stat-label">Most Active Day</span><span class="stat-value"><?php echo $chat['most_active_day']; ?></span></div>
      <div class="stat-row"><span class="stat-label">Most Active Hour</span><span class="stat-value"><?php echo $chat['most_active_hour']; ?>:00</span></div>
    </div>

    <!-- Timeline Insights -->
    <div class="glass-card">
      <h3><i class="fas fa-clock"></i> Timeline Insights</h3>
      <div class="stat-row"><span class="stat-label">First Chat</span><span class="stat-value"><?php echo $chat['first_chat_date']; ?></span></div>
      <div class="stat-row"><span class="stat-label">Last Chat</span><span class="stat-value"><?php echo $chat['last_chat_date']; ?></span></div>
      <div class="stat-row"><span class="stat-label">Longest Streak</span><span class="stat-value"><?php echo $chat['longest_chat_streak_days']; ?> days</span></div>
      <div class="stat-row"><span class="stat-label">Longest Gap</span><span class="stat-value"><?php echo $chat['longest_no_chat_gap_days']; ?> days</span></div>
    </div>
  </div>

  <!-- USER COMPARISON CHARTS (two columns) -->
  <div class="grid-2">
    <div class="glass-card">
      <h3><i class="fas fa-users"></i> Messages per Partner</h3>
      <canvas id="msgChart"></canvas>
    </div>
    <div class="glass-card">
      <h3><i class="fas fa-heart"></i> Love Expressions (I love you + Miss you)</h3>
      <canvas id="loveChart"></canvas>
    </div>
  </div>

  <!-- LONGEST MESSAGE -->
  <div class="glass-card">
    <h3><i class="fas fa-quote-right"></i> Longest Message Ever</h3>
    <div class="stat-row"><span class="stat-label">Sender</span><span class="stat-value"><?php echo $chat['longest_message_sender']; ?></span></div>
    <div class="stat-row"><span class="stat-label">Date</span><span class="stat-value"><?php echo $chat['longest_message_datetime']; ?></span></div>
    <div class="stat-row"><span class="stat-label">Length</span><span class="stat-value"><?php echo $chat['longest_message_length']; ?> characters</span></div>
    <div class="longest-message-box">
      <?php echo nl2br(htmlspecialchars($chat['longest_message_text'])); ?>
    </div>
  </div>

  <!-- LOVE MOMENTS -->
  <div class="glass-card">
    <h3><i class="fas fa-crown"></i> First Love Moments</h3>
    <?php if(mysqli_num_rows($moments) > 0): ?>
      <?php while($m = mysqli_fetch_assoc($moments)): ?>
        <div class="moment-item">
          <span class="moment-type"><?php echo $m['moment_type']; ?></span>
          <span><i class="fas fa-user"></i> <?php echo $m['sender']; ?></span>
          <span><i class="far fa-calendar"></i> <?php echo $m['moment_datetime']; ?></span>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No special moments recorded yet.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Chart.js Scripts (identical to original, just placed after HTML) -->
<script>
  const users = [
    <?php
    mysqli_data_seek($users, 0);
    while($u = mysqli_fetch_assoc($users)){
      echo "{
        name:'{$u['user_name']}',
        messages:{$u['messages_count']},
        love:{$u['i_love_you_count']},
        miss:{$u['i_miss_you_count']}
      },";
    }
    ?>
  ];

  // Messages chart
  new Chart(document.getElementById("msgChart"), {
    type: "bar",
    data: {
      labels: users.map(u => u.name),
      datasets: [{
        label: "Messages",
        data: users.map(u => u.messages),
        backgroundColor: "#ff8aad",
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      }
    }
  });

  // Love expressions chart (pie)
  new Chart(document.getElementById("loveChart"), {
    type: "pie",
    data: {
      labels: users.map(u => u.name),
      datasets: [{
        data: users.map(u => u.love + u.miss),
        backgroundColor: ["#ff8aad", "#c472e0", "#eac0db", "#b483cf"]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
</script>
</body>
</html>