<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoveSync AI · Deeper human connections</title>
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
      background: linear-gradient(145deg, #fff9fb 0%, #f7f0ff 100%);
      color: #1e1e2f;
      line-height: 1.5;
      scroll-behavior: smooth;
    }

    /* glassy card effect */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: 2rem;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    /* navigation */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-weight: 700;
      font-size: 1.8rem;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #e3648c, #a07bda);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo i {
      font-size: 2.2rem;
      background: none;
      -webkit-text-fill-color: #e17b9f; /* fallback */
      color: #e17b9f;
    }

    .nav-buttons {
      display: flex;
      gap: 1rem;
    }

    .btn {
      display: inline-block;
      padding: 0.7rem 1.8rem;
      border-radius: 3rem;
      font-weight: 600;
      text-decoration: none;
      transition: 0.2s ease;
      font-size: 1rem;
      border: 1.5px solid transparent;
    }

    .btn-outline {
      background: transparent;
      border-color: #d9b6d2;
      color: #3f2e4a;
    }

    .btn-outline:hover {
      background: #ffffffb3;
      border-color: #c084b5;
    }

    .btn-primary {
      background: linear-gradient(145deg, #ff8aad, #c472e0);
      color: white;
      box-shadow: 0 10px 20px -8px rgba(230, 100, 140, 0.4);
    }

    .btn-primary:hover {
      transform: scale(1.02);
      box-shadow: 0 14px 24px -8px #d47eb3;
    }

    /* hero section */
    .hero {
      padding: 3rem 0 4rem 0;
      text-align: center;
    }

    .hero h1 {
      font-size: 3.8rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      background: linear-gradient(145deg, #32243d, #aa608c);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      line-height: 1.2;
      max-width: 900px;
      margin: 0 auto 1.5rem;
    }

    .hero .tagline {
      font-size: 1.5rem;
      color: #4a3859;
      font-weight: 300;
      max-width: 700px;
      margin: 0 auto 2.5rem;
      background: rgba(255,255,255,0.6);
      padding: 0.8rem 2rem;
      border-radius: 60px;
      backdrop-filter: blur(4px);
      display: inline-block;
    }

    .hero-cta {
      display: flex;
      gap: 1.5rem;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
    }

    .hero-cta .btn {
      padding: 1rem 3rem;
      font-size: 1.2rem;
    }

    /* section headings */
    .section-head {
      text-align: center;
      margin: 5rem 0 2.5rem;
    }

    .section-head h2 {
      font-size: 2.8rem;
      font-weight: 600;
      background: linear-gradient(145deg, #32243d, #8f6592);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .section-head p {
      color: #5e4b6b;
      font-size: 1.2rem;
      max-width: 600px;
      margin: 0.5rem auto 0;
    }

    /* card grid */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 2rem;
      margin: 3rem 0;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(8px);
      border-radius: 2rem;
      padding: 2rem 1.5rem;
      border: 1px solid rgba(255,255,255,0.8);
      box-shadow: 0 12px 30px -12px rgba(120, 60, 100, 0.2);
      transition: all 0.2s;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      background: rgba(255, 255, 255, 0.85);
      box-shadow: 0 30px 40px -16px #cca5c0;
    }

    .feature-icon {
      font-size: 2.6rem;
      background: linear-gradient(130deg, #ffb1c0, #d59bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 1.5rem;
    }

    .feature-card h3 {
      font-size: 1.7rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: #32243d;
    }

    .feature-card p {
      color: #3d2e48;
      margin-bottom: 1.4rem;
    }

    .feature-list {
      list-style: none;
      color: #574563;
      font-size: 0.95rem;
    }

    .feature-list li {
      margin: 0.7rem 0;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .feature-list i {
      color: #e27c9f;
      width: 1.2rem;
    }

    /* two column highlight */
    .highlight-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin: 4rem 0;
    }

    .highlight-item {
      background: rgba(255,255,240,0.5);
      backdrop-filter: blur(8px);
      border-radius: 2.5rem;
      padding: 2.5rem;
      border: 1px solid #ffeef4;
    }

    .highlight-item h3 {
      font-size: 2rem;
      font-weight: 600;
      color: #2b1d33;
      margin-bottom: 1.5rem;
    }

    .metric-badge {
      background: white;
      padding: 1rem 1.5rem;
      border-radius: 3rem;
      display: inline-flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 4px 12px #f0d9ef;
      margin: 1rem 1rem 0 0;
      font-weight: 500;
    }

    .metric-badge i {
      color: #de6e97;
    }

    /* impact strip */
    .impact-strip {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 3rem;
      background: rgba(255,255,255,0.5);
      border-radius: 5rem;
      padding: 2rem 3rem;
      margin: 4rem 0;
      backdrop-filter: blur(4px);
    }

    .impact-item {
      text-align: center;
      min-width: 130px;
    }

    .impact-item i {
      font-size: 2.8rem;
      background: linear-gradient(130deg, #ea7ba0, #b486d6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .impact-item span {
      display: block;
      font-weight: 600;
      color: #3d284b;
      margin-top: 0.5rem;
    }

    /* footer */
    .footer {
      margin-top: 6rem;
      padding: 2.5rem 2rem;
      background: rgba(255, 235, 245, 0.6);
      border-radius: 3rem 3rem 0 0;
      text-align: center;
      color: #4e3e58;
    }

    .footer .links {
      display: flex;
      justify-content: center;
      gap: 2.5rem;
      flex-wrap: wrap;
      margin: 1.5rem 0;
    }

    .footer a {
      text-decoration: none;
      color: #6b4d77;
      font-weight: 500;
    }

    .footer a:hover {
      color: #c5538c;
    }

    /* container for floating hearts */
    .hearts-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none; /* so clicks pass through */
      z-index: 9999;
      overflow: hidden;
    }

    .heart {
      position: absolute;
      color: #ff8aad;
      font-size: 1rem;
      opacity: 0.8;
      animation: floatUp linear forwards;
      user-select: none;
      pointer-events: none;
    }

    @keyframes floatUp {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.8;
      }
      100% {
        transform: translateY(-100vh) rotate(20deg);
        opacity: 0;
      }
    }

    @media (max-width: 700px) {
      .navbar {
        flex-direction: column;
        gap: 1rem;
      }
      .hero h1 {
        font-size: 2.5rem;
      }
      .highlight-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <!-- floating hearts container -->
  <div class="hearts-container" id="heartsContainer"></div>

  <!-- navigation with login / signup links -->
  <nav class="navbar">
    <div class="logo">
      <i class="fas fa-heart-circle-check"></i>
      <span>LoveSync AI</span>
    </div>
    <div class="nav-buttons">
      <a href="login.php" class="btn btn-outline"><i class="far fa-user" style="margin-right: 0.4rem;"></i>Log in</a>
      <a href="signup.php" class="btn btn-primary">Sign up <i class="fas fa-arrow-right" style="margin-left: 0.4rem;"></i></a>
    </div>
  </nav>

  <main class="container">
    <!-- hero -->
    <section class="hero">
      <h1>Measure, cherish & strengthen <br>human connection through AI.</h1>
      <div class="tagline">
        <i class="fas fa-heart" style="color: #e6769e; margin-right: 8px;"></i> 
        for couples & singles · code for connection
      </div>
      <div class="hero-cta">
        <a href="signup.php" class="btn btn-primary" style="font-size: 1.3rem; padding: 1rem 3.5rem;">Start syncing <i class="fas fa-sparkles" style="margin-left: 0.5rem;"></i></a>
        <a href="#modules" class="btn btn-outline" style="font-size: 1.3rem;">Explore features</a>
      </div>
    </section>

    <!-- tagline + brief overview -->
    <div style="text-align: center; margin: 2rem 0 1rem; font-size: 1.4rem; color: #5f4a6e;">
      ✦ Relationship intelligence · Memory preservation · AI emotional tools ✦
    </div>

    <!-- modules section (Singles Connect, Compatibility, Memory Vault etc) -->
    <div id="modules" class="section-head">
      <h2>designed for every heartbeat</h2>
      <p>Modules for singles, couples, and everyone in between</p>
    </div>

    <div class="card-grid">
      <!-- Singles Connect Zone -->
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-comments"></i></div>
        <h3>Singles Connect</h3>
        <p>Safe public space with moderated chatrooms, AI conversation starters & compatibility lite.</p>
        <ul class="feature-list">
          <li><i class="fas fa-shield-heart"></i> Profanity filter & blocker</li>
          <li><i class="fas fa-robot"></i> “What’s your comfort movie?”</li>
          <li><i class="fas fa-chart-simple"></i> 82% match indicator</li>
        </ul>
      </div>

      <!-- Compatibility & Insights -->
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
        <h3>Compatibility Zone</h3>
        <p>Astrology reports, WhatsApp chat analyzer, “I miss you” counter, distance tracker.</p>
        <ul class="feature-list">
          <li><i class="fas fa-star"></i> Love score & message ratio</li>
          <li><i class="fas fa-map-pin"></i> “214 days together / 1,280 km apart”</li>
          <li><i class="fas fa-face-smile"></i> Positive vs negative %</li>
        </ul>
      </div>

      <!-- Memory Vault & AI Moments -->
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-cloud-moon"></i></div>
        <h3>Memory Vault</h3>
        <p>Upload Google Drive folder, AI creates monthly recap videos & “on this day” reels.</p>
        <ul class="feature-list">
          <li><i class="fas fa-calendar-alt"></i> Event clustering</li>
          <li><i class="fas fa-video"></i> Auto slideshows + music</li>
          <li><i class="fas fa-image"></i> Metadata fetch</li>
        </ul>
      </div>

      <!-- Celebration Scheduler -->
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-envelope"></i></div>
        <h3>Care Scheduler</h3>
        <p>Automated love emails, birthday & anniversary reminders, surprise notes via cron.</p>
        <ul class="feature-list">
          <li><i class="fas fa-birthday-cake"></i> His/her birthday counters</li>
          <li><i class="fas fa-clock"></i> Scheduled delivery</li>
          <li><i class="fas fa-heart"></i> Apology message templates</li>
        </ul>
      </div>
    </div>

    <!-- deep-dive highlight: whatsapp analyzer + memory ai -->
    <div class="highlight-grid">
      <div class="highlight-item">
        <h3>🧠 WhatsApp Analyzer</h3>
        <p style="font-size: 1.2rem; margin-bottom: 1.5rem;">Upload .txt export and reveal your love story in data</p>
        <div>
          <span class="metric-badge"><i class="fas fa-heart"></i> Love score 94%</span>
          <span class="metric-badge"><i class="fas fa-arrows-left-right"></i> Response time 4m</span>
          <span class="metric-badge"><i class="fas fa-face-smile"></i> 78% positive</span>
        </div>
        <div style="margin-top: 2rem;">
          <span class="metric-badge"><i class="fas fa-clock"></i> First chat: 12 Aug 2022</span>
          <span class="metric-badge"><i class="fas fa-miss"></i> “miss you” counter 143×</span>
        </div>
        <p style="margin-top: 2rem;"><i class="fas fa-calendar-week" style="color:#af69b0;"></i> longest gap without “miss you”: 6 days</p>
      </div>

      <div class="highlight-item">
        <h3>🤍 AI Memory Vault</h3>
        <p style="font-size: 1.2rem;">Your Google Drive folder → magical recap videos</p>
        <ul class="feature-list" style="font-size: 1.1rem;">
          <li><i class="fas fa-chart-line"></i> upload frequency · monthly memory count</li>
          <li><i class="fas fa-film"></i> anniversary slideshows with music</li>
          <li><i class="fas fa-rotate-left"></i> “on this day” reels</li>
        </ul>
        <div style="background: rgba(255,255,255,0.8); padding: 1rem; border-radius: 2rem; margin-top: 2rem;">
          <i class="fas fa-cloud" style="color:#b47bd6;"></i> connected with Google Drive · metadata fetched
        </div>
      </div>
    </div>

    <!-- Astrology / distance tracker teaser -->
    <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: center; background: #fbeaf2; border-radius: 4rem; padding: 2rem; margin: 3rem 0;">
      <div style="display: flex; align-items: center; gap: 1rem;"><i class="fas fa-star" style="color:#c86faa; font-size: 2rem;"></i> <span><strong>Astrology compatibility</strong> — report with strength areas & conflict traits</span></div>
      <div style="display: flex; align-items: center; gap: 1rem;"><i class="fas fa-location-dot" style="color:#b46f9f; font-size: 2rem;"></i> <span><strong>How far are we?</strong> distance in km, travel time, map</span></div>
    </div>

    <!-- target impact + hackathon value -->
    <div class="section-head">
      <h2>built to bring us closer</h2>
      <p>impact that matters — under Code for Connection</p>
    </div>

    <div class="impact-strip">
      <div class="impact-item"><i class="fas fa-comment-dots"></i><span>emotional communication</span></div>
      <div class="impact-item"><i class="fas fa-person-walking"></i><span>reduce loneliness</span></div>
      <div class="impact-item"><i class="fas fa-camera-retro"></i><span>preserve memories</span></div>
      <div class="impact-item"><i class="fas fa-balloon"></i><span>celebrate moments</span></div>
      <div class="impact-item"><i class="fas fa-handshake"></i><span>meaningful bonds</span></div>
    </div>

    <!-- quick tech & call to action -->
    <div style="background: rgba(255,225,240,0.5); border-radius: 3rem; padding: 3rem; text-align: center; margin: 4rem 0;">
      <h3 style="font-size: 2.2rem; font-weight: 600; color:#2d1b33">AI + emotional analytics + memory intelligence</h3>
      <p style="font-size: 1.3rem; max-width: 700px; margin: 1.5rem auto;">Flask / Node, React, MongoDB, VADER · MoviePy · Google Drive API</p>
      <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin: 2.5rem 0;">
        <a href="signup.php" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.4rem;">Join the waitlist <i class="fas fa-arrow-right"></i></a>
        <a href="login.php" class="btn btn-outline" style="padding: 1rem 3rem; font-size: 1.4rem;">Login <i class="fas fa-user"></i></a>
      </div>
      <p style="color:#6c4f76;"><i class="fas fa-crown" style="color:#f1b0cc;"></i> future scope: mobile app · voice emotion · AR timelines · AI coach</p>
    </div>

    <!-- team / hackathon extra -->
    <div style="display: flex; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; background: #ffffffc9; border-radius: 2rem; padding: 2rem;">
      <div><i class="fas fa-paint-brush" style="color:#e485ac;"></i> Frontend UI/UX</div>
      <div><i class="fas fa-server" style="color:#b481c9;"></i> Backend APIs</div>
      <div><i class="fas fa-brain" style="color:#c079b5;"></i> AI / NLP</div>
      <div><i class="fas fa-video" style="color:#dc92b5;"></i> Media processing</div>
      <div><i class="fas fa-rocket" style="color:#ac79cd;"></i> Integration & deployment</div>
    </div>
  </main>

  <footer class="footer">
    <div class="logo" style="justify-content: center; margin-bottom: 1rem;">
      <i class="fas fa-heart-circle-check"></i> LoveSync AI
    </div>
    <div class="links">
      <a href="#">About</a>
      <a href="#">For couples</a>
      <a href="#">For singles</a>
      <a href="#">Privacy</a>
      <a href="login.php">Log in</a>
      <a href="signup.php">Sign up</a>
    </div>
    <p style="opacity: 0.7; margin-top: 2rem;">© 2025 LoveSync AI — measure, cherish & strengthen connection. <br> Hackathon “Code for Connection” project.</p>
  </footer>

  <!-- Script for floating hearts (now truly random positions) -->
  <script>
    (function() {
      const container = document.getElementById('heartsContainer');
      const colors = ['#ff8aad', '#e6769e', '#c472e0', '#ffb1c0', '#d59bff', '#f48fb1', '#ce93d8'];

      function createHeart() {
        const heart = document.createElement('i');
        heart.classList.add('fas', 'fa-heart', 'heart');
        
        // Randomize everything
        const size = Math.random() * 2 + 0.8; // 0.8rem to 2.8rem
        const left = Math.random() * 100; // random % of viewport width
        const top = Math.random() * 100;  // random % of viewport height
        const animDuration = Math.random() * 4 + 3; // 3s to 7s
        const color = colors[Math.floor(Math.random() * colors.length)];
        const rotation = Math.random() * 30 - 15; // -15deg to 15deg

        heart.style.left = left + '%';
        heart.style.top = top + '%';
        heart.style.fontSize = size + 'rem';
        heart.style.animation = `floatUp ${animDuration}s ease-in forwards`;
        heart.style.color = color;
        heart.style.transform = `rotate(${rotation}deg)`;

        container.appendChild(heart);

        // Remove heart after animation ends
        setTimeout(() => {
          if (heart.parentNode) heart.remove();
        }, animDuration * 1000);
      }

      // Generate hearts at random intervals (between 200ms and 700ms)
      setInterval(createHeart, Math.random() * 500 + 200);
    })();
  </script>
</body>
</html>